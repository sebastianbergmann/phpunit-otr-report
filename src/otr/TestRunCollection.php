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

use function array_is_list;
use function array_values;
use function assert;
use function count;
use IteratorAggregate;

/**
 * @template-implements IteratorAggregate<non-negative-int, TestRun>
 *
 * @immutable
 */
final readonly class TestRunCollection implements IteratorAggregate
{
    /**
     * @var list<TestRun>
     */
    private array $runs;

    /**
     * @param list<TestRun> $runs
     */
    public static function fromArray(array $runs): self
    {
        assert(array_is_list($runs));

        return new self(...$runs);
    }

    private function __construct(TestRun ...$runs)
    {
        $this->runs = array_values($runs);
    }

    /**
     * @return list<TestRun>
     */
    public function asArray(): array
    {
        return $this->runs;
    }

    public function count(): int
    {
        return count($this->runs);
    }

    public function isEmpty(): bool
    {
        return $this->runs === [];
    }

    public function getIterator(): TestRunCollectionIterator
    {
        return new TestRunCollectionIterator($this);
    }
}
