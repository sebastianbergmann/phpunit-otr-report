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
use Iterator;

/**
 * @template-implements Iterator<non-negative-int, TestRun>
 */
final class TestRunCollectionIterator implements Iterator
{
    /**
     * @var list<TestRun>
     */
    private readonly array $runs;

    /**
     * @var non-negative-int
     */
    private int $position = 0;

    public function __construct(TestRunCollection $runs)
    {
        $this->runs = $runs->asArray();
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->runs[$this->position]);
    }

    /**
     * @return non-negative-int
     */
    public function key(): int
    {
        return $this->position;
    }

    public function current(): TestRun
    {
        assert(isset($this->runs[$this->position]));

        return $this->runs[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }
}
