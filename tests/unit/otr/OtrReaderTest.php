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
#[UsesClass(Metric::class)]
#[UsesClass(TestRun::class)]
#[UsesClass(TestRunCollection::class)]
#[TestDox('OtrReader')]
#[Small]
final class OtrReaderTest extends TestCase
{
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

    public function testReadsALogfileWithoutTests(): void
    {
        $run = (new OtrReader)->read($this->fixture('empty.xml'));

        $this->assertTrue($run->isEmpty());
        $this->assertSame(0, $run->testCount());
        $this->assertSame(5.0, $run->totalTime());
        $this->assertSame([], $run->tests());
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

    private function fixture(string $name): string
    {
        return __DIR__ . '/../../fixture/' . $name;
    }
}
