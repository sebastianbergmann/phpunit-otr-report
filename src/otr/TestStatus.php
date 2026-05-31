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

enum TestStatus: string
{
    public function isPassing(): bool
    {
        return $this === self::Successful;
    }

    public function isFailingOrErrored(): bool
    {
        return $this === self::Failed || $this === self::Errored;
    }
    case Successful = 'SUCCESSFUL';
    case Failed     = 'FAILED';
    case Errored    = 'ERRORED';
    case Aborted    = 'ABORTED';
    case Skipped    = 'SKIPPED';
}
