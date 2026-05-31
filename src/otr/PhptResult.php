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

final readonly class PhptResult extends TestResult
{
    /**
     * @var non-empty-string
     */
    private string $path;

    /**
     * @param non-empty-string $path
     * @param list<Issue>      $issues
     */
    public function __construct(string $path, TestStatus $status, string $reason, ?Throwable $throwable, array $issues, ?float $time)
    {
        $this->path = $path;

        parent::__construct($status, $reason, $throwable, $issues, $time);
    }

    /**
     * @return non-empty-string
     */
    public function path(): string
    {
        return $this->path;
    }

    public function isPhpt(): true
    {
        return true;
    }
}
