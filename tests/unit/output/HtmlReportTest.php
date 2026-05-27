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
use function sprintf;
use function substr_count;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlReport::class)]
#[UsesClass(Chart::class)]
#[UsesClass(DataSeries::class)]
#[UsesClass(NumberFormatter::class)]
#[UsesClass(Sparkline::class)]
#[UsesClass(TestRun::class)]
#[UsesClass(TestRunCollection::class)]
#[TestDox('HtmlReport')]
#[Small]
final class HtmlReportTest extends TestCase
{
    public function testRendersATrendReportForMultipleRuns(): void
    {
        $collection = TestRunCollection::fromArray(
            [
                $this->createTestRun('2026-01-01T10:00:00.000000Z', 1.1, ['Slow::a' => 1.0, 'Fast::b' => 0.1], '/path/run1.xml'),
                $this->createTestRun('2026-01-02T10:00:00.000000Z', 2.7, ['Slow::a' => 2.0, 'Fast::b' => 0.2, 'New::c' => 0.5], '/path/run2.xml'),
            ],
        );

        $this->assertSame(
            $this->expectation('html/trends.html'),
            (new HtmlReport)->render($collection),
        );
    }

    public function testRendersATrendReportForManyRuns(): void
    {
        $collection = TestRunCollection::fromArray(
            [
                $this->createTestRun('2026-01-01T10:00:00.000000Z', 50.0, ['Slow::a' => 10.0], '/path/run1.xml'),
                $this->createTestRun('2026-01-02T10:00:00.000000Z', 100.0, ['Slow::a' => 60.0], '/path/run2.xml'),
                $this->createTestRun('2026-01-03T10:00:00.000000Z', 150.0, ['Slow::a' => 120.0], '/path/run3.xml'),
            ],
        );

        $this->assertSame(
            $this->expectation('html/trends-with-many-runs.html'),
            (new HtmlReport)->render($collection),
        );
    }

    public function testRendersATrendReportForASingleRun(): void
    {
        $collection = TestRunCollection::fromArray(
            [
                $this->createTestRun('2026-01-01T10:00:00.000000Z', 1.1, ['Slow::a' => 1.0, 'Fast::b' => 0.1], '/path/run1.xml'),
            ],
        );

        $this->assertSame(
            $this->expectation('html/trends-with-single-run.html'),
            (new HtmlReport)->render($collection),
        );
    }

    public function testListsAtMostTheTenSlowestTests(): void
    {
        $tests = [];

        for ($i = 1; $i <= 12; $i++) {
            $tests[sprintf('Test%02d::test', $i)] = $i / 100;
        }

        $collection = TestRunCollection::fromArray(
            [
                $this->createTestRun('2026-01-01T10:00:00.000000Z', 1.0, $tests, '/path/run1.xml'),
            ],
        );

        $this->assertSame(10, substr_count((new HtmlReport)->render($collection), 'class="spark"'));
    }

    public function testHighlightsTestsThatChangedByAtLeastTenPercent(): void
    {
        $collection = TestRunCollection::fromArray(
            [
                $this->createTestRun('2026-01-01T10:00:00.000000Z', 1.0, ['Grew::a' => 10.0, 'Shrank::b' => 10.0], '/path/run1.xml'),
                $this->createTestRun('2026-01-02T10:00:00.000000Z', 1.0, ['Grew::a' => 11.0, 'Shrank::b' => 9.0], '/path/run2.xml'),
            ],
        );

        $html = (new HtmlReport)->render($collection);

        $this->assertStringContainsString('#c53030', $html);
        $this->assertStringContainsString('#2f855a', $html);
    }

    public function testOmitsTheDeltaWhenTheBaselineValueIsZero(): void
    {
        $collection = TestRunCollection::fromArray(
            [
                $this->createTestRun('2026-01-01T10:00:00.000000Z', 1.0, ['Zero::a' => 0.0], '/path/run1.xml'),
                $this->createTestRun('2026-01-02T10:00:00.000000Z', 1.0, ['Zero::a' => 1.0], '/path/run2.xml'),
            ],
        );

        $this->assertStringNotContainsString('style="color:', (new HtmlReport)->render($collection));
    }

    public function testRendersNothingForAnEmptyCollection(): void
    {
        $this->assertSame('', (new HtmlReport)->render(TestRunCollection::fromArray([])));
    }

    /**
     * @param array<non-empty-string, float> $tests
     */
    private function createTestRun(string $startedAt, float $totalTime, array $tests, string $file): TestRun
    {
        return new TestRun($file, new DateTimeImmutable($startedAt), $totalTime, $tests, [], []);
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
