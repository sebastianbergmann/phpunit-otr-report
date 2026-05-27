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
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextReport::class)]
#[UsesClass(Metric::class)]
#[UsesClass(TestRun::class)]
#[TestDox('TextReport')]
#[Small]
final class TextReportTest extends TestCase
{
    public function testRendersTheSlowestTestsOrderedByRuntime(): void
    {
        $this->assertSame(
            $this->expectation('text/slowest.txt'),
            (new TextReport)->render($this->createTestRun(), false, 10, Metric::Time),
        );
    }

    public function testRendersOnlyTestsSlowerThanTheMeanWithTheirFactor(): void
    {
        $this->assertSame(
            $this->expectation('text/slowest-with-mean.txt'),
            (new TextReport)->render($this->createTestRun(), true, 10, Metric::Time),
        );
    }

    public function testLimitsTheNumberOfTestsListed(): void
    {
        $this->assertSame(
            $this->expectation('text/slowest-with-limit.txt'),
            (new TextReport)->render($this->createTestRun(), false, 2, Metric::Time),
        );
    }

    public function testLimitsTheNumberOfTestsListedAboveTheMean(): void
    {
        $run = new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable('2026-01-01T08:00:00.000000Z'),
            12.0,
            [
                'Slow::a'   => 6.0,
                'Medium::b' => 5.0,
                'Fast::c'   => 1.0,
            ],
            [],
            [],
        );

        $report = (new TextReport)->render($run, true, 1, Metric::Time);

        $this->assertStringContainsString('Slow::a', $report);
        $this->assertStringNotContainsString('Medium::b', $report);
    }

    public function testSortsByCpuTime(): void
    {
        $this->assertSame(
            $this->expectation('text/slowest-by-cpu.txt'),
            (new TextReport)->render($this->createTestRun(), false, 10, Metric::Cpu),
        );
    }

    public function testSortsByCpuTimeAboveTheMean(): void
    {
        $this->assertSame(
            $this->expectation('text/slowest-by-cpu-above-mean.txt'),
            (new TextReport)->render($this->createTestRun(), true, 10, Metric::Cpu),
        );
    }

    public function testSortsByPeakMemory(): void
    {
        $this->assertSame(
            $this->expectation('text/slowest-by-memory.txt'),
            (new TextReport)->render($this->createTestRun(), false, 10, Metric::Memory),
        );
    }

    public function testSortsByPeakMemoryAboveTheMean(): void
    {
        $this->assertSame(
            $this->expectation('text/slowest-by-memory-above-mean.txt'),
            (new TextReport)->render($this->createTestRun(), true, 10, Metric::Memory),
        );
    }

    public function testRendersNothingForARunWithoutTests(): void
    {
        $run = new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable('2026-01-01T08:00:00.000000Z'),
            0.0,
            [],
            [],
            [],
        );

        $this->assertSame('', (new TextReport)->render($run, false, 10, Metric::Time));
        $this->assertSame('', (new TextReport)->render($run, true, 10, Metric::Time));
        $this->assertSame('', (new TextReport)->render($run, false, 10, Metric::Memory));
    }

    private function createTestRun(): TestRun
    {
        return new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable('2026-01-01T08:00:00.000000Z'),
            6.0,
            [
                'A::a' => 1.0,
                'B::b' => 2.0,
                'C::c' => 3.0,
            ],
            [
                'A::a' => 3.0,
                'B::b' => 1.0,
                'C::c' => 2.0,
            ],
            [
                'A::a' => 2000.0,
                'B::b' => 4000.0,
                'C::c' => 1000.0,
            ],
        );
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
