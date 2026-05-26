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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArgumentsBuilder::class)]
#[UsesClass(Arguments::class)]
#[UsesClass(ArgumentsBuilderException::class)]
#[UsesClass(Metric::class)]
#[UsesClass(RequiredArgumentMissingException::class)]
#[TestDox('ArgumentsBuilder')]
#[Small]
final class ArgumentsBuilderTest extends TestCase
{
    public function testParsesTheSlowestCommandWithItsDefaults(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['otr-report', 'slowest', 'logfile.xml']);

        $this->assertSame('slowest', $arguments->command());
        $this->assertSame(['logfile.xml'], $arguments->arguments());
        $this->assertFalse($arguments->help());
        $this->assertFalse($arguments->aboveMean());
        $this->assertSame(10, $arguments->limit());
        $this->assertSame(Metric::Time, $arguments->sort());
        $this->assertFalse($arguments->version());
    }

    public function testParsesTheAboveMeanOption(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--above-mean', 'logfile.xml']);

        $this->assertTrue($arguments->aboveMean());
    }

    public function testParsesTheLimitOption(): void
    {
        $this->assertSame(
            5,
            (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--limit', '5', 'logfile.xml'])->limit(),
        );

        $this->assertSame(
            7,
            (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--limit=7', 'logfile.xml'])->limit(),
        );
    }

    public function testParsesTheSortOption(): void
    {
        $this->assertSame(
            Metric::Cpu,
            (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--sort', 'cpu', 'logfile.xml'])->sort(),
        );

        $this->assertSame(
            Metric::Memory,
            (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--sort=memory', 'logfile.xml'])->sort(),
        );

        $this->assertSame(
            Metric::Time,
            (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--sort', 'time', 'logfile.xml'])->sort(),
        );
    }

    public function testParsesAllSlowestOptionsTogether(): void
    {
        $arguments = (new ArgumentsBuilder)->build(
            ['otr-report', 'slowest', '--above-mean', '--limit', '3', '--sort', 'cpu', 'logfile.xml'],
        );

        $this->assertSame('slowest', $arguments->command());
        $this->assertSame(['logfile.xml'], $arguments->arguments());
        $this->assertTrue($arguments->aboveMean());
        $this->assertSame(3, $arguments->limit());
        $this->assertSame(Metric::Cpu, $arguments->sort());
    }

    public function testParsesTheTrendsCommand(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['otr-report', 'trends', 'logfiles', 'report.html']);

        $this->assertSame('trends', $arguments->command());
        $this->assertSame(['logfiles', 'report.html'], $arguments->arguments());
    }

    public function testParsesTheHelpOption(): void
    {
        $this->assertTrue((new ArgumentsBuilder)->build(['otr-report', '--help'])->help());
        $this->assertTrue((new ArgumentsBuilder)->build(['otr-report', '-h'])->help());
    }

    public function testParsesTheVersionOption(): void
    {
        $this->assertTrue((new ArgumentsBuilder)->build(['otr-report', '--version'])->version());
        $this->assertTrue((new ArgumentsBuilder)->build(['otr-report', '-v'])->version());
    }

    public function testHasNoCommandWhenNoneIsGiven(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['otr-report']);

        $this->assertNull($arguments->command());
        $this->assertSame([], $arguments->arguments());
    }

    public function testHasNoCommandForAnUnknownCommand(): void
    {
        $arguments = (new ArgumentsBuilder)->build(['otr-report', 'unknown']);

        $this->assertNull($arguments->command());
        $this->assertSame([], $arguments->arguments());
    }

    public function testRejectsTheSlowestCommandWithoutAFile(): void
    {
        $this->expectException(RequiredArgumentMissingException::class);
        $this->expectExceptionMessage('Required argument "file" is missing');

        (new ArgumentsBuilder)->build(['otr-report', 'slowest']);
    }

    public function testRejectsTheTrendsCommandWithoutAnOutput(): void
    {
        $this->expectException(RequiredArgumentMissingException::class);
        $this->expectExceptionMessage('Required argument "output" is missing');

        (new ArgumentsBuilder)->build(['otr-report', 'trends', 'logfiles']);
    }

    public function testRejectsANonPositiveIntegerLimit(): void
    {
        $this->expectException(ArgumentsBuilderException::class);
        $this->expectExceptionMessage('Value of --limit must be a positive integer, got "0"');

        (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--limit', '0', 'logfile.xml']);
    }

    public function testRejectsANonNumericLimit(): void
    {
        $this->expectException(ArgumentsBuilderException::class);
        $this->expectExceptionMessage('Value of --limit must be a positive integer, got "abc"');

        (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--limit', 'abc', 'logfile.xml']);
    }

    public function testRejectsAnUnknownSortMetric(): void
    {
        $this->expectException(ArgumentsBuilderException::class);
        $this->expectExceptionMessage('Value of --sort must be one of "time", "cpu", "memory", got "bogus"');

        (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--sort', 'bogus', 'logfile.xml']);
    }

    public function testWrapsErrorsFromTheCliParser(): void
    {
        $this->expectException(ArgumentsBuilderException::class);

        (new ArgumentsBuilder)->build(['otr-report', 'slowest', '--unknown', 'logfile.xml']);
    }
}
