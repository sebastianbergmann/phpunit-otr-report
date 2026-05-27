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
use function array_key_last;
use function array_map;
use function array_slice;
use function arsort;
use function basename;
use function count;
use function htmlspecialchars;
use function sprintf;
use SebastianBergmann\Template\Template;

final class HtmlReport
{
    public function render(TestRunCollection $runs): string
    {
        $runsArray = $runs->asArray();

        if ($runsArray === []) {
            return '';
        }

        $nRuns      = count($runsArray);
        $latest     = $runsArray[array_key_last($runsArray)];
        $first      = $runsArray[0];
        $runLabels  = array_map(static fn (TestRun $run): string => $run->startedAt()->format('Y-m-d H:i'), $runsArray);
        $totalTimes = array_map(static fn (TestRun $run): float => $run->totalTime(), $runsArray);
        $testCounts = array_map(static fn (TestRun $run): float => $run->testCount(), $runsArray);

        $latestTests = $latest->tests();

        arsort($latestTests);

        $slowest = array_slice($latestTests, 0, 10, true);

        $chart = new Chart;

        $template = new Template(__DIR__ . '/html/report.html');

        $template->setVar(
            [
                'runs'            => (string) $nRuns,
                'firstRun'        => $this->escape($first->startedAt()->format('Y-m-d')),
                'latestRun'       => $this->escape($latest->startedAt()->format('Y-m-d')),
                'latestTestCount' => (string) $latest->testCount(),
                'latestTotalTime' => (new NumberFormatter)->format($latest->totalTime()),
                'totalTimeChart'  => $chart->render($totalTimes, $runLabels, 1040, 300, ' s'),
                'testCountChart'  => $chart->render($testCounts, $runLabels, 1040, 240),
                'slowestRows'     => $this->slowestRows($slowest, $runsArray, $nRuns),
                'runRows'         => $this->runRows($runsArray),
            ],
        );

        return $template->render();
    }

    /**
     * @param array<string, float> $slowest
     * @param list<TestRun>        $runs
     */
    private function slowestRows(array $slowest, array $runs, int $nRuns): string
    {
        $sparkline = new Sparkline;
        $template  = new Template(__DIR__ . '/html/slowest_row.html');
        $rows      = '';

        foreach ($slowest as $name => $time) {
            $values = array_map(static fn (TestRun $run): ?float => $run->tests()[$name] ?? null, $runs);

            $template->setVar(
                [
                    'test'      => $this->escape($name),
                    'time'      => sprintf('%.6f', $time),
                    'delta'     => $this->delta($values, $time, $nRuns),
                    'sparkline' => $sparkline->render($values, 220, 28),
                ],
            );

            $rows .= $template->render();
        }

        return $rows;
    }

    /**
     * @param list<TestRun> $runs
     */
    private function runRows(array $runs): string
    {
        $template = new Template(__DIR__ . '/html/run_row.html');
        $rows     = '';

        foreach ($runs as $run) {
            $template->setVar(
                [
                    'started'   => $this->escape($run->startedAt()->format('Y-m-d H:i:s')),
                    'totalTime' => sprintf('%.3f', $run->totalTime()),
                    'testCount' => (string) $run->testCount(),
                    'file'      => $this->escape(basename($run->file())),
                ],
            );

            $rows .= $template->render();
        }

        return $rows;
    }

    /**
     * @param list<null|float> $values
     */
    private function delta(array $values, float $time, int $nRuns): string
    {
        $start = null;

        foreach ($values as $value) {
            if ($value !== null) {
                $start = $value;

                break;
            }
        }

        if ($start === null || $start <= 0 || $nRuns <= 1) {
            return '';
        }

        $percent = ($time - $start) / $start * 100;

        $template = new Template(__DIR__ . '/html/delta.html');

        $template->setVar(
            [
                'color'   => $percent >= 10 ? '#c53030' : ($percent <= -10 ? '#2f855a' : '#718096'),
                'sign'    => $percent >= 0 ? '+' : '',
                'percent' => sprintf('%.0f', $percent),
            ],
        );

        return $template->render();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}
