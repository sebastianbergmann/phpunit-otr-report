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

use const ENT_QUOTES;
use function count;
use function htmlspecialchars;
use function max;
use function sprintf;

final class Chart
{
    /**
     * @param list<null|float> $values
     * @param list<string>     $labels
     */
    public function render(array $values, array $labels, int $width = 1000, int $height = 300, string $unit = ''): string
    {
        $valid = new DataSeries($values)->withoutGaps();

        if ($valid === []) {
            return '';
        }

        $formatter = new NumberFormatter;

        $maxY          = max($valid) * 1.1;
        $count         = count($values);
        $paddingLeft   = 70;
        $paddingRight  = 20;
        $paddingTop    = 20;
        $paddingBottom = 50;
        $plotWidth     = $width - $paddingLeft - $paddingRight;
        $plotHeight    = $height - $paddingTop - $paddingBottom;

        $svg = sprintf('<svg width="%d" height="%d" viewBox="0 0 %d %d" class="chart">', $width, $height, $width, $height);

        for ($gridStep = 0; $gridStep <= 4; $gridStep++) {
            $gridY     = $paddingTop + $plotHeight - ($gridStep / 4) * $plotHeight;
            $gridValue = $maxY * $gridStep / 4;
            $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#e2e8f0"/>', $paddingLeft, $gridY, $width - $paddingRight, $gridY);
            $svg .= sprintf('<text x="%d" y="%.1f" font-size="11" text-anchor="end" fill="#718096">%s%s</text>', $paddingLeft - 8, $gridY + 4, $formatter->format($gridValue), $unit);
        }

        $path      = '';
        $needsMove = true;
        $markers   = '';

        foreach ($values as $index => $value) {
            if ($value === null) {
                $needsMove = true;

                continue;
            }

            $x = $paddingLeft + ($count <= 1 ? $plotWidth / 2 : $index * $plotWidth / ($count - 1));
            $y = $paddingTop + $plotHeight - ($value / max($maxY, 1e-12)) * $plotHeight;
            $path .= sprintf('%s%.1f,%.1f', $needsMove ? 'M' : ' L', $x, $y);
            $markers .= sprintf(
                '<circle cx="%.1f" cy="%.1f" r="3" fill="#2b6cb0"><title>%s%s @ %s</title></circle>',
                $x,
                $y,
                $formatter->format($value),
                $unit,
                htmlspecialchars($labels[$index] ?? '', ENT_QUOTES),
            );
            $needsMove = false;
        }

        $svg .= sprintf('<path d="%s" fill="none" stroke="#2b6cb0" stroke-width="1.5"/>', $path);
        $svg .= $markers;

        $ticks = [0];

        if ($count > 2) {
            $ticks[] = (int) ($count / 2);
        }

        if ($count > 1) {
            $ticks[] = $count - 1;
        }

        foreach ($ticks as $tick) {
            $tickX = $paddingLeft + ($count <= 1 ? $plotWidth / 2 : $tick * $plotWidth / ($count - 1));
            $svg .= sprintf('<text x="%.1f" y="%d" font-size="11" text-anchor="middle" fill="#718096">%s</text>', $tickX, $height - $paddingBottom + 18, htmlspecialchars($labels[$tick] ?? '', ENT_QUOTES));
        }

        $svg .= '</svg>';

        return $svg;
    }
}
