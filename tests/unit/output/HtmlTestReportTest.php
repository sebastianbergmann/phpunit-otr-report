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

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTestReport::class)]
#[UsesClass(Issue::class)]
#[UsesClass(TestResult::class)]
#[UsesClass(TestRun::class)]
#[UsesClass(TestStatus::class)]
#[UsesClass(Throwable::class)]
#[TestDox('HtmlTestReport')]
#[Small]
final class HtmlTestReportTest extends TestCase
{
    public function testRendersATestResultsReportForASingleRun(): void
    {
        $run = $this->createTestRun(
            [
                new TestResult('Vendor\ExampleTest', 'test_ok', 'test_ok', TestStatus::Successful, '', null, [], 0.001),
                new TestResult('Vendor\ExampleTest', 'test_fail', 'test_fail', TestStatus::Failed, 'Failed asserting that false is true.', new Throwable('PHPUnit\Framework\ExpectationFailedException', true, 'Failed asserting that false is true.'), [], 0.002),
                new TestResult('Vendor\OtherTest', 'test_error', 'test_error', TestStatus::Errored, 'boom', new Throwable('RuntimeException', false, 'boom'), [], 0.003),
                new TestResult('Vendor\OtherTest', 'test_risky', 'test_risky', TestStatus::Successful, '', null, [new Issue('risky', 'no assertions')], 0.001),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringContainsString('<title>Test Results</title>', $html);
        $this->assertStringContainsString('Vendor\ExampleTest', $html);
        $this->assertStringContainsString('Vendor\OtherTest', $html);
        $this->assertStringContainsString('test_ok', $html);
        $this->assertStringContainsString('test_fail', $html);
        $this->assertStringContainsString('test_error', $html);
        $this->assertStringContainsString('PHPUnit\Framework\ExpectationFailedException', $html);
        $this->assertStringContainsString('RuntimeException', $html);
        $this->assertStringContainsString('pill issue', $html);
        $this->assertStringContainsString('no assertions', $html);
    }

    public function testShowsAStatusCountForEveryOtrStatus(): void
    {
        $run = $this->createTestRun(
            [
                new TestResult('Vendor\ExampleTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestResult('Vendor\ExampleTest', 'b', 'b', TestStatus::Failed, '', null, [], 0.0),
                new TestResult('Vendor\ExampleTest', 'c', 'c', TestStatus::Errored, '', null, [], 0.0),
                new TestResult('Vendor\ExampleTest', 'd', 'd', TestStatus::Aborted, '', null, [], 0.0),
                new TestResult('Vendor\ExampleTest', 'e', 'e', TestStatus::Skipped, '', null, [], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringContainsString('card successful', $html);
        $this->assertStringContainsString('card failed', $html);
        $this->assertStringContainsString('card errored', $html);
        $this->assertStringContainsString('card aborted', $html);
        $this->assertStringContainsString('card skipped', $html);
    }

    public function testOpensFailingResultsAndKeepsSuccessfulOnesCollapsed(): void
    {
        $run = $this->createTestRun(
            [
                new TestResult('Vendor\ExampleTest', 'test_ok', 'test_ok', TestStatus::Successful, '', null, [], 0.0),
                new TestResult('Vendor\ExampleTest', 'test_fail', 'test_fail', TestStatus::Failed, 'reason', null, [], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertMatchesRegularExpression('/<details class="result " id="test-\d+" open>\s*<summary><span class="pill failed">/', $html);
        $this->assertMatchesRegularExpression('/<details class="result no-details" id="test-\d+" >\s*<summary><span class="pill successful">/', $html);
    }

    public function testRendersASidebarTreeWithNamespacesClassesAndMethods(): void
    {
        $run = $this->createTestRun(
            [
                new TestResult('Vendor\Pkg\AlphaTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestResult('Vendor\Pkg\BetaTest', 'b', 'b', TestStatus::Failed, '', null, [], 0.0),
                new TestResult('OtherTest', 'c', 'c', TestStatus::Skipped, '', null, [], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringContainsString('<aside class="sidebar">', $html);
        $this->assertStringContainsString('ul class="tree tree-root"', $html);
        $this->assertStringContainsString('class="seg">Vendor</span>', $html);
        $this->assertStringContainsString('class="seg">Pkg</span>', $html);
        $this->assertStringContainsString('class="seg">AlphaTest</span>', $html);
        $this->assertStringContainsString('class="seg">BetaTest</span>', $html);
        $this->assertStringContainsString('class="seg">OtherTest</span>', $html);
        $this->assertMatchesRegularExpression('/<a href="#class-\d+" class="cls-link">/', $html);
        $this->assertMatchesRegularExpression('/<li class="m"><a href="#test-\d+">/', $html);
    }

    public function testPropagatesTheWorstStatusUpTheSidebarTree(): void
    {
        $run = $this->createTestRun(
            [
                new TestResult('Vendor\Pkg\AlphaTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestResult('Vendor\Pkg\BetaTest', 'b', 'b', TestStatus::Errored, 'boom', null, [], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertMatchesRegularExpression(
            '/<summary><span class="marker"><\/span><span class="dot errored"><\/span><span class="seg">Vendor<\/span>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<summary><span class="marker"><\/span><span class="dot errored"><\/span><span class="seg">Pkg<\/span>/',
            $html,
        );
    }

    public function testEscapesUserSuppliedStrings(): void
    {
        $run = $this->createTestRun(
            [
                new TestResult(
                    'Vendor\ExampleTest',
                    'test_one',
                    'test_one',
                    TestStatus::Failed,
                    '<script>alert(1)</script>',
                    new Throwable('Some<Type>', false, '"quoted" & escaped'),
                    [new Issue('warning', 'message with <tags>')],
                    0.0,
                ),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('Some&lt;Type&gt;', $html);
        $this->assertStringContainsString('&quot;quoted&quot; &amp; escaped', $html);
        $this->assertStringContainsString('message with &lt;tags&gt;', $html);
    }

    /**
     * @param list<TestResult> $results
     */
    private function createTestRun(array $results): TestRun
    {
        return new TestRun(
            '/path/to/run.xml',
            new DateTimeImmutable('2026-01-01T08:00:00.000000Z'),
            0.01,
            [],
            [],
            [],
            $results,
        );
    }
}
