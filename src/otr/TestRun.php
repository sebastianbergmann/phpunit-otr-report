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

use function count;
use DateTimeImmutable;

final readonly class TestRun
{
    private string $file;
    private DateTimeImmutable $startedAt;
    private float $totalTime;

    /**
     * @var array<non-empty-string, float>
     */
    private array $times;

    /**
     * @var array<non-empty-string, float>
     */
    private array $cpuTimes;

    /**
     * @var array<non-empty-string, float>
     */
    private array $peakMemory;

    /**
     * @var list<TestResult>
     */
    private array $results;

    /**
     * @param array<non-empty-string, float> $times
     * @param array<non-empty-string, float> $cpuTimes
     * @param array<non-empty-string, float> $peakMemory
     * @param list<TestResult>               $results
     */
    public function __construct(string $file, DateTimeImmutable $startedAt, float $totalTime, array $times, array $cpuTimes, array $peakMemory, array $results = [])
    {
        $this->file       = $file;
        $this->startedAt  = $startedAt;
        $this->totalTime  = $totalTime;
        $this->times      = $times;
        $this->cpuTimes   = $cpuTimes;
        $this->peakMemory = $peakMemory;
        $this->results    = $results;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function totalTime(): float
    {
        return $this->totalTime;
    }

    /**
     * @return array<non-empty-string, float>
     */
    public function tests(): array
    {
        return $this->times;
    }

    /**
     * @return array<non-empty-string, float>
     */
    public function valuesFor(Metric $metric): array
    {
        return match ($metric) {
            Metric::Time   => $this->times,
            Metric::Cpu    => $this->cpuTimes,
            Metric::Memory => $this->peakMemory,
        };
    }

    /**
     * @return non-negative-int
     */
    public function testCount(): int
    {
        return count($this->times);
    }

    public function isEmpty(): bool
    {
        return $this->times === [];
    }

    /**
     * @return list<TestResult>
     */
    public function results(): array
    {
        return $this->results;
    }

    /**
     * @return non-negative-int
     */
    public function resultCount(): int
    {
        return count($this->results);
    }

    public function hasResults(): bool
    {
        return $this->results !== [];
    }

    /**
     * @return array{SUCCESSFUL: non-negative-int, FAILED: non-negative-int, ERRORED: non-negative-int, ABORTED: non-negative-int, SKIPPED: non-negative-int}
     */
    public function statusCounts(): array
    {
        $counts = [
            'SUCCESSFUL' => 0,
            'FAILED'     => 0,
            'ERRORED'    => 0,
            'ABORTED'    => 0,
            'SKIPPED'    => 0,
        ];

        foreach ($this->results as $result) {
            $counts[$result->status()->value]++;
        }

        return $counts;
    }

    /**
     * @return non-negative-int
     */
    public function countOf(TestStatus $status): int
    {
        return $this->statusCounts()[$status->value];
    }

    /**
     * @return non-negative-int
     */
    public function issueCount(): int
    {
        $count = 0;

        foreach ($this->results as $result) {
            $count += count($result->issues());
        }

        return $count;
    }
}
