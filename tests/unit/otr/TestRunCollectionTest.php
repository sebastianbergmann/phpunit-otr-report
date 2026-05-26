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

#[CoversClass(TestRunCollection::class)]
#[UsesClass(TestRun::class)]
#[UsesClass(TestRunCollectionIterator::class)]
#[TestDox('TestRunCollection')]
#[Small]
final class TestRunCollectionTest extends TestCase
{
    public function testCanBeCreatedFromAListOfTestRuns(): void
    {
        $first  = $this->createTestRun('2026-01-01T10:00:00.000000Z');
        $second = $this->createTestRun('2026-01-02T10:00:00.000000Z');

        $collection = TestRunCollection::fromArray([$first, $second]);

        $this->assertSame([$first, $second], $collection->asArray());
    }

    public function testCountsItsTestRuns(): void
    {
        $collection = TestRunCollection::fromArray(
            [
                $this->createTestRun('2026-01-01T10:00:00.000000Z'),
                $this->createTestRun('2026-01-02T10:00:00.000000Z'),
            ],
        );

        $this->assertCount(2, $collection);
        $this->assertSame(2, $collection->count());
        $this->assertFalse($collection->isEmpty());
    }

    public function testKnowsWhenItIsEmpty(): void
    {
        $collection = TestRunCollection::fromArray([]);

        $this->assertSame(0, $collection->count());
        $this->assertTrue($collection->isEmpty());
    }

    public function testCanBeIteratedInOrder(): void
    {
        $first  = $this->createTestRun('2026-01-01T10:00:00.000000Z');
        $second = $this->createTestRun('2026-01-02T10:00:00.000000Z');

        $collection = TestRunCollection::fromArray([$first, $second]);

        $this->assertInstanceOf(TestRunCollectionIterator::class, $collection->getIterator());

        $runs = [];

        foreach ($collection as $key => $run) {
            $runs[$key] = $run;
        }

        $this->assertSame([$first, $second], $runs);
    }

    private function createTestRun(string $startedAt): TestRun
    {
        return new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable($startedAt),
            1.0,
            ['Vendor\ExampleTest::test_one' => 0.5],
            [],
            [],
        );
    }
}
