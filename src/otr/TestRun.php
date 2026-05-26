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

final class TestRun
{
    private string $file;
    private DateTimeImmutable $startedAt;
    private float $totalTime;

    /**
     * @var array<string, float>
     */
    private array $times;

    /**
     * @var array<string, float>
     */
    private array $cpuTimes;

    /**
     * @var array<string, float>
     */
    private array $peakMemory;

    /**
     * @param array<string, float> $times
     * @param array<string, float> $cpuTimes
     * @param array<string, float> $peakMemory
     */
    public function __construct(string $file, DateTimeImmutable $startedAt, float $totalTime, array $times, array $cpuTimes, array $peakMemory)
    {
        $this->file       = $file;
        $this->startedAt  = $startedAt;
        $this->totalTime  = $totalTime;
        $this->times      = $times;
        $this->cpuTimes   = $cpuTimes;
        $this->peakMemory = $peakMemory;
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
     * @return array<string, float>
     */
    public function tests(): array
    {
        return $this->times;
    }

    /**
     * @return array<string, float>
     */
    public function valuesFor(Metric $metric): array
    {
        return match ($metric) {
            Metric::Time   => $this->times,
            Metric::Cpu    => $this->cpuTimes,
            Metric::Memory => $this->peakMemory,
        };
    }

    public function testCount(): int
    {
        return count($this->times);
    }

    public function isEmpty(): bool
    {
        return $this->times === [];
    }
}
