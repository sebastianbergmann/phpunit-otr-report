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
    private bool $help;
    private bool $version;

    public function __construct(?string $command, bool $help, bool $version)
    {
        $this->command = $command;
        $this->help    = $help;
        $this->version = $version;
    }

    public function command(): ?string
    {
        return $this->command;
    }

    public function help(): bool
    {
        return $this->help;
    }

    public function version(): bool
    {
        return $this->version;
    }
}
