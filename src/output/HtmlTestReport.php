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

use const ENT_QUOTES;
use function array_map;
use function array_pop;
use function array_slice;
use function array_values;
use function assert;
use function basename;
use function count;
use function dirname;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function max;
use function min;
use function sprintf;
use function strtolower;
use function usort;
use SebastianBergmann\Template\Template;

/**
 * @phpstan-type IndexedResult array{testId: non-empty-string, methodDisplayName: non-empty-string, result: TestResult}
 * @phpstan-type IndexedClass array{className: non-empty-string, classDisplayName: non-empty-string, classId: non-empty-string, results: non-empty-list<IndexedResult>}
 * @phpstan-type IndexedSuite array{suiteName: ?non-empty-string, suiteId: non-empty-string, classes: non-empty-list<IndexedClass>}
 */
final readonly class HtmlTestReport
{
    public function render(TestRun $run, bool $testdox = false): string
    {
        $suites = $this->index($run->results(), $testdox);

        $grouped = count($suites) > 1;

        $template = new Template(__DIR__ . '/html/test_report.html');

        $template->setVar(
            [
                'file'       => $this->escape($run->file()),
                'startedAt'  => $this->escape($run->startedAt()->format('Y-m-d H:i:s')),
                'totalTime'  => sprintf('%.6f', $run->totalTime()),
                'total'      => (string) $run->resultCount(),
                'successful' => (string) $run->countOf(TestStatus::Successful),
                'failed'     => (string) $run->countOf(TestStatus::Failed),
                'errored'    => (string) $run->countOf(TestStatus::Errored),
                'aborted'    => (string) $run->countOf(TestStatus::Aborted),
                'skipped'    => (string) $run->countOf(TestStatus::Skipped),
                'issues'     => (string) $run->issueCount(),
                'sidebar'    => $this->sidebar($suites, $testdox, $grouped),
                'classes'    => $this->body($suites, $grouped),
            ],
        );

        return $template->render();
    }

    /**
     * @param list<TestResult> $results
     *
     * @return list<IndexedSuite>
     */
    private function index(array $results, bool $testdox): array
    {
        /** @var array<string, array{suiteName: ?non-empty-string, suiteId: non-empty-string}> $suiteMeta */
        $suiteMeta = [];

        /** @var array<string, array<non-empty-string, IndexedClass>> $suiteClasses */
        $suiteClasses = [];

        $suiteCounter = 0;
        $classCounter = 0;
        $testCounter  = 0;

        foreach ($results as $result) {
            $testCounter++;

            $suiteName = $result->suite();
            $suiteKey  = $suiteName ?? '';
            $groupKey  = $this->groupKey($result);

            if (!isset($suiteMeta[$suiteKey])) {
                $suiteCounter++;
                $suiteMeta[$suiteKey] = [
                    'suiteName' => $suiteName,
                    'suiteId'   => 'suite-' . $suiteCounter,
                ];
                $suiteClasses[$suiteKey] = [];
            }

            $indexedResult = [
                'testId'            => 'test-' . $testCounter,
                'methodDisplayName' => $this->methodDisplayName($result, $testdox),
                'result'            => $result,
            ];

            if (!isset($suiteClasses[$suiteKey][$groupKey])) {
                $classCounter++;
                $suiteClasses[$suiteKey][$groupKey] = [
                    'className'        => $groupKey,
                    'classDisplayName' => $this->classDisplayName($result, $testdox),
                    'classId'          => 'class-' . $classCounter,
                    'results'          => [$indexedResult],
                ];

                continue;
            }

            $suiteClasses[$suiteKey][$groupKey]['results'][] = $indexedResult;
        }

        $suites = [];

        foreach ($suiteMeta as $suiteKey => $meta) {
            $classes = $suiteClasses[$suiteKey] ?? [];

            assert($classes !== []);

            $suites[] = [
                'suiteName' => $meta['suiteName'],
                'suiteId'   => $meta['suiteId'],
                'classes'   => array_values($classes),
            ];
        }

        return $suites;
    }

    /**
     * @param list<IndexedSuite> $suites
     */
    private function body(array $suites, bool $grouped): string
    {
        if ($suites === []) {
            return '';
        }

        if (!$grouped) {
            return $this->classes($suites[0]['classes']);
        }

        $template = new Template(__DIR__ . '/html/test_suite.html');
        $output   = '';

        foreach ($suites as $suite) {
            $worst = $this->worstStatusForSuite($suite);

            $template->setVar(
                [
                    'suiteId'          => $suite['suiteId'],
                    'suiteName'        => $this->escape($this->suiteDisplayName($suite)),
                    'suiteStatusClass' => $this->statusClass($worst),
                    'suiteStatusLabel' => $worst->value,
                    'classes'          => $this->classes($suite['classes']),
                ],
            );

            $output .= $template->render();
        }

        return $output;
    }

    /**
     * @param list<IndexedClass> $classes
     */
    private function classes(array $classes): string
    {
        $template = new Template(__DIR__ . '/html/test_class.html');
        $output   = '';

        foreach ($classes as $class) {
            $worst = $this->worstStatusForClass($class);

            $template->setVar(
                [
                    'classId'          => $class['classId'],
                    'className'        => $this->escape($class['classDisplayName']),
                    'classStatusClass' => $this->statusClass($worst),
                    'classStatusLabel' => $worst->value,
                    'classTime'        => sprintf('%.6f', $this->totalTimeForClass($class)),
                    'results'          => $this->results($class['results']),
                ],
            );

            $output .= $template->render();
        }

        return $output;
    }

    /**
     * @param non-empty-list<IndexedResult> $results
     */
    private function results(array $results): string
    {
        $template = new Template(__DIR__ . '/html/test_result.html');
        $output   = '';

        foreach ($results as $entry) {
            $result     = $entry['result'];
            $hasDetails = $result->hasReason() || $result->throwable() !== null || $result->hasIssues();

            $template->setVar(
                [
                    'testId'       => $entry['testId'],
                    'detailsClass' => $hasDetails ? '' : 'no-details',
                    'open'         => !$result->status()->isPassing() && $hasDetails ? 'open' : '',
                    'statusClass'  => $this->statusClass($result->status()),
                    'statusLabel'  => $result->status()->value,
                    'methodName'   => $this->escape($entry['methodDisplayName']),
                    'issuePills'   => $this->issuePills($result->issues()),
                    'time'         => $result->time() === null ? '' : sprintf('%.6f s', $result->time()),
                    'body'         => $hasDetails ? $this->resultBody($result) : '',
                ],
            );

            $output .= $template->render();
        }

        return $output;
    }

    /**
     * @return non-empty-string
     */
    private function groupKey(TestResult $result): string
    {
        if ($result->isPhpt()) {
            /** @noinspection PhpParamsInspection */
            return $this->directory($result);
        }

        assert($result instanceof TestMethodResult);

        return $result->className();
    }

    /**
     * @return non-empty-string
     */
    private function classDisplayName(TestResult $result, bool $testdox): string
    {
        if ($result->isPhpt()) {
            /** @noinspection PhpParamsInspection */
            return $this->directory($result);
        }

        assert($result instanceof TestMethodResult);

        if ($testdox && ($prettified = $result->prettifiedClassName()) !== null) {
            return $prettified;
        }

        return $result->className();
    }

    /**
     * @return non-empty-string
     */
    private function methodDisplayName(TestResult $result, bool $testdox): string
    {
        if ($result->isPhpt()) {
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $name = basename($result->path());

            assert($name !== '');

            return $name;
        }

        assert($result instanceof TestMethodResult);

        if ($testdox && ($prettified = $result->prettifiedMethodName()) !== null) {
            return $prettified;
        }

        return $result->displayName();
    }

    /**
     * @return non-empty-string
     */
    private function directory(PhptResult $result): string
    {
        $directory = dirname($result->path());

        assert($directory !== '');

        return $directory;
    }

    private function resultBody(TestResult $result): string
    {
        $body = '<div class="details-body">';

        if ($result->hasReason()) {
            $body .= '<div class="label">Reason</div>';
            $body .= '<pre>' . $this->escape($result->reason()) . '</pre>';
        }

        if (($throwable = $result->throwable()) !== null) {
            $body .= '<div class="label">Throwable (' . $this->escape($throwable->type()) . ')</div>';
            $body .= '<pre>' . $this->escape($throwable->message()) . '</pre>';
        }

        if ($result->hasIssues()) {
            $body .= '<div class="label">Issues</div>';
            $body .= '<ul class="issues">';

            foreach ($result->issues() as $issue) {
                $body .= sprintf(
                    '<li><span class="pill issue">%s</span><span class="msg">%s</span></li>',
                    $this->escape($issue->type()),
                    $this->escape($issue->message()),
                );
            }

            $body .= '</ul>';
        }

        $body .= '</div>';

        return $body;
    }

    /**
     * @param list<Issue> $issues
     */
    private function issuePills(array $issues): string
    {
        $output = '';

        foreach ($issues as $issue) {
            $output .= sprintf(
                '<span class="pill issue">%s</span>',
                $this->escape($issue->type()),
            );
        }

        return $output;
    }

    /**
     * @param list<IndexedSuite> $suites
     */
    private function sidebar(array $suites, bool $testdox, bool $grouped): string
    {
        if ($suites === []) {
            return '';
        }

        if (!$grouped) {
            return sprintf(
                '<ul class="tree tree-root">%s</ul>',
                $this->treeItems($suites[0]['classes'], $testdox),
            );
        }

        return sprintf(
            '<ul class="tree tree-root">%s</ul>',
            implode('', array_map(
                fn (array $suite): string => $this->renderSuiteNode($suite, $testdox),
                $suites,
            )),
        );
    }

    /**
     * @param IndexedSuite $suite
     */
    private function renderSuiteNode(array $suite, bool $testdox): string
    {
        return sprintf(
            '<li class="ns suite"><details open><summary>%s<a href="#%s" class="suite-link"><span class="dot %s"></span><span class="seg">%s</span></a></summary><ul class="tree">%s</ul></details></li>',
            $this->treeMarker(),
            $this->escape($suite['suiteId']),
            $this->statusClass($this->worstStatusForSuite($suite)),
            $this->escape($this->suiteDisplayName($suite)),
            $this->treeItems($suite['classes'], $testdox),
        );
    }

    /**
     * @param non-empty-list<IndexedClass> $classes
     */
    private function treeItems(array $classes, bool $testdox): string
    {
        $sorted = $classes;

        usort(
            $sorted,
            static fn (array $a, array $b): int => $a['classDisplayName'] <=> $b['classDisplayName'],
        );

        if ($testdox) {
            $output = '';

            foreach ($sorted as $class) {
                $output .= $this->renderClassNode($class['classDisplayName'], $class);
            }

            return $output;
        }

        $namespaceStatuses = $this->namespaceStatuses($sorted);
        $strip             = $this->commonNamespacePrefixLength($sorted);

        $output          = '';
        $currentSegments = [];

        foreach ($sorted as $class) {
            $segments  = explode('\\', $class['className']);
            $shortName = array_pop($segments);

            assert($shortName !== null && $shortName !== '');

            $common = max($strip, $this->commonPrefixLength($currentSegments, $segments));

            for ($i = count($currentSegments) - 1; $i >= $common; $i--) {
                $output .= '</ul></details></li>';
            }

            for ($i = $common; $i < count($segments); $i++) {
                $prefix = implode('\\', array_slice($segments, 0, $i + 1));
                $status = $namespaceStatuses[$prefix] ?? TestStatus::Successful;

                $output .= sprintf(
                    '<li class="ns"><details open><summary>%s<span class="dot %s"></span><span class="seg">%s</span></summary><ul class="tree">',
                    $this->treeMarker(),
                    $this->statusClass($status),
                    $this->escape($segments[$i]),
                );
            }

            $output .= $this->renderClassNode($shortName, $class);

            $currentSegments = $segments;
        }

        for ($i = count($currentSegments) - 1; $i >= $strip; $i--) {
            $output .= '</ul></details></li>';
        }

        return $output;
    }

    /**
     * Returns the number of leading namespace segments shared by every class.
     *
     * The longest common prefix of the fully-qualified class names is used:
     * distinct classes always diverge at or before their final (class name)
     * segment, so that segment never enters the shared prefix and there is no
     * need to strip it first.
     *
     * @param non-empty-list<IndexedClass> $classes
     *
     * @return non-negative-int
     */
    private function commonNamespacePrefixLength(array $classes): int
    {
        $first  = explode('\\', $classes[0]['className']);
        $length = count($first);

        foreach ($classes as $class) {
            $length = min($length, $this->commonPrefixLength($first, explode('\\', $class['className'])));
        }

        return $length;
    }

    /**
     * @param list<IndexedClass> $classes
     *
     * @return array<string, TestStatus>
     */
    private function namespaceStatuses(array $classes): array
    {
        /** @var array<string, list<TestStatus>> $bucket */
        $bucket = [];

        foreach ($classes as $class) {
            $segments = explode('\\', $class['className']);
            $worst    = $this->worstStatusForClass($class);
            $prefix   = '';

            foreach ($segments as $segment) {
                $prefix            = $prefix === '' ? $segment : $prefix . '\\' . $segment;
                $bucket[$prefix][] = $worst;
            }
        }

        $statuses = [];

        foreach ($bucket as $prefix => $candidates) {
            $statuses[$prefix] = $this->worstStatus($candidates);
        }

        return $statuses;
    }

    /**
     * @param IndexedClass $class
     */
    private function renderClassNode(string $shortName, array $class): string
    {
        $worst = $this->worstStatusForClass($class);

        $methods = '';

        foreach ($class['results'] as $entry) {
            $methods .= sprintf(
                '<li class="m"><a href="#%s"><span class="dot %s"></span><span class="seg">%s</span></a></li>',
                $this->escape($entry['testId']),
                $this->statusClass($entry['result']->status()),
                $this->escape($entry['methodDisplayName']),
            );
        }

        return sprintf(
            '<li class="cls"><details open><summary>%s<a href="#%s" class="cls-link"><span class="dot %s"></span><span class="seg">%s</span></a></summary><ul class="tree">%s</ul></details></li>',
            $this->treeMarker(),
            $this->escape($class['classId']),
            $this->statusClass($worst),
            $this->escape($shortName),
            $methods,
        );
    }

    /**
     * @param list<string> $a
     * @param list<string> $b
     *
     * @return non-negative-int
     */
    private function commonPrefixLength(array $a, array $b): int
    {
        foreach ($a as $i => $segment) {
            if (!isset($b[$i]) || $b[$i] !== $segment) {
                return $i;
            }
        }

        return count($a);
    }

    /**
     * @param IndexedSuite $suite
     *
     * @return non-empty-string
     */
    private function suiteDisplayName(array $suite): string
    {
        return $suite['suiteName'] ?? 'Tests without a suite';
    }

    /**
     * @param IndexedSuite $suite
     */
    private function worstStatusForSuite(array $suite): TestStatus
    {
        return $this->worstStatus(array_map(
            fn (array $class): TestStatus => $this->worstStatusForClass($class),
            $suite['classes'],
        ));
    }

    /**
     * @param IndexedClass $class
     */
    private function worstStatusForClass(array $class): TestStatus
    {
        return $this->worstStatus(array_map(
            static fn (array $entry): TestStatus => $entry['result']->status(),
            $class['results'],
        ));
    }

    /**
     * @param list<TestStatus> $statuses
     */
    private function worstStatus(array $statuses): TestStatus
    {
        foreach ([TestStatus::Errored, TestStatus::Failed, TestStatus::Aborted, TestStatus::Skipped] as $severity) {
            if (in_array($severity, $statuses, true)) {
                return $severity;
            }
        }

        return TestStatus::Successful;
    }

    /**
     * @param IndexedClass $class
     */
    private function totalTimeForClass(array $class): float
    {
        $total = 0.0;

        foreach ($class['results'] as $entry) {
            $total += $entry['result']->time() ?? 0.0;
        }

        return $total;
    }

    private function statusClass(TestStatus $status): string
    {
        return strtolower($status->value);
    }

    private function treeMarker(): string
    {
        return '<span class="marker"></span>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}
