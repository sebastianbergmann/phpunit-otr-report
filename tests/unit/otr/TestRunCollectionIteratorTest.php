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

#[CoversClass(TestRunCollectionIterator::class)]
#[UsesClass(TestRunCollection::class)]
#[UsesClass(TestRun::class)]
#[TestDox('TestRunCollectionIterator')]
#[Small]
final class TestRunCollectionIteratorTest extends TestCase
{
    public function testWalksTheTestRunsInOrder(): void
    {
        $first  = $this->createTestRun('2026-01-01T10:00:00.000000Z');
        $second = $this->createTestRun('2026-01-02T10:00:00.000000Z');

        $iterator = new TestRunCollectionIterator(TestRunCollection::fromArray([$first, $second]));

        $iterator->rewind();

        $this->assertTrue($iterator->valid());
        $this->assertSame(0, $iterator->key());
        $this->assertSame($first, $iterator->current());

        $iterator->next();

        $this->assertTrue($iterator->valid());
        $this->assertSame(1, $iterator->key());
        $this->assertSame($second, $iterator->current());

        $iterator->next();

        $this->assertFalse($iterator->valid());
    }

    public function testIsNotValidForAnEmptyCollection(): void
    {
        $iterator = new TestRunCollectionIterator(TestRunCollection::fromArray([]));

        $iterator->rewind();

        $this->assertFalse($iterator->valid());
    }

    private function createTestRun(string $startedAt): TestRun
    {
        return new TestRun(
            '/path/to/logfile.xml',
            new DateTimeImmutable($startedAt),
            1.0,
            ['Vendor\ExampleTest::test_one' => 0.5],
        );
    }
}
