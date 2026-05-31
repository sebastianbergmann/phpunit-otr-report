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
use function sprintf;
use function strtolower;
use function usort;
use SebastianBergmann\Template\Template;

/**
 * @phpstan-type IndexedResult array{testId: non-empty-string, methodDisplayName: non-empty-string, result: TestResult}
 * @phpstan-type IndexedClass array{className: non-empty-string, classDisplayName: non-empty-string, classId: non-empty-string, results: non-empty-list<IndexedResult>}
 */
final readonly class HtmlTestReport
{
    public function render(TestRun $run, bool $testdox = false): string
    {
        $classes = $this->index($run->results(), $testdox);

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
                'sidebar'    => $this->sidebar($classes, $testdox),
                'classes'    => $this->classes($classes),
            ],
        );

        return $template->render();
    }

    /**
     * @param list<TestResult> $results
     *
     * @return list<IndexedClass>
     */
    private function index(array $results, bool $testdox): array
    {
        /** @var array<non-empty-string, IndexedClass> $byClass */
        $byClass = [];

        $classCounter = 0;
        $testCounter  = 0;

        foreach ($results as $result) {
            $testCounter++;

            $groupKey = $this->groupKey($result);

            $indexedResult = [
                'testId'            => 'test-' . $testCounter,
                'methodDisplayName' => $this->methodDisplayName($result, $testdox),
                'result'            => $result,
            ];

            if (!isset($byClass[$groupKey])) {
                $classCounter++;
                $byClass[$groupKey] = [
                    'className'        => $groupKey,
                    'classDisplayName' => $this->classDisplayName($result, $testdox),
                    'classId'          => 'class-' . $classCounter,
                    'results'          => [$indexedResult],
                ];

                continue;
            }

            $byClass[$groupKey]['results'][] = $indexedResult;
        }

        return array_values($byClass);
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
     * @param list<IndexedClass> $classes
     */
    private function sidebar(array $classes, bool $testdox): string
    {
        if ($classes === []) {
            return '';
        }

        $sorted = $classes;

        usort(
            $sorted,
            static fn (array $a, array $b): int => $a['classDisplayName'] <=> $b['classDisplayName'],
        );

        if ($testdox) {
            $output = '<ul class="tree tree-root">';

            foreach ($sorted as $class) {
                $output .= $this->renderClassNode($class['classDisplayName'], $class);
            }

            $output .= '</ul>';

            return $output;
        }

        $namespaceStatuses = $this->namespaceStatuses($sorted);

        $output          = '<ul class="tree tree-root">';
        $currentSegments = [];

        foreach ($sorted as $class) {
            $segments  = explode('\\', $class['className']);
            $shortName = array_pop($segments);

            assert($shortName !== null && $shortName !== '');

            $common = $this->commonPrefixLength($currentSegments, $segments);

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

        for ($i = count($currentSegments) - 1; $i >= 0; $i--) {
            $output .= '</ul></details></li>';
        }

        $output .= '</ul>';

        return $output;
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
