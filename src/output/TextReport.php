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
    public function render(TestRun $run, bool $aboveMean): string
    {
        $runtimes = $run->tests();

        if ($runtimes === []) {
            return '';
        }

        arsort($runtimes);

        if ($aboveMean) {
            return $this->renderWithMean($runtimes);
        }

        return $this->renderPlain($runtimes);
    }

    /**
     * @param non-empty-array<string, float> $runtimes
     */
    private function renderWithMean(array $runtimes): string
    {
        $mean    = array_sum($runtimes) / count($runtimes);
        $slower  = array_filter($runtimes, static fn (float $time): bool => $time > $mean);
        $slowest = array_slice($slower, 0, 10, true);

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
     */
    private function renderPlain(array $runtimes): string
    {
        $slowest = array_slice($runtimes, 0, 10, true);

        $output = sprintf("%-8s  %s\n", 'Time(s)', 'Test');
        $output .= sprintf("%-8s  %s\n", '-------', '----');

        foreach ($slowest as $test => $time) {
            $output .= sprintf("%8.6f  %s\n", $time, $test);
        }

        return $output;
    }
}
