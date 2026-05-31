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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arguments::class)]
#[UsesClass(Metric::class)]
#[Small]
final class ArgumentsTest extends TestCase
{
    public function testExposesTheValuesItWasCreatedFrom(): void
    {
        $arguments = new Arguments('slowest', ['logfile.xml'], true, true, 5, Metric::Cpu, true, true);

        $this->assertSame('slowest', $arguments->command());
        $this->assertSame(['logfile.xml'], $arguments->arguments());
        $this->assertTrue($arguments->help());
        $this->assertTrue($arguments->aboveMean());
        $this->assertSame(5, $arguments->limit());
        $this->assertSame(Metric::Cpu, $arguments->sort());
        $this->assertTrue($arguments->testdox());
        $this->assertTrue($arguments->version());
    }

    public function testCommandCanBeNull(): void
    {
        $arguments = new Arguments(null, [], false, false, 10, Metric::Time, false, false);

        $this->assertNull($arguments->command());
        $this->assertSame([], $arguments->arguments());
        $this->assertFalse($arguments->help());
        $this->assertFalse($arguments->aboveMean());
        $this->assertSame(10, $arguments->limit());
        $this->assertSame(Metric::Time, $arguments->sort());
        $this->assertFalse($arguments->testdox());
        $this->assertFalse($arguments->version());
    }
}
