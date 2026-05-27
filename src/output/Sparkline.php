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

use function array_key_last;
use function count;
use function max;
use function sprintf;

final readonly class Sparkline
{
    /**
     * @param list<null|float> $values
     * @param positive-int     $width
     * @param positive-int     $height
     * @param non-negative-int $padding
     *
     * @return non-empty-string
     */
    public function render(array $values, int $width = 200, int $height = 30, int $padding = 3): string
    {
        $valid = new DataSeries($values)->withoutGaps();

        if ($valid === []) {
            return sprintf('<svg width="%d" height="%d"></svg>', $width, $height);
        }

        $maxY       = max($valid);
        $count      = count($values);
        $plotWidth  = $width - 2 * $padding;
        $plotHeight = $height - 2 * $padding;

        $path      = '';
        $needsMove = true;

        foreach ($values as $index => $value) {
            if ($value === null) {
                $needsMove = true;

                continue;
            }

            $x = $padding + ($count <= 1 ? $plotWidth / 2 : $index * $plotWidth / ($count - 1));
            $y = $padding + $plotHeight - ($value / max($maxY, 1e-12)) * $plotHeight;
            $path .= sprintf('%s%.1f,%.1f', $needsMove ? 'M' : ' L', $x, $y);
            $needsMove = false;
        }

        $lastKey = array_key_last($valid);
        $last    = $valid[$lastKey];
        $lastX   = $padding + ($count <= 1 ? $plotWidth / 2 : $lastKey * $plotWidth / ($count - 1));
        $lastY   = $padding + $plotHeight - ($last / max($maxY, 1e-12)) * $plotHeight;

        return sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 %d %d"><path d="%s" fill="none" stroke="#2b6cb0" stroke-width="1.2"/><circle cx="%.1f" cy="%.1f" r="1.8" fill="#2b6cb0"/></svg>',
            $width,
            $height,
            $width,
            $height,
            $path,
            $lastX,
            $lastY,
        );
    }
}
