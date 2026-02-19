<?php

declare(strict_types=1);

namespace QXS\pythonic;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python-like set for PHP.
 *
 * Features:
 *   - Set operations: union, intersection, difference, symmetric_difference
 *   - Operator-like syntax: $a->union($b), $a->intersect($b)
 *   - Comprehension: $set->comp(fn($x) => $x*2, fn($x) => $x > 0)
 *   - add, remove, discard, pop, clear
 *   - issubset, issuperset, isdisjoint
 */
class PySet implements Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    /** @var array<string, mixed> key = serialized value, value = original value */
    protected array $data = [];

    public function __construct(iterable $items = [])
    {
        foreach ($items as $item) {
            $this->data[$this->hash($item)] = $item;
        }
    }

    private function hash(mixed $item): string
    {
        return serialize($item);
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        if (empty($this->data)) return 'set()';
        $items = array_map(fn($v) => $this->reprValue($v), array_values($this->data));
        return '{' . implode(', ', $items) . '}';
    }

    public function __bool(): bool
    {
        return count($this->data) > 0;
    }

    public function __len(): int
    {
        return count($this->data);
    }

    // ─── Countable & IteratorAggregate ───────────────────────

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->data));
    }

    // ─── Mutation ────────────────────────────────────────────

    public function add(mixed $value): static
    {
        $this->data[$this->hash($value)] = $value;
        return $this;
    }

    /** Remove value; throw if not present. */
    public function remove(mixed $value): static
    {
        $key = $this->hash($value);
        if (!isset($this->data[$key])) {
            throw new \OutOfRangeException("KeyError: value not in set");
        }
        unset($this->data[$key]);
        return $this;
    }

    /** Remove value if present (no error). */
    public function discard(mixed $value): static
    {
        unset($this->data[$this->hash($value)]);
        return $this;
    }

    /** Remove and return an arbitrary element. */
    public function pop(): mixed
    {
        if (empty($this->data)) {
            throw new \OutOfRangeException("pop from an empty set");
        }
        $key = array_key_first($this->data);
        $value = $this->data[$key];
        unset($this->data[$key]);
        return $value;
    }

    public function clear(): static
    {
        $this->data = [];
        return $this;
    }

    // ─── Membership ──────────────────────────────────────────

    public function contains(mixed $value): bool
    {
        return isset($this->data[$this->hash($value)]);
    }

    public function in(mixed $value): bool
    {
        return $this->contains($value);
    }

    // ─── Set operations (return new sets) ────────────────────

    /** A | B → union */
    public function union(self|iterable ...$others): static
    {
        $result = new static(array_values($this->data));
        foreach ($others as $other) {
            foreach ($other as $item) {
                $result->add($item);
            }
        }
        return $result;
    }

    /** A & B → intersection */
    public function intersection(self|iterable ...$others): static
    {
        $result = new static();
        foreach ($this->data as $item) {
            $inAll = true;
            foreach ($others as $other) {
                $otherSet = $other instanceof self ? $other : new self($other);
                if (!$otherSet->contains($item)) {
                    $inAll = false;
                    break;
                }
            }
            if ($inAll) $result->add($item);
        }
        return $result;
    }

    /** A - B → difference */
    public function difference(self|iterable ...$others): static
    {
        $result = new static(array_values($this->data));
        foreach ($others as $other) {
            foreach ($other as $item) {
                $result->discard($item);
            }
        }
        return $result;
    }

    /** A ^ B → symmetric difference */
    public function symmetric_difference(self|iterable $other): static
    {
        $otherSet = $other instanceof self ? $other : new self($other);
        $result = new static();
        foreach ($this->data as $item) {
            if (!$otherSet->contains($item)) $result->add($item);
        }
        foreach ($otherSet as $item) {
            if (!$this->contains($item)) $result->add($item);
        }
        return $result;
    }

    // ─── Set comparisons ─────────────────────────────────────

    public function issubset(self|iterable $other): bool
    {
        $otherSet = $other instanceof self ? $other : new self($other);
        foreach ($this->data as $item) {
            if (!$otherSet->contains($item)) return false;
        }
        return true;
    }

    public function issuperset(self|iterable $other): bool
    {
        $otherSet = $other instanceof self ? $other : new self($other);
        return $otherSet->issubset($this);
    }

    public function isdisjoint(self|iterable $other): bool
    {
        $otherSet = $other instanceof self ? $other : new self($other);
        foreach ($this->data as $item) {
            if ($otherSet->contains($item)) return false;
        }
        return true;
    }

    // ─── Comprehension / Functional ─────────────────────────

    /** Set comprehension: $set->comp(fn($x) => $x*2, fn($x) => $x > 0) */
    public function comp(callable $transform, ?callable $filter = null): static
    {
        $result = new static();
        foreach ($this->data as $item) {
            if ($filter !== null && !$filter($item)) continue;
            $result->add($transform($item));
        }
        return $result;
    }

    public function map(callable $fn): static
    {
        $result = new static();
        foreach ($this->data as $item) {
            $result->add($fn($item));
        }
        return $result;
    }

    public function filter(?callable $fn = null): static
    {
        $result = new static();
        foreach ($this->data as $item) {
            if ($fn === null ? (bool)$item : $fn($item)) {
                $result->add($item);
            }
        }
        return $result;
    }

    // ─── Python dunder operators ──────────────────────────────

    /** Python __or__: set | set → union */
    public function __or(self|iterable ...$others): static
    {
        return $this->union(...$others);
    }

    /** Python __and__: set & set → intersection */
    public function __and(self|iterable ...$others): static
    {
        return $this->intersection(...$others);
    }

    /** Python __sub__: set - set → difference */
    public function __sub(self|iterable ...$others): static
    {
        return $this->difference(...$others);
    }

    /** Python __xor__: set ^ set → symmetric_difference */
    public function __xor(self|iterable $other): static
    {
        return $this->symmetric_difference($other);
    }

    /** Python __contains__: x in set */
    public function __contains(mixed $value): bool
    {
        return $this->contains($value);
    }

    /** Python __eq__: set == set */
    public function __eq(self $other): bool
    {
        if (count($this->data) !== count($other)) return false;
        foreach ($this->data as $item) {
            if (!$other->contains($item)) return false;
        }
        return true;
    }

    // ─── Conversion ──────────────────────────────────────────

    public function toPhp(): array
    {
        return array_values($this->data);
    }

    public function toList(): PyList
    {
        return new PyList(array_values($this->data));
    }

    public function jsonSerialize(): array
    {
        return array_values($this->data);
    }

    public function copy(): static
    {
        return new static(array_values($this->data));
    }

    // ─── Internal helpers ────────────────────────────────────

    private function reprValue(mixed $v): string
    {
        if (is_string($v)) return "'" . addslashes($v) . "'";
        if (is_bool($v)) return $v ? 'True' : 'False';
        if (is_null($v)) return 'None';
        if ($v instanceof \Stringable) return (string)$v;
        return (string)$v;
    }
}
