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

final readonly class TestResult
{
    /**
     * @var non-empty-string
     */
    private string $className;

    /**
     * @var non-empty-string
     */
    private string $methodName;

    /**
     * @var non-empty-string
     */
    private string $displayName;
    private TestStatus $status;
    private string $reason;
    private ?Throwable $throwable;

    /**
     * @var list<Issue>
     */
    private array $issues;
    private ?float $time;

    /**
     * @param non-empty-string $className
     * @param non-empty-string $methodName
     * @param non-empty-string $displayName
     * @param list<Issue>      $issues
     */
    public function __construct(string $className, string $methodName, string $displayName, TestStatus $status, string $reason, ?Throwable $throwable, array $issues, ?float $time)
    {
        $this->className   = $className;
        $this->methodName  = $methodName;
        $this->displayName = $displayName;
        $this->status      = $status;
        $this->reason      = $reason;
        $this->throwable   = $throwable;
        $this->issues      = $issues;
        $this->time        = $time;
    }

    /**
     * @return non-empty-string
     */
    public function className(): string
    {
        return $this->className;
    }

    /**
     * @return non-empty-string
     */
    public function methodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return non-empty-string
     */
    public function displayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->className . '::' . $this->displayName;
    }

    public function status(): TestStatus
    {
        return $this->status;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function hasReason(): bool
    {
        return $this->reason !== '';
    }

    public function throwable(): ?Throwable
    {
        return $this->throwable;
    }

    /**
     * @return list<Issue>
     */
    public function issues(): array
    {
        return $this->issues;
    }

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }

    public function time(): ?float
    {
        return $this->time;
    }
}
