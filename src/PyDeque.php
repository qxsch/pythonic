<?php

declare(strict_types=1);

namespace QXS\pythonic;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use ArrayAccess;
use JsonSerializable;

/**
 * Python collections.deque for PHP.
 *
 * A double-ended queue with O(1) append/pop on both ends.
 * Optionally bounded with maxlen.
 *
 * Usage:
 *   $dq = new PyDeque([1, 2, 3], maxlen: 5);
 *   $dq->append(4);           // [1, 2, 3, 4]
 *   $dq->appendleft(0);       // [0, 1, 2, 3, 4]
 *   $dq->pop();               // 4  → [0, 1, 2, 3]
 *   $dq->popleft();           // 0  → [1, 2, 3]
 *   $dq->rotate(1);           // [3, 1, 2]
 *   $dq->rotate(-1);          // [1, 2, 3]
 *   $dq->extend([4, 5]);      // [1, 2, 3, 4, 5]
 *   $dq->extendleft([0, -1]); // [-1, 0, 1, 2, 3]  (each item prepended)
 *
 * With maxlen, items are dropped from the opposite end:
 *   $dq = new PyDeque([1,2,3], maxlen: 3);
 *   $dq->append(4);       // [2, 3, 4] — 1 dropped from left
 *   $dq->appendleft(0);   // [0, 2, 3] — 4 dropped from right
 */
class PyDeque implements Countable, IteratorAggregate, ArrayAccess, JsonSerializable
{
    use PyObject;

    protected array $data;
    protected ?int $maxlen;

    /**
     * @param iterable $items   Initial items
     * @param int|null $maxlen  Maximum size (null = unlimited)
     */
    public function __construct(iterable $items = [], ?int $maxlen = null)
    {
        if ($maxlen !== null && $maxlen < 0) {
            throw new \ValueError("maxlen must be non-negative");
        }
        $this->maxlen = $maxlen;
        $this->data = [];

        foreach ($items as $item) {
            $this->data[] = $item;
        }

        $this->enforce();
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        $items = array_map(fn($v) => $this->reprValue($v), $this->data);
        $repr = 'deque([' . implode(', ', $items) . ']';
        if ($this->maxlen !== null) {
            $repr .= ', maxlen=' . $this->maxlen;
        }
        return $repr . ')';
    }

    public function __bool(): bool
    {
        return !empty($this->data);
    }

    public function __len(): int
    {
        return count($this->data);
    }

    public function toPhp(): array
    {
        return $this->data;
    }

    // ─── Right-end operations ────────────────────────────────

    /** Append to right end. */
    public function append(mixed $item): static
    {
        $this->data[] = $item;
        $this->enforceRight();
        return $this;
    }

    /** Remove and return item from right end. */
    public function pop(): mixed
    {
        if (empty($this->data)) {
            throw new \UnderflowException("pop from an empty deque");
        }
        return array_pop($this->data);
    }

    /** Extend right end. */
    public function extend(iterable $items): static
    {
        foreach ($items as $item) {
            $this->data[] = $item;
        }
        $this->enforceRight();
        return $this;
    }

    // ─── Left-end operations ─────────────────────────────────

    /** Append to left end. */
    public function appendleft(mixed $item): static
    {
        array_unshift($this->data, $item);
        $this->enforceLeft();
        return $this;
    }

    /** Remove and return item from left end. */
    public function popleft(): mixed
    {
        if (empty($this->data)) {
            throw new \UnderflowException("pop from an empty deque");
        }
        return array_shift($this->data);
    }

    /** Extend left end (each item prepended individually, so order reverses). */
    public function extendleft(iterable $items): static
    {
        foreach ($items as $item) {
            array_unshift($this->data, $item);
        }
        $this->enforceLeft();
        return $this;
    }

    // ─── Rotate ──────────────────────────────────────────────

    /**
     * Rotate n steps to the right. Negative n rotates left.
     *   deque([1,2,3,4,5])->rotate(2)  → [4,5,1,2,3]
     *   deque([1,2,3,4,5])->rotate(-2) → [3,4,5,1,2]
     */
    public function rotate(int $n = 1): static
    {
        $len = count($this->data);
        if ($len <= 1) return $this;

        $n = $n % $len;
        if ($n === 0) return $this;

        if ($n > 0) {
            // Take n items from right, move to left
            $tail = array_splice($this->data, -$n);
            $this->data = array_merge($tail, $this->data);
        } else {
            // Take |n| items from left, move to right
            $head = array_splice($this->data, 0, abs($n));
            $this->data = array_merge($this->data, $head);
        }
        return $this;
    }

    // ─── Misc Python deque methods ───────────────────────────

    /** Remove first occurrence of value. */
    public function remove(mixed $value): static
    {
        $key = array_search($value, $this->data, true);
        if ($key === false) {
            throw new \ValueError("deque.remove(x): x not in deque");
        }
        array_splice($this->data, $key, 1);
        return $this;
    }

    /** Count occurrences of value. */
    public function countOf(mixed $value): int
    {
        return count(array_filter($this->data, fn($v) => $v === $value));
    }

    /** Return index of first occurrence. */
    public function index(mixed $value, int $start = 0, ?int $stop = null): int
    {
        $stop ??= count($this->data);
        for ($i = $start; $i < $stop && $i < count($this->data); $i++) {
            if ($this->data[$i] === $value) return $i;
        }
        throw new \ValueError("'{$value}' is not in deque");
    }

    /** Insert value at position. */
    public function insert(int $index, mixed $value): static
    {
        if ($this->maxlen !== null && count($this->data) >= $this->maxlen) {
            throw new \OverflowException("deque already at its maximum size");
        }
        array_splice($this->data, $index, 0, [$value]);
        return $this;
    }

    /** Clear all items. */
    public function clear(): static
    {
        $this->data = [];
        return $this;
    }

    /** Reverse in-place. */
    public function reverse(): static
    {
        $this->data = array_reverse($this->data);
        return $this;
    }

    /** Return a shallow copy. */
    public function copy(): static
    {
        return new static($this->data, $this->maxlen);
    }

    /** Does the deque contain the value? */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->data, true);
    }

    /** Get the maxlen, or null if unlimited. */
    public function getMaxlen(): ?int
    {
        return $this->maxlen;
    }

    /** Peek at right end without removing. */
    public function peekright(): mixed
    {
        if (empty($this->data)) {
            throw new \UnderflowException("peek from an empty deque");
        }
        return end($this->data);
    }

    /** Peek at left end without removing. */
    public function peekleft(): mixed
    {
        if (empty($this->data)) {
            throw new \UnderflowException("peek from an empty deque");
        }
        return $this->data[0];
    }

    // ─── ArrayAccess ─────────────────────────────────────────

    public function offsetExists(mixed $offset): bool
    {
        $offset = $this->resolveIndex((int)$offset, false);
        return $offset !== null;
    }

    public function offsetGet(mixed $offset): mixed
    {
        $idx = $this->resolveIndex((int)$offset);
        return $this->data[$idx];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->append($value);
            return;
        }
        $idx = $this->resolveIndex((int)$offset);
        $this->data[$idx] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $idx = $this->resolveIndex((int)$offset);
        array_splice($this->data, $idx, 1);
    }

    // ─── Countable / Iterable / JSON ─────────────────────────

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    // ─── Internal ────────────────────────────────────────────

    private function resolveIndex(int $index, bool $throw = true): ?int
    {
        $len = count($this->data);
        if ($index < 0) $index += $len;
        if ($index < 0 || $index >= $len) {
            if ($throw) throw new \OutOfRangeException("deque index out of range");
            return null;
        }
        return $index;
    }

    /** Enforce maxlen by removing from left (for right-append). */
    private function enforceRight(): void
    {
        if ($this->maxlen !== null) {
            while (count($this->data) > $this->maxlen) {
                array_shift($this->data);
            }
        }
    }

    /** Enforce maxlen by removing from right (for left-append). */
    private function enforceLeft(): void
    {
        if ($this->maxlen !== null) {
            while (count($this->data) > $this->maxlen) {
                array_pop($this->data);
            }
        }
    }

    /** Enforce on init (trim from right). */
    private function enforce(): void
    {
        if ($this->maxlen !== null && count($this->data) > $this->maxlen) {
            $this->data = array_slice($this->data, count($this->data) - $this->maxlen);
        }
    }

    private function reprValue(mixed $v): string
    {
        if (is_string($v)) return "'" . addslashes($v) . "'";
        if (is_bool($v)) return $v ? 'True' : 'False';
        if (is_null($v)) return 'None';
        if ($v instanceof \Stringable) return (string)$v;
        return (string)$v;
    }
}
