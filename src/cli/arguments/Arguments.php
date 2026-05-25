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

final class Arguments
{
    private ?string $command;

    /**
     * @var list<string>
     */
    private array $arguments;
    private bool $help;
    private bool $mean;
    private bool $version;

    /**
     * @param list<string> $arguments
     */
    public function __construct(?string $command, array $arguments, bool $help, bool $mean, bool $version)
    {
        $this->command   = $command;
        $this->arguments = $arguments;
        $this->help      = $help;
        $this->mean      = $mean;
        $this->version   = $version;
    }

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

    public function mean(): bool
    {
        return $this->mean;
    }

    public function version(): bool
    {
        return $this->version;
    }
}
