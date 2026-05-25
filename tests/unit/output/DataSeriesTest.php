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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(DataSeries::class)]
#[TestDox('DataSeries')]
#[Small]
final class DataSeriesTest extends TestCase
{
    public function testDropsGapsWhileKeepingTheOriginalIndices(): void
    {
        $this->assertSame(
            [0 => 1.0, 2 => 3.0],
            new DataSeries([1.0, null, 3.0])->withoutGaps(),
        );
    }

    public function testReturnsNothingWhenEveryValueIsAGap(): void
    {
        $this->assertSame([], new DataSeries([null, null])->withoutGaps());
    }
}
