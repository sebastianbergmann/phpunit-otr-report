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

#[CoversClass(Sparkline::class)]
#[UsesClass(DataSeries::class)]
#[Small]
final class SparklineTest extends TestCase
{
    public function testRendersASparklineWithGaps(): void
    {
        $this->assertSame(
            $this->expectation('svg/sparkline.svg'),
            (new Sparkline)->render([1.0, null, 3.0], 220, 28),
        );
    }

    public function testRendersASingleValueInTheCenter(): void
    {
        $this->assertSame(
            $this->expectation('svg/sparkline-with-single-value.svg'),
            (new Sparkline)->render([2.0], 220, 28),
        );
    }

    public function testRendersAnEmptySparklineWhenThereAreNoValues(): void
    {
        $this->assertSame('<svg width="220" height="28"></svg>', (new Sparkline)->render([], 220, 28));
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
