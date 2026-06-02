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

final readonly class TextReport
{
    /**
     * @param positive-int $limit
     */
    public function render(TestRun $run, bool $aboveMean, int $limit, Metric $metric): string
    {
        $values = $run->valuesFor($metric);

        if ($values === []) {
            return '';
        }

        arsort($values);

        if ($aboveMean) {
            return $this->renderWithMean($values, $limit, $metric);
        }

        return $this->renderPlain($values, $limit, $metric);
    }

    /**
     * @param non-empty-array<non-empty-string, float> $values
     * @param positive-int                             $limit
     */
    private function renderWithMean(array $values, int $limit, Metric $metric): string
    {
        $mean    = array_sum($values) / count($values);
        $beyond  = array_filter($values, static fn (float $value): bool => $value > $mean);
        $slowest = array_slice($beyond, 0, $limit, true);

        $output = $this->meanSummary($metric, $mean, count($values), count($beyond));

        $output .= sprintf("%8s  %8s  %s\n", $this->header($metric), 'x mean', 'Test');
        $output .= sprintf("%8s  %8s  %s\n", '-------', '------', '----');

        foreach ($slowest as $test => $value) {
            $output .= sprintf("%s  %7.2fx  %s\n", $this->formatValue($metric, $value), $value / $mean, $test);
        }

        return $output;
    }

    /**
     * @param non-empty-array<non-empty-string, float> $values
     * @param positive-int                             $limit
     */
    private function renderPlain(array $values, int $limit, Metric $metric): string
    {
        $slowest = array_slice($values, 0, $limit, true);

        $output = sprintf("%8s  %s\n", $this->header($metric), 'Test');
        $output .= sprintf("%8s  %s\n", '-------', '----');

        foreach ($slowest as $test => $value) {
            $output .= sprintf("%s  %s\n", $this->formatValue($metric, $value), $test);
        }

        return $output;
    }

    private function meanSummary(Metric $metric, float $mean, int $total, int $beyond): string
    {
        $description = match ($metric) {
            Metric::Time   => 'runtime',
            Metric::Cpu    => 'CPU time',
            Metric::Memory => 'peak memory',
        };

        $comparison = match ($metric) {
            Metric::Time, Metric::Cpu => 'slower than mean',
            Metric::Memory            => 'larger than mean',
        };

        return sprintf(
            "Mean test %s: %s %s (%d tests, %d %s)\n\n",
            $description,
            $this->formatMean($metric, $mean),
            $metric === Metric::Memory ? 'bytes' : 's',
            $total,
            $beyond,
            $comparison,
        );
    }

    private function header(Metric $metric): string
    {
        return match ($metric) {
            Metric::Time   => 'Time(s)',
            Metric::Cpu    => 'CPU(s)',
            Metric::Memory => 'Memory',
        };
    }

    private function formatValue(Metric $metric, float $value): string
    {
        return match ($metric) {
            Metric::Time, Metric::Cpu => sprintf('%8.6f', $value),
            Metric::Memory            => sprintf('%8.0f', $value),
        };
    }

    private function formatMean(Metric $metric, float $value): string
    {
        return match ($metric) {
            Metric::Time, Metric::Cpu => sprintf('%.6f', $value),
            Metric::Memory            => sprintf('%.0f', $value),
        };
    }
}
