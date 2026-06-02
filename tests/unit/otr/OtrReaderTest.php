<?php declare(strict_types=1);
/*
 * This file is part of phpunit/otr-report.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\OtrReport;

use function array_key_exists;
use function basename;
use function count;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OtrReader::class)]
#[UsesClass(Issue::class)]
#[UsesClass(Metric::class)]
#[UsesClass(PhptResult::class)]
#[UsesClass(SchemaValidationException::class)]
#[UsesClass(TestMethodResult::class)]
#[UsesClass(TestResult::class)]
#[UsesClass(TestRun::class)]
#[UsesClass(TestRunCollection::class)]
#[UsesClass(TestStatus::class)]
#[UsesClass(Throwable::class)]
#[TestDox('OtrReader')]
#[Small]
final class OtrReaderTest extends TestCase
{
    public function testRejectsALogfileThatIsNotValidOtrXml(): void
    {
        $this->expectException(SchemaValidationException::class);
        $this->expectExceptionMessage('invalid.xml is not a valid OTR XML logfile');

        (new OtrReader)->read($this->fixture('invalid.xml'));
    }

    public function testRestoresLibxmlErrorHandlingAfterValidatingALogfile(): void
    {
        $previous = libxml_use_internal_errors(false);

        try {
            (new OtrReader)->read($this->fixture('minimal.xml'));

            $this->assertFalse(libxml_use_internal_errors(true));
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    public function testDoesNotLeaveValidationErrorsInTheGlobalLibxmlErrorBuffer(): void
    {
        $previous = libxml_use_internal_errors(true);

        try {
            try {
                (new OtrReader)->read($this->fixture('invalid.xml'));
            } catch (SchemaValidationException) {
            }

            $this->assertSame([], libxml_get_errors());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function testReadsARealLogfileIntoATestRun(): void
    {
        $run = (new OtrReader)->read($this->fixture('raytracer.xml'));

        $this->assertEquals(new DateTimeImmutable('2026-05-24T08:58:25.786969Z'), $run->startedAt());
        $this->assertSame(177, $run->testCount());
        $this->assertEqualsWithDelta(10.56532016, $run->totalTime(), 1e-9);

        $tests      = $run->tests();
        $cpuTimes   = $run->valuesFor(Metric::Cpu);
        $peakMemory = $run->valuesFor(Metric::Memory);
        $name       = 'SebastianBergmann\Raytracer\CameraTest::test_constructing_a_camera';

        if (!array_key_exists($name, $tests) ||
            !array_key_exists($name, $cpuTimes) ||
            !array_key_exists($name, $peakMemory)) {
            $this->fail('Expected test was not found in the parsed metrics');
        }

        $this->assertEqualsWithDelta(0.000549776, $tests[$name], 1e-12);
        $this->assertEqualsWithDelta(0.000524, $cpuTimes[$name], 1e-9);
        $this->assertSame(19962808.0, $peakMemory[$name]);
    }

    public function testCollectsTotalTimeAndOnlyTestsWithAMethodSourceAndResourceUsage(): void
    {
        $run = (new OtrReader)->read($this->fixture('minimal.xml'));

        $this->assertEquals(new DateTimeImmutable('2026-03-03T09:00:00.000000Z'), $run->startedAt());
        $this->assertSame(1.5, $run->totalTime());
        $this->assertSame(['Vendor\GroupTest::test_alpha' => 0.25], $run->tests());
        $this->assertSame(1, $run->testCount());
    }

    public function testCollectsPhptTestsKeyedByTheirFilePath(): void
    {
        $run = (new OtrReader)->read($this->fixture('phpunit.xml'));

        $name       = '/path/to/phpunit/tests/end-to-end/testdox/diff.phpt';
        $tests      = $run->tests();
        $cpuTimes   = $run->valuesFor(Metric::Cpu);
        $peakMemory = $run->valuesFor(Metric::Memory);

        if (!array_key_exists($name, $tests) ||
            !array_key_exists($name, $cpuTimes) ||
            !array_key_exists($name, $peakMemory)) {
            $this->fail('Expected PHPT test was not found in the parsed metrics');
        }

        $this->assertEqualsWithDelta(0.294980159, $tests[$name], 1e-12);
        $this->assertEqualsWithDelta(0.001841, $cpuTimes[$name], 1e-9);
        $this->assertSame(60843576.0, $peakMemory[$name]);
    }

    public function testRepresentsPhptTestsAsPhptResultsCarryingTheirFilePath(): void
    {
        $run  = (new OtrReader)->read($this->fixture('phpunit.xml'));
        $path = '/path/to/phpunit/tests/end-to-end/testdox/diff.phpt';

        foreach ($run->results() as $result) {
            if ($result instanceof PhptResult && $result->path() === $path) {
                $this->assertSame(TestStatus::Successful, $result->status());

                return;
            }
        }

        $this->fail('Expected a PhptResult for ' . $path);
    }

    public function testAssociatesTestsWithTheirConfiguredTestSuite(): void
    {
        $run = (new OtrReader)->read($this->fixture('phpunit.xml'));

        $methodSuite = null;
        $phptSuite   = null;

        foreach ($run->results() as $result) {
            if ($result instanceof TestMethodResult &&
                $result->className() === 'PHPUnit\Framework\assertArraysAreEqualIgnoringOrderTest') {
                $methodSuite = $result->suite();
            }

            if ($result instanceof PhptResult &&
                $result->path() === '/path/to/phpunit/tests/end-to-end/testdox/diff.phpt') {
                $phptSuite = $result->suite();
            }
        }

        $this->assertSame('unit', $methodSuite);
        $this->assertSame('end-to-end', $phptSuite);
    }

    public function testLeavesTestsWithoutAConfiguredSuiteUnassigned(): void
    {
        $run = (new OtrReader)->read($this->fixture('status.xml'));

        $this->assertNotSame([], $run->results());

        foreach ($run->results() as $result) {
            $this->assertNull($result->suite());
            $this->assertFalse($result->hasSuite());
        }
    }

    public function testReadsALogfileWithoutTests(): void
    {
        $run = (new OtrReader)->read($this->fixture('empty.xml'));

        $this->assertTrue($run->isEmpty());
        $this->assertSame(0, $run->testCount());
        $this->assertSame(5.0, $run->totalTime());
        $this->assertSame([], $run->tests());
    }

    public function testParsesLogfilesRegardlessOfTheNamespacePrefixesUsed(): void
    {
        $run = (new OtrReader)->read($this->fixture('prefixed.xml'));

        $this->assertSame(1, $run->testCount());
        $this->assertSame(1.5, $run->totalTime());
        $this->assertSame(['Vendor\GroupTest::test_alpha' => 0.25], $run->tests());
        $this->assertSame(['Vendor\GroupTest::test_alpha' => 0.2], $run->valuesFor(Metric::Cpu));
        $this->assertSame(['Vendor\GroupTest::test_alpha' => 1048576.0], $run->valuesFor(Metric::Memory));
    }

    public function testReportsZeroTotalTimeWhenTheSuiteHasNoResourceUsage(): void
    {
        $run = (new OtrReader)->read($this->fixture('without-total-time.xml'));

        $this->assertSame(0.0, $run->totalTime());
        $this->assertSame(1, $run->testCount());
    }

    public function testReadsADirectoryOfLogfilesSortedByStartTime(): void
    {
        $collection = (new OtrReader)->readDirectory($this->fixture('runs'));

        $this->assertSame(2, $collection->count());

        $runs = $collection->asArray();

        if (count($runs) !== 2) {
            $this->fail('Expected exactly two runs in the collection');
        }

        $this->assertSame('bbb.xml', basename($runs[0]->file()));
        $this->assertSame('aaa.xml', basename($runs[1]->file()));
        $this->assertEquals(new DateTimeImmutable('2026-01-01T10:00:00.000000Z'), $runs[0]->startedAt());
        $this->assertEquals(new DateTimeImmutable('2026-02-02T10:00:00.000000Z'), $runs[1]->startedAt());
    }

    public function testCollectsTestResultsWithStatusReasonThrowableAndIssues(): void
    {
        $run = (new OtrReader)->read($this->fixture('status.xml'));

        $this->assertSame(13, $run->resultCount());
        $this->assertTrue($run->hasResults());
        $this->assertSame(
            [
                'SUCCESSFUL' => 4,
                'FAILED'     => 2,
                'ERRORED'    => 2,
                'ABORTED'    => 2,
                'SKIPPED'    => 3,
            ],
            $run->statusCounts(),
        );
        $this->assertSame(2, $run->issueCount());

        $error = $this->findByMethod($run, 'testError');
        $this->assertSame(TestStatus::Errored, $error->status());

        $errorThrowable = $error->throwable();

        if ($errorThrowable === null) {
            $this->fail('Expected testError to carry a throwable');
        }

        $this->assertSame('RuntimeException', $errorThrowable->type());
        $this->assertFalse($errorThrowable->assertionError());

        $failure = $this->findByMethod($run, 'testFailure');
        $this->assertSame(TestStatus::Failed, $failure->status());
        $this->assertSame('Failed asserting that false is true.', $failure->reason());

        $failureThrowable = $failure->throwable();

        if ($failureThrowable === null) {
            $this->fail('Expected testFailure to carry a throwable');
        }

        $this->assertTrue($failureThrowable->assertionError());

        $this->assertSame(TestStatus::Aborted, $this->findByMethod($run, 'testIncomplete')->status());
        $this->assertSame(TestStatus::Skipped, $this->findByMethod($run, 'testSkipped')->status());

        $risky = $this->findByMethod($run, 'testRisky');
        $this->assertSame(TestStatus::Successful, $risky->status());
        $issues = $risky->issues();
        $this->assertCount(1, $issues);

        if (!isset($issues[0])) {
            $this->fail('Expected at least one issue on testRisky');
        }

        $this->assertSame('risky', $issues[0]->type());

        $skippedByMetadata = $this->findByMethod($run, 'testSkippedByMetadata');
        $this->assertNull($skippedByMetadata->time());
        $this->assertSame('PHP > 9000.0.0 is required.', $skippedByMetadata->reason());
    }

    public function testReadsTestDoxPrettifiedClassAndMethodNames(): void
    {
        $run = (new OtrReader)->read($this->fixture('status.xml'));

        $error = $this->findByMethod($run, 'testError');

        $this->assertSame('Test result status with and without message', $error->prettifiedClassName());
        $this->assertSame('Error', $error->prettifiedMethodName());
    }

    public function testIgnoresStartedNodesWhoseOnlySourceIsANonPhptFile(): void
    {
        $run = (new OtrReader)->read($this->fixture('non-phpt-file-source.xml'));

        $this->assertTrue($run->isEmpty());
        $this->assertSame(0, $run->resultCount());
    }

    public function testIgnoresReportedEventsThatDoNotBelongToATestMethodOrPhpt(): void
    {
        $run = (new OtrReader)->read($this->fixture('reported-without-test.xml'));

        $this->assertSame(1, $run->resultCount());

        $result = $this->findByMethod($run, 'test_alpha');

        $this->assertSame([], $result->issues());
        $this->assertFalse($result->hasIssues());
    }

    public function testIgnoresResultsThatDoNotCarryAStatus(): void
    {
        $run = (new OtrReader)->read($this->fixture('result-without-status.xml'));

        $this->assertSame(0, $run->resultCount());
        $this->assertSame(1, $run->testCount());
        $this->assertSame(['Vendor\GroupTest::test_alpha' => 0.1], $run->tests());
    }

    public function testReadsADirectoryWithoutLogfiles(): void
    {
        $directory = sys_get_temp_dir() . '/otr-report-' . uniqid();

        mkdir($directory);

        try {
            $collection = (new OtrReader)->readDirectory($directory);

            $this->assertTrue($collection->isEmpty());
            $this->assertSame(0, $collection->count());
        } finally {
            rmdir($directory);
        }
    }

    private function findByMethod(TestRun $run, string $methodName): TestMethodResult
    {
        foreach ($run->results() as $result) {
            if ($result instanceof TestMethodResult && $result->methodName() === $methodName) {
                return $result;
            }
        }

        $this->fail('No result found for ' . $methodName);
    }

    private function fixture(string $name): string
    {
        return __DIR__ . '/../../fixture/' . $name;
    }
}
