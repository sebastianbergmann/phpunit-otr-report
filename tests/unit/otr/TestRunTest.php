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

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestRun::class)]
#[UsesClass(Metric::class)]
#[TestDox('TestRun')]
#[Small]
final class TestRunTest extends TestCase
{
    public function testExposesTheValuesItWasCreatedFrom(): void
    {
        $startedAt = new DateTimeImmutable('2026-01-01T08:00:00.000000Z');

        $run = new TestRun(
            '/path/to/logfile.xml',
            $startedAt,
            1.5,
            [
                'Vendor\ExampleTest::test_one' => 0.5,
                'Vendor\ExampleTest::test_two' => 0.75,
            ],
            [
                'Vendor\ExampleTest::test_one' => 0.4,
                'Vendor\ExampleTest::test_two' => 0.6,
            ],
            [
                'Vendor\ExampleTest::test_one' => 1000.0,
                'Vendor\ExampleTest::test_two' => 2000.0,
            ],
        );

        $this->assertSame('/path/to/logfile.xml', $run->file());
        $this->assertSame($startedAt, $run->startedAt());
        $this->assertSame(1.5, $run->totalTime());
        $this->assertSame(
            [
                'Vendor\ExampleTest::test_one' => 0.5,
                'Vendor\ExampleTest::test_two' => 0.75,
            ],
            $run->tests(),
        );
    }

    public function testExposesTheValuesForEachMetric(): void
    {
        $run = new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable('2026-01-01T08:00:00.000000Z'),
            1.5,
            ['Vendor\ExampleTest::test_one' => 0.5],
            ['Vendor\ExampleTest::test_one' => 0.4],
            ['Vendor\ExampleTest::test_one' => 1000.0],
        );

        $this->assertSame(['Vendor\ExampleTest::test_one' => 0.5], $run->valuesFor(Metric::Time));
        $this->assertSame(['Vendor\ExampleTest::test_one' => 0.4], $run->valuesFor(Metric::Cpu));
        $this->assertSame(['Vendor\ExampleTest::test_one' => 1000.0], $run->valuesFor(Metric::Memory));
        $this->assertSame($run->tests(), $run->valuesFor(Metric::Time));
    }

    public function testCountsItsTests(): void
    {
        $run = new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable('2026-01-01T08:00:00.000000Z'),
            1.5,
            [
                'Vendor\ExampleTest::test_one' => 0.5,
                'Vendor\ExampleTest::test_two' => 0.75,
            ],
            [],
            [],
        );

        $this->assertSame(2, $run->testCount());
        $this->assertFalse($run->isEmpty());
    }

    public function testKnowsWhenItContainsNoTests(): void
    {
        $run = new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable('2026-01-01T08:00:00.000000Z'),
            1.5,
            [],
            [],
            [],
        );

        $this->assertSame(0, $run->testCount());
        $this->assertTrue($run->isEmpty());
    }
}
