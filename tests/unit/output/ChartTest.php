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

use function assert;
use function file_get_contents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Chart::class)]
#[UsesClass(DataSeries::class)]
#[UsesClass(NumberFormatter::class)]
#[Small]
final class ChartTest extends TestCase
{
    public function testRendersALineChartWithAxisLabelsMarkersAndGaps(): void
    {
        $this->assertSame(
            $this->expectation('svg/chart.svg'),
            (new Chart)->render([1.0, null, 3.0], ['2026-01-01', '2026-01-02', '2026-01-03'], 1040, 300, ' s'),
        );
    }

    public function testRendersASingleValueInTheCenter(): void
    {
        $this->assertSame(
            $this->expectation('svg/chart-with-single-value.svg'),
            (new Chart)->render([2.0], ['2026-01-01'], 1040, 300, ' s'),
        );
    }

    public function testRendersNothingWhenThereAreNoValues(): void
    {
        $this->assertSame('', (new Chart)->render([], []));
    }

    /**
     * @param non-empty-string $path
     *
     * @return non-empty-string
     */
    private function expectation(string $path): string
    {
        $contents = file_get_contents(__DIR__ . '/../../expectations/' . $path);

        assert($contents !== false);
        assert($contents !== '');

        return $contents;
    }
}
