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

final readonly class TestMethodResult extends TestResult
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

    /**
     * @var ?non-empty-string
     */
    private ?string $prettifiedClassName;

    /**
     * @var ?non-empty-string
     */
    private ?string $prettifiedMethodName;

    /**
     * @param non-empty-string  $className
     * @param non-empty-string  $methodName
     * @param non-empty-string  $displayName
     * @param list<Issue>       $issues
     * @param ?non-empty-string $prettifiedClassName
     * @param ?non-empty-string $prettifiedMethodName
     */
    public function __construct(string $className, string $methodName, string $displayName, TestStatus $status, string $reason, ?Throwable $throwable, array $issues, ?float $time, ?string $prettifiedClassName = null, ?string $prettifiedMethodName = null)
    {
        $this->className            = $className;
        $this->methodName           = $methodName;
        $this->displayName          = $displayName;
        $this->prettifiedClassName  = $prettifiedClassName;
        $this->prettifiedMethodName = $prettifiedMethodName;

        parent::__construct($status, $reason, $throwable, $issues, $time);
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

    /**
     * @return ?non-empty-string
     */
    public function prettifiedClassName(): ?string
    {
        return $this->prettifiedClassName;
    }

    /**
     * @return ?non-empty-string
     */
    public function prettifiedMethodName(): ?string
    {
        return $this->prettifiedMethodName;
    }

    public function isTestMethod(): true
    {
        return true;
    }
}
