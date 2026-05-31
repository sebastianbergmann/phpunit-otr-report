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

final readonly class Arguments
{
    /**
     * @var null|non-empty-string
     */
    private ?string $command;

    /**
     * @var list<string>
     */
    private array $arguments;
    private bool $help;
    private bool $aboveMean;

    /**
     * @var positive-int
     */
    private int $limit;
    private Metric $sort;
    private bool $testdox;
    private bool $version;

    /**
     * @param null|non-empty-string $command
     * @param list<string>          $arguments
     * @param positive-int          $limit
     */
    public function __construct(?string $command, array $arguments, bool $help, bool $aboveMean, int $limit, Metric $sort, bool $testdox, bool $version)
    {
        $this->command   = $command;
        $this->arguments = $arguments;
        $this->help      = $help;
        $this->aboveMean = $aboveMean;
        $this->limit     = $limit;
        $this->sort      = $sort;
        $this->testdox   = $testdox;
        $this->version   = $version;
    }

    /**
     * @return null|non-empty-string
     */
    public function command(): ?string
    {
        return $this->command;
    }

    /**
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    public function help(): bool
    {
        return $this->help;
    }

    public function aboveMean(): bool
    {
        return $this->aboveMean;
    }

    /**
     * @return positive-int
     */
    public function limit(): int
    {
        return $this->limit;
    }

    public function sort(): Metric
    {
        return $this->sort;
    }

    public function testdox(): bool
    {
        return $this->testdox;
    }

    public function version(): bool
    {
        return $this->version;
    }
}
