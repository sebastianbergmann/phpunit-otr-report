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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(NumberFormatter::class)]
#[TestDox('NumberFormatter')]
#[Small]
final class NumberFormatterTest extends TestCase
{
    /**
     * @return array<string, array{float, string}>
     */
    public static function provider(): array
    {
        return [
            'at least 100 is rendered without decimals'     => [150.0, '150'],
            'exactly 100 is rendered without decimals'      => [100.0, '100'],
            'at least 1 is rendered with two decimals'      => [2.7, '2.70'],
            'exactly 1 is rendered with two decimals'       => [1.0, '1.00'],
            'at least 0.01 is rendered with three decimals' => [0.5, '0.500'],
            'exactly 0.01 is rendered with three decimals'  => [0.01, '0.010'],
            'less than 0.01 is rendered with four decimals' => [0.005, '0.0050'],
            'zero is rendered with four decimals'           => [0.0, '0.0000'],
        ];
    }

    #[DataProvider('provider')]
    public function testFormatsNumbersWithAdaptivePrecision(float $value, string $expected): void
    {
        $this->assertSame($expected, (new NumberFormatter)->format($value));
    }
}
