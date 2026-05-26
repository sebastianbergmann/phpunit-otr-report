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

use function array_filter;
use function array_slice;
use function array_sum;
use function arsort;
use function count;
use function sprintf;

final class TextReport
{
    /**
     * @param positive-int $limit
     */
    public function render(TestRun $run, bool $aboveMean, int $limit): string
    {
        $runtimes = $run->tests();

        if ($runtimes === []) {
            return '';
        }

        arsort($runtimes);

        if ($aboveMean) {
            return $this->renderWithMean($runtimes, $limit);
        }

        return $this->renderPlain($runtimes, $limit);
    }

    /**
     * @param non-empty-array<string, float> $runtimes
     * @param positive-int                   $limit
     */
    private function renderWithMean(array $runtimes, int $limit): string
    {
        $mean    = array_sum($runtimes) / count($runtimes);
        $slower  = array_filter($runtimes, static fn (float $time): bool => $time > $mean);
        $slowest = array_slice($slower, 0, $limit, true);

        $output = sprintf(
            "Mean test runtime: %.6f s (%d tests, %d slower than mean)\n\n",
            $mean,
            count($runtimes),
            count($slower),
        );

        $output .= sprintf("%-8s  %-8s  %s\n", 'Time(s)', 'x mean', 'Test');
        $output .= sprintf("%-8s  %-8s  %s\n", '-------', '------', '----');

        foreach ($slowest as $test => $time) {
            $output .= sprintf("%8.6f  %7.2fx  %s\n", $time, $time / $mean, $test);
        }

        return $output;
    }

    /**
     * @param non-empty-array<string, float> $runtimes
     * @param positive-int                   $limit
     */
    private function renderPlain(array $runtimes, int $limit): string
    {
        $slowest = array_slice($runtimes, 0, $limit, true);

        $output = sprintf("%-8s  %s\n", 'Time(s)', 'Test');
        $output .= sprintf("%-8s  %s\n", '-------', '----');

        foreach ($slowest as $test => $time) {
            $output .= sprintf("%8.6f  %s\n", $time, $test);
        }

        return $output;
    }
}
