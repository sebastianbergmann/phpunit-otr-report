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

use function sprintf;

final class NumberFormatter
{
    public function format(float $value): string
    {
        if ($value >= 100) {
            return sprintf('%.0f', $value);
        }

        if ($value >= 1) {
            return sprintf('%.2f', $value);
        }

        if ($value >= 0.01) {
            return sprintf('%.3f', $value);
        }

        return sprintf('%.4f', $value);
    }
}
