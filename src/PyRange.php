<?php

declare(strict_types=1);

namespace QXS\pythonic;

use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * Python-like range for PHP.
 *
 * Usage:
 *   py_range(10)          → 0..9
 *   py_range(2, 10)       → 2..9
 *   py_range(0, 10, 2)    → 0, 2, 4, 6, 8
 *   py_range(10, 0, -1)   → 10, 9, 8, ..., 1
 *
 * Supports foreach, count, contains, slice, toList(), and chaining.
 */
class PyRange implements Countable, IteratorAggregate
{
    use PyObject;

    protected int $start;
    protected int $stop;
    protected int $step;

    public function __construct(int $startOrStop, ?int $stop = null, int $step = 1)
    {
        if ($step === 0) {
            throw new \ValueError("range() arg 3 must not be zero");
        }
        if ($stop === null) {
            $this->start = 0;
            $this->stop = $startOrStop;
        } else {
            $this->start = $startOrStop;
            $this->stop = $stop;
        }
        $this->step = $step;
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        if ($this->step === 1) {
            return "range({$this->start}, {$this->stop})";
        }
        return "range({$this->start}, {$this->stop}, {$this->step})";
    }

    public function __bool(): bool
    {
        return $this->__len() > 0;
    }

    public function __len(): int
    {
        if ($this->step > 0) {
            return max(0, (int)ceil(($this->stop - $this->start) / $this->step));
        }
        return max(0, (int)ceil(($this->start - $this->stop) / (-$this->step)));
    }

    // ─── Countable & IteratorAggregate ───────────────────────

    public function count(): int
    {
        return $this->__len();
    }

    public function getIterator(): \Generator
    {
        if ($this->step > 0) {
            for ($i = $this->start; $i < $this->stop; $i += $this->step) {
                yield $i;
            }
        } else {
            for ($i = $this->start; $i > $this->stop; $i += $this->step) {
                yield $i;
            }
        }
    }

    // ─── Membership ──────────────────────────────────────────

    public function contains(int $value): bool
    {
        if ($this->step > 0) {
            if ($value < $this->start || $value >= $this->stop) return false;
        } else {
            if ($value > $this->start || $value <= $this->stop) return false;
        }
        return ($value - $this->start) % $this->step === 0;
    }

    public function in(int $value): bool
    {
        return $this->contains($value);
    }

    // ─── Indexing ────────────────────────────────────────────

    public function get(int $index): int
    {
        $len = $this->__len();
        if ($index < 0) $index += $len;
        if ($index < 0 || $index >= $len) {
            throw new \OutOfRangeException("range index out of range");
        }
        return $this->start + $index * $this->step;
    }

    // ─── Functional ──────────────────────────────────────────

    /** Comprehension on range: py_range(10)->comp(fn($x) => $x**2, fn($x) => $x % 2 === 0) */
    public function comp(callable $transform, ?callable $filter = null): PyList
    {
        $result = [];
        foreach ($this as $value) {
            if ($filter !== null && !$filter($value)) continue;
            $result[] = $transform($value);
        }
        return new PyList($result);
    }

    public function map(callable $fn): PyList
    {
        $result = [];
        foreach ($this as $value) {
            $result[] = $fn($value);
        }
        return new PyList($result);
    }

    public function filter(callable $fn): PyList
    {
        $result = [];
        foreach ($this as $value) {
            if ($fn($value)) $result[] = $value;
        }
        return new PyList($result);
    }

    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        return $this->toList()->reduce($fn, $initial);
    }

    public function sum(): int
    {
        // Arithmetic series formula for efficiency
        $n = $this->__len();
        if ($n === 0) return 0;
        $last = $this->start + ($n - 1) * $this->step;
        return (int)($n * ($this->start + $last) / 2);
    }

    // ─── Conversion ──────────────────────────────────────────

    public function toPhp(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    public function toList(): PyList
    {
        return new PyList($this->toPhp());
    }

    // ─── Accessors ───────────────────────────────────────────

    public function start(): int { return $this->start; }
    public function stop(): int { return $this->stop; }
    public function step(): int { return $this->step; }
}
