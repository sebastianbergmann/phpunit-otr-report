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

use function strpos;
use function strtolower;
use function substr;
use function substr_count;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlTestReport::class)]
#[UsesClass(Issue::class)]
#[UsesClass(PhptResult::class)]
#[UsesClass(TestMethodResult::class)]
#[UsesClass(TestResult::class)]
#[UsesClass(TestRun::class)]
#[UsesClass(TestStatus::class)]
#[UsesClass(Throwable::class)]
#[TestDox('HtmlTestReport')]
#[Small]
final class HtmlTestReportTest extends TestCase
{
    /**
     * @return iterable<string, array{0: array{0: TestStatus, 1: TestStatus}, 1: TestStatus}>
     */
    public static function worstStatusPairs(): iterable
    {
        yield 'successful vs skipped' => [[TestStatus::Successful, TestStatus::Skipped], TestStatus::Skipped];

        yield 'skipped vs aborted' => [[TestStatus::Skipped, TestStatus::Aborted], TestStatus::Aborted];

        yield 'aborted vs failed' => [[TestStatus::Aborted, TestStatus::Failed], TestStatus::Failed];

        yield 'failed vs errored' => [[TestStatus::Failed, TestStatus::Errored], TestStatus::Errored];
    }

    public function testRendersATestResultsReportForASingleRun(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 'test_ok', 'test_ok', TestStatus::Successful, '', null, [], 0.001),
                new TestMethodResult('Vendor\ExampleTest', 'test_fail', 'test_fail', TestStatus::Failed, 'Failed asserting that false is true.', new Throwable('PHPUnit\Framework\ExpectationFailedException', true, 'Failed asserting that false is true.'), [], 0.002),
                new TestMethodResult('Vendor\OtherTest', 'test_error', 'test_error', TestStatus::Errored, 'boom', new Throwable('RuntimeException', false, 'boom'), [], 0.003),
                new TestMethodResult('Vendor\OtherTest', 'test_risky', 'test_risky', TestStatus::Successful, '', null, [new Issue('risky', 'no assertions')], 0.001),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringContainsString('<title>Test Results</title>', $html);
        $this->assertStringContainsString('<strong>/path/to/run.xml</strong>', $html);
        $this->assertStringContainsString('Vendor\ExampleTest', $html);
        $this->assertStringContainsString('Vendor\OtherTest', $html);
        $this->assertStringContainsString('test_ok', $html);
        $this->assertStringContainsString('test_fail', $html);
        $this->assertStringContainsString('test_error', $html);
        $this->assertStringContainsString('<div class="details-body"><div class="label">Reason</div><pre>Failed asserting that false is true.</pre><div class="label">Throwable (PHPUnit\Framework\ExpectationFailedException)</div><pre>Failed asserting that false is true.</pre>', $html);
        $this->assertStringContainsString('<div class="details-body"><div class="label">Reason</div><pre>boom</pre><div class="label">Throwable (RuntimeException)</div><pre>boom</pre>', $html);
        $this->assertStringContainsString('<li><span class="pill issue">risky</span><span class="msg">no assertions</span></li>', $html);
    }

    public function testShowsAStatusCountForEveryOtrStatus(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'b', 'b', TestStatus::Failed, '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'c', 'c', TestStatus::Errored, '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'd', 'd', TestStatus::Aborted, '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'e', 'e', TestStatus::Skipped, '', null, [], 0.0),
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
                new TestMethodResult('Vendor\ExampleTest', 'test_ok', 'test_ok', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'test_fail', 'test_fail', TestStatus::Failed, 'reason', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'test_risky', 'test_risky', TestStatus::Successful, '', null, [new Issue('risky', 'no assertions')], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertMatchesRegularExpression('/<details class="result " id="test-\d+" open>\s*<summary><span class="pill failed">/', $html);
        $this->assertMatchesRegularExpression('/<details class="result no-details" id="test-\d+" >\s*<summary><span class="pill successful">[^<]*<\/span><span class="method">test_ok/', $html);
        $this->assertMatchesRegularExpression('/<details class="result " id="test-\d+" >\s*<summary><span class="pill successful">[^<]*<\/span><span class="method">test_risky/', $html);
    }

    public function testRendersClassIdAsAnchorTarget(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 't', 't', TestStatus::Successful, '', null, [], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertMatchesRegularExpression('/<details class="class" id="class-\d+" open>/', $html);
    }

    public function testRendersClassTotalTimeAsTheSumOfItsTestTimes(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.001),
                new TestMethodResult('Vendor\ExampleTest', 'b', 'b', TestStatus::Successful, '', null, [], 0.002),
                new TestMethodResult('Vendor\ExampleTest', 'c', 'c', TestStatus::Skipped, '', null, [], null),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringContainsString('<span class="time">0.003000 s</span>', $html);
    }

    public function testRendersMultipleIssuePillsAsSummaryBadges(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult(
                    'Vendor\ExampleTest',
                    'test_many_issues',
                    'test_many_issues',
                    TestStatus::Successful,
                    '',
                    null,
                    [new Issue('risky', 'r'), new Issue('deprecation', 'd')],
                    0.0,
                ),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringContainsString(
            '<span class="pill issue">risky</span><span class="pill issue">deprecation</span>',
            $html,
        );
    }

    public function testRendersASidebarTreeWithNamespacesClassesAndMethods(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\Pkg\BetaTest', 'b', 'b', TestStatus::Failed, '', null, [], 0.0),
                new TestMethodResult('Vendor\Pkg\AlphaTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('OtherTest', 'c', 'c', TestStatus::Skipped, '', null, [], 0.0),
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

        $sidebar = $this->sidebarMarkup($html);

        $this->assertSame(1, substr_count($sidebar, 'class="seg">Vendor</span>'));
        $this->assertSame(1, substr_count($sidebar, 'class="seg">Pkg</span>'));

        $alpha = strpos($sidebar, 'AlphaTest');
        $beta  = strpos($sidebar, 'BetaTest');
        $other = strpos($sidebar, 'OtherTest');

        $this->assertNotFalse($alpha);
        $this->assertNotFalse($beta);
        $this->assertNotFalse($other);
        $this->assertLessThan($beta, $alpha);
        $this->assertLessThan($alpha, $other);
    }

    public function testClosesEveryOpenNamespaceInTheSidebar(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('A\B\C\AlphaTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('OtherTest', 'b', 'b', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('Z\Y\X\OmegaTest', 'c', 'c', TestStatus::Successful, '', null, [], 0.0),
            ],
        );

        $html    = (new HtmlTestReport)->render($run);
        $sidebar = $this->sidebarMarkup($html);

        $this->assertSame(
            substr_count($sidebar, '<details open><summary>'),
            substr_count($sidebar, '</ul></details></li>'),
        );
    }

    public function testRendersEveryMethodOfAClassInTheSidebar(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 'first', 'first', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'second', 'second', TestStatus::Failed, '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'third', 'third', TestStatus::Skipped, '', null, [], 0.0),
            ],
        );

        $sidebar = $this->sidebarMarkup((new HtmlTestReport)->render($run));

        $this->assertStringContainsString('class="seg">first</span>', $sidebar);
        $this->assertStringContainsString('class="seg">second</span>', $sidebar);
        $this->assertStringContainsString('class="seg">third</span>', $sidebar);
    }

    public function testPropagatesTheWorstStatusUpEveryLevelOfTheSidebarTree(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Root\Mid\Leaf\AlphaTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('Root\Mid\XtraTest', 'x', 'x', TestStatus::Errored, 'boom', null, [], 0.0),
                new TestMethodResult('Root\Sibling\GammaTest', 'g', 'g', TestStatus::Successful, '', null, [], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        // "Root" is the shared prefix and is collapsed away; "Mid" carries the
        // errored status of Root\Mid\XtraTest, while its child "Leaf" (which only
        // contains the successful AlphaTest) stays successful. This distinction
        // is only correct when each namespace node looks up the status for its
        // own depth rather than for the full namespace of the class below it.
        $this->assertMatchesRegularExpression(
            '/<summary><span class="marker"><\/span><span class="dot errored"><\/span><span class="seg">Mid<\/span>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<summary><span class="marker"><\/span><span class="dot successful"><\/span><span class="seg">Leaf<\/span>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<summary><span class="marker"><\/span><span class="dot successful"><\/span><span class="seg">Sibling<\/span>/',
            $html,
        );
    }

    public function testCollapsesTheNamespacePrefixSharedByEveryClassInTheSidebar(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\Pkg\AlphaTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0),
                new TestMethodResult('Vendor\Pkg\BetaTest', 'b', 'b', TestStatus::Successful, '', null, [], 0.0),
            ],
        );

        $sidebar = $this->sidebarMarkup((new HtmlTestReport)->render($run));

        $this->assertStringNotContainsString('class="seg">Vendor</span>', $sidebar);
        $this->assertStringNotContainsString('class="seg">Pkg</span>', $sidebar);
        $this->assertStringContainsString('class="seg">AlphaTest</span>', $sidebar);
        $this->assertStringContainsString('class="seg">BetaTest</span>', $sidebar);
    }

    /**
     * @param array{0: TestStatus, 1: TestStatus} $statuses
     */
    #[DataProvider('worstStatusPairs')]
    public function testPicksTheWorseStatusBetweenTwoTestsInAClass(array $statuses, TestStatus $expected): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 'a', 'a', $statuses[0], '', null, [], 0.0),
                new TestMethodResult('Vendor\ExampleTest', 'b', 'b', $statuses[1], '', null, [], 0.0),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertMatchesRegularExpression(
            '/<span class="class-name">Vendor\\\\ExampleTest<\/span><span class="pill ' . strtolower($expected->value) . '">' . $expected->value . '<\/span>/',
            $html,
        );
    }

    public function testRendersTestDoxNamesWhenEnabled(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 'test_ok', 'test_ok', TestStatus::Successful, '', null, [], 0.001, 'Example behavior', 'Succeeds'),
                new TestMethodResult('Vendor\ExampleTest', 'test_fail', 'test_fail', TestStatus::Failed, 'reason', null, [], 0.002, 'Example behavior', 'Fails loudly'),
            ],
        );

        $html = (new HtmlTestReport)->render($run, true);

        $this->assertStringContainsString('Example behavior', $html);
        $this->assertStringContainsString('Succeeds', $html);
        $this->assertStringContainsString('Fails loudly', $html);
        $this->assertStringNotContainsString('Vendor\ExampleTest', $html);
        $this->assertStringNotContainsString('test_ok', $html);
        $this->assertStringNotContainsString('test_fail', $html);
    }

    public function testFallsBackToOriginalNamesWhenTestDoxIsEnabledButMissing(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\ExampleTest', 'test_ok', 'test_ok', TestStatus::Successful, '', null, [], 0.001),
            ],
        );

        $html = (new HtmlTestReport)->render($run, true);

        $this->assertStringContainsString('Vendor\ExampleTest', $html);
        $this->assertStringContainsString('test_ok', $html);
    }

    public function testRendersAFlatSidebarTreeWithEveryClassWhenTestDoxIsEnabled(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\Pkg\AlphaTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.0, 'Alpha behavior', 'a'),
                new TestMethodResult('Vendor\Pkg\BetaTest', 'b', 'b', TestStatus::Failed, '', null, [], 0.0, 'Beta behavior', 'b'),
            ],
        );

        $sidebar = $this->sidebarMarkup((new HtmlTestReport)->render($run, true));

        $this->assertStringNotContainsString('class="seg">Vendor</span>', $sidebar);
        $this->assertStringNotContainsString('class="seg">Pkg</span>', $sidebar);
        $this->assertStringContainsString('Alpha behavior', $sidebar);
        $this->assertStringContainsString('Beta behavior', $sidebar);
    }

    public function testGroupsPhptTestsByTheirDirectoryRatherThanPerFile(): void
    {
        $run = $this->createTestRun(
            [
                new PhptResult('/path/to/tests/end-to-end/testdox/diff.phpt', TestStatus::Successful, '', null, [], 0.001),
                new PhptResult('/path/to/tests/end-to-end/testdox/diff-colorized.phpt', TestStatus::Failed, 'nope', null, [], 0.002),
                new PhptResult('/path/to/tests/end-to-end/generic/output.phpt', TestStatus::Successful, '', null, [], 0.003),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertSame(2, substr_count($html, '<details class="class"'));
        $this->assertStringContainsString('<span class="class-name">/path/to/tests/end-to-end/testdox</span>', $html);
        $this->assertStringContainsString('<span class="class-name">/path/to/tests/end-to-end/generic</span>', $html);
        $this->assertStringContainsString('<span class="method">diff.phpt</span>', $html);
        $this->assertStringContainsString('<span class="method">diff-colorized.phpt</span>', $html);
        $this->assertStringContainsString('<span class="method">output.phpt</span>', $html);
        $this->assertStringNotContainsString('<span class="method">/path/to/tests/end-to-end/testdox/diff.phpt</span>', $html);
    }

    public function testGroupsTestsByTestSuiteWhenMoreThanOneSuiteIsPresent(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\UnitTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.001, null, null, 'unit'),
                new TestMethodResult('Vendor\E2ETest', 'b', 'b', TestStatus::Failed, 'nope', null, [], 0.002, null, null, 'end-to-end'),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringContainsString('<section class="suite" id="suite-1">', $html);
        $this->assertStringContainsString('<section class="suite" id="suite-2">', $html);
        $this->assertStringContainsString('<span class="suite-label">unit</span>', $html);
        $this->assertStringContainsString('<span class="suite-label">end-to-end</span>', $html);
        $this->assertStringContainsString('<span class="suite-label">end-to-end</span><span class="pill failed">FAILED</span>', $html);

        $sidebar = $this->sidebarMarkup($html);

        $this->assertStringContainsString('<li class="ns suite">', $sidebar);
        $this->assertStringContainsString('class="suite-link"', $sidebar);

        $unit = strpos($html, 'suite-label">unit');
        $e2e  = strpos($html, 'suite-label">end-to-end');

        $this->assertNotFalse($unit);
        $this->assertNotFalse($e2e);
        $this->assertLessThan($e2e, $unit);
    }

    public function testDoesNotRenderASuiteTierWhenOnlyOneSuiteIsPresent(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult('Vendor\UnitTest', 'a', 'a', TestStatus::Successful, '', null, [], 0.001, null, null, 'unit'),
                new TestMethodResult('Vendor\UnitTest', 'b', 'b', TestStatus::Successful, '', null, [], 0.001, null, null, 'unit'),
            ],
        );

        $html = (new HtmlTestReport)->render($run);

        $this->assertStringNotContainsString('<section class="suite"', $html);
        $this->assertStringNotContainsString('<li class="ns suite"', $html);
        $this->assertStringNotContainsString('class="suite-link"', $html);
        $this->assertStringContainsString('Vendor\UnitTest', $html);
    }

    public function testEscapesUserSuppliedStrings(): void
    {
        $run = $this->createTestRun(
            [
                new TestMethodResult(
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

    private function sidebarMarkup(string $html): string
    {
        $start = strpos($html, '<aside class="sidebar">');
        $end   = strpos($html, '</aside>', $start === false ? 0 : $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }
}
