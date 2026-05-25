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

final readonly class DataSeries
{
    /**
     * @var list<null|float>
     */
    private array $values;

    /**
     * @param list<null|float> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Returns the present data points keyed by their original index, dropping gaps.
     *
     * @return array<non-negative-int, float>
     */
    public function withoutGaps(): array
    {
        $points = [];

        foreach ($this->values as $index => $value) {
            if ($value !== null) {
                $points[$index] = $value;
            }
        }

        return $points;
    }
}
