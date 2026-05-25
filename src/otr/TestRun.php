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
    private array $tests;

    /**
     * @param array<string, float> $tests
     */
    public function __construct(string $file, DateTimeImmutable $startedAt, float $totalTime, array $tests)
    {
        $this->file      = $file;
        $this->startedAt = $startedAt;
        $this->totalTime = $totalTime;
        $this->tests     = $tests;
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
        return $this->tests;
    }

    public function testCount(): int
    {
        return count($this->tests);
    }

    public function isEmpty(): bool
    {
        return $this->tests === [];
    }
}
