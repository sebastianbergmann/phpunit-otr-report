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

abstract readonly class TestResult
{
    private TestStatus $status;
    private string $reason;
    private ?Throwable $throwable;

    /**
     * @var list<Issue>
     */
    private array $issues;
    private ?float $time;

    /**
     * @var ?non-empty-string
     */
    private ?string $suite;

    /**
     * @param list<Issue>       $issues
     * @param ?non-empty-string $suite
     */
    public function __construct(TestStatus $status, string $reason, ?Throwable $throwable, array $issues, ?float $time, ?string $suite = null)
    {
        $this->status    = $status;
        $this->reason    = $reason;
        $this->throwable = $throwable;
        $this->issues    = $issues;
        $this->time      = $time;
        $this->suite     = $suite;
    }

    final public function status(): TestStatus
    {
        return $this->status;
    }

    final public function reason(): string
    {
        return $this->reason;
    }

    final public function hasReason(): bool
    {
        return $this->reason !== '';
    }

    final public function throwable(): ?Throwable
    {
        return $this->throwable;
    }

    /**
     * @return list<Issue>
     */
    final public function issues(): array
    {
        return $this->issues;
    }

    final public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    final public function time(): ?float
    {
        return $this->time;
    }

    /**
     * @return ?non-empty-string
     */
    final public function suite(): ?string
    {
        return $this->suite;
    }

    final public function hasSuite(): bool
    {
        return $this->suite !== null;
    }

    /**
     * @phpstan-assert-if-true TestMethodResult $this
     */
    public function isTestMethod(): bool
    {
        return false;
    }

    /**
     * @phpstan-assert-if-true PhptResult $this
     */
    public function isPhpt(): bool
    {
        return false;
    }
}
