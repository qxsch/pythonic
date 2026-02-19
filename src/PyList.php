<?php

declare(strict_types=1);

namespace QXS\pythonic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python-like list for PHP.
 *
 * Features:
 *   - Negative indexing: $list[-1]
 *   - Slicing: $list->slice(1, -1), $list->slice(0, null, 2)
 *   - Comprehensions: $list->comp(fn($x) => $x**2, fn($x) => $x > 2)
 *   - All Python list methods: append, extend, insert, remove, pop, etc.
 *   - Fluent chaining: $list->filter(...)->map(...)->sorted()
 *   - Spread/unpack: [...$list]
 *   - Functional: map, filter, reduce, any, all, sum, min, max
 */
class PyList implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    protected array $data;

    public function __construct(array $items = [])
    {
        $this->data = array_values($items);
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        $items = array_map(fn($v) => $this->reprValue($v), $this->data);
        return '[' . implode(', ', $items) . ']';
    }

    public function __bool(): bool
    {
        return count($this->data) > 0;
    }

    public function __len(): int
    {
        return count($this->data);
    }

    // ─── Indexing (negative index support) ───────────────────

    private function resolveIndex(int $index): int
    {
        $len = count($this->data);
        if ($index < 0) {
            $index += $len;
        }
        if ($index < 0 || $index >= $len) {
            throw new \OutOfRangeException("Index {$index} out of range for list of length {$len}");
        }
        return $index;
    }

    // ─── ArrayAccess ─────────────────────────────────────────

    public function offsetExists(mixed $offset): bool
    {
        try {
            $this->resolveIndex((int)$offset);
            return true;
        } catch (\OutOfRangeException) {
            return false;
        }
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$this->resolveIndex((int)$offset)];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$this->resolveIndex((int)$offset)] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$this->resolveIndex((int)$offset)]);
        $this->data = array_values($this->data);
    }

    // ─── Countable & IteratorAggregate ───────────────────────

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    // ─── Slicing (Python-style) ─────────────────────────────

    /**
     * Python-style slice: $list->slice(start, stop, step)
     *   $list->slice(1, -1)     // elements 1 through second-to-last
     *   $list->slice(0, null, 2) // every other element
     *   $list->slice(null, null, -1) // reversed
     */
    public function slice(?int $start = null, ?int $stop = null, int $step = 1): static
    {
        $len = count($this->data);

        if ($step === 0) {
            throw new \ValueError('Slice step cannot be zero');
        }

        if ($step > 0) {
            $start = $start === null ? 0 : $this->clampSlice($start, $len);
            $stop = $stop === null ? $len : $this->clampSlice($stop, $len);
        } else {
            $start = $start === null ? $len - 1 : $this->clampSliceReverse($start, $len);
            $stop = $stop === null ? -1 : $this->clampSliceReverse($stop, $len);
        }

        $result = [];
        if ($step > 0) {
            for ($i = $start; $i < $stop; $i += $step) {
                $result[] = $this->data[$i];
            }
        } else {
            for ($i = $start; $i > $stop; $i += $step) {
                $result[] = $this->data[$i];
            }
        }

        return new static($result);
    }

    private function clampSlice(int $idx, int $len): int
    {
        if ($idx < 0) $idx += $len;
        return max(0, min($idx, $len));
    }

    private function clampSliceReverse(int $idx, int $len): int
    {
        if ($idx < 0) $idx += $len;
        return max(-1, min($idx, $len - 1));
    }

    // ─── Python list methods ────────────────────────────────

    /** Append an item (mutates). */
    public function append(mixed $value): static
    {
        $this->data[] = $value;
        return $this;
    }

    /** Extend with items from an iterable (mutates). */
    public function extend(iterable $values): static
    {
        foreach ($values as $v) {
            $this->data[] = $v;
        }
        return $this;
    }

    /** Insert at index (mutates). */
    public function insert(int $index, mixed $value): static
    {
        $index = max(0, min($this->clampSlice($index, count($this->data)), count($this->data)));
        array_splice($this->data, $index, 0, [$value]);
        return $this;
    }

    /** Remove first occurrence of value (mutates). */
    public function remove(mixed $value): static
    {
        $idx = array_search($value, $this->data, true);
        if ($idx === false) {
            throw new \ValueError("PyList.remove(x): x not in list");
        }
        array_splice($this->data, $idx, 1);
        return $this;
    }

    /** Pop item at index (default: last). Returns the removed item. */
    public function pop(int $index = -1): mixed
    {
        $index = $this->resolveIndex($index);
        $value = $this->data[$index];
        array_splice($this->data, $index, 1);
        return $value;
    }

    /** Clear all items (mutates). */
    public function clear(): static
    {
        $this->data = [];
        return $this;
    }

    /** Return index of first occurrence. */
    public function index(mixed $value, int $start = 0, ?int $stop = null): int
    {
        $stop = $stop ?? count($this->data);
        for ($i = $start; $i < $stop; $i++) {
            if ($this->data[$i] === $value) {
                return $i;
            }
        }
        throw new \ValueError("'{$value}' is not in list");
    }

    /** Count occurrences of value. */
    public function countOf(mixed $value): int
    {
        $c = 0;
        foreach ($this->data as $item) {
            if ($item === $value) $c++;
        }
        return $c;
    }

    // ─── Python 'in' operator ────────────────────────────────

    /** Python `x in list` → $list->contains(x) or $list->in(x) */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->data, true);
    }

    public function in(mixed $value): bool
    {
        return $this->contains($value);
    }

    // ─── Sorting / Reversing (return new) ────────────────────

    /** Return a new sorted list (like Python sorted()). */
    public function sorted(?callable $key = null, bool $reverse = false): static
    {
        $copy = $this->data;
        if ($key !== null) {
            usort($copy, function ($a, $b) use ($key, $reverse) {
                $va = $key($a);
                $vb = $key($b);
                $cmp = $va <=> $vb;
                return $reverse ? -$cmp : $cmp;
            });
        } else {
            sort($copy);
            if ($reverse) {
                $copy = array_reverse($copy);
            }
        }
        return new static($copy);
    }

    /** Sort in-place (mutates). */
    public function sort(?callable $key = null, bool $reverse = false): static
    {
        if ($key !== null) {
            usort($this->data, function ($a, $b) use ($key, $reverse) {
                $va = $key($a);
                $vb = $key($b);
                $cmp = $va <=> $vb;
                return $reverse ? -$cmp : $cmp;
            });
        } else {
            sort($this->data);
            if ($reverse) {
                $this->data = array_reverse($this->data);
            }
        }
        return $this;
    }

    /** Return a new reversed list. */
    public function reversed(): static
    {
        return new static(array_reverse($this->data));
    }

    /** Reverse in-place (mutates). */
    public function reverse(): static
    {
        $this->data = array_reverse($this->data);
        return $this;
    }

    // ─── Comprehension / Functional ─────────────────────────

    /**
     * List comprehension:
     *   py([1,2,3,4,5])->comp(fn($x) => $x**2, fn($x) => $x > 2)
     *   // [9, 16, 25]  — filter first, then map
     */
    public function comp(callable $transform, ?callable $filter = null): static
    {
        $result = [];
        foreach ($this->data as $item) {
            if ($filter === null || $filter($item)) {
                $result[] = $transform($item);
            }
        }
        return new static($result);
    }

    /** Map each item, return new list. */
    public function map(callable $fn): static
    {
        return new static(array_map($fn, $this->data));
    }

    /** Filter items, return new list. */
    public function filter(?callable $fn = null): static
    {
        if ($fn === null) {
            // Python filter(None, ...) removes falsy values
            return new static(array_values(array_filter($this->data)));
        }
        return new static(array_values(array_filter($this->data, $fn)));
    }

    /** Reduce to a single value. */
    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        if ($initial === null && count($this->data) > 0) {
            return array_reduce(array_slice($this->data, 1), $fn, $this->data[0]);
        }
        return array_reduce($this->data, $fn, $initial);
    }

    /** Flat map — map then flatten one level. */
    public function flatmap(callable $fn): static
    {
        $result = [];
        foreach ($this->data as $item) {
            $mapped = $fn($item);
            if (is_iterable($mapped)) {
                foreach ($mapped as $sub) {
                    $result[] = $sub;
                }
            } else {
                $result[] = $mapped;
            }
        }
        return new static($result);
    }

    /** Flatten nested lists by depth. */
    public function flatten(int $depth = 1): static
    {
        return new static($this->flattenRecursive($this->data, $depth));
    }

    private function flattenRecursive(array $arr, int $depth): array
    {
        $result = [];
        foreach ($arr as $item) {
            if ($depth > 0 && (is_array($item) || $item instanceof self)) {
                $sub = $item instanceof self ? $item->toPhp() : $item;
                foreach ($this->flattenRecursive($sub, $depth - 1) as $v) {
                    $result[] = $v;
                }
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    // ─── Aggregation ─────────────────────────────────────────

    public function sum(): int|float
    {
        return array_sum($this->data);
    }

    public function min(?callable $key = null): mixed
    {
        if (empty($this->data)) throw new \ValueError("min() arg is an empty sequence");
        if ($key === null) return min($this->data);
        return $this->sorted($key)->first();
    }

    public function max(?callable $key = null): mixed
    {
        if (empty($this->data)) throw new \ValueError("max() arg is an empty sequence");
        if ($key === null) return max($this->data);
        return $this->sorted($key, reverse: true)->first();
    }

    public function any(?callable $fn = null): bool
    {
        foreach ($this->data as $item) {
            if ($fn ? $fn($item) : (bool)$item) return true;
        }
        return false;
    }

    public function all(?callable $fn = null): bool
    {
        foreach ($this->data as $item) {
            if (!($fn ? $fn($item) : (bool)$item)) return false;
        }
        return true;
    }

    // ─── Accessors ───────────────────────────────────────────

    /** First item (or default). */
    public function first(mixed $default = null): mixed
    {
        return $this->data[0] ?? $default;
    }

    /** Last item (or default). */
    public function last(mixed $default = null): mixed
    {
        return empty($this->data) ? $default : $this->data[count($this->data) - 1];
    }

    /** Get unique values (preserves order). */
    public function unique(): static
    {
        $seen = [];
        $result = [];
        foreach ($this->data as $item) {
            $key = serialize($item);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $item;
            }
        }
        return new static($result);
    }

    // ─── Enumerate / Zip ─────────────────────────────────────

    /** Python enumerate() — yields [index, value] pairs. */
    public function enumerate(int $start = 0): static
    {
        $result = [];
        foreach ($this->data as $i => $v) {
            $result[] = [$start + $i, $v];
        }
        return new static($result);
    }

    /** Python zip() — zip with one or more iterables. */
    public function zip(iterable ...$others): static
    {
        $arrays = [
            $this->data,
            ...array_map(fn($o) => is_array($o) ? array_values($o) : ($o instanceof self ? $o->toPhp() : iterator_to_array($o)), $others)
        ];
        $len = min(...array_map('count', $arrays));
        $result = [];
        for ($i = 0; $i < $len; $i++) {
            $tuple = [];
            foreach ($arrays as $arr) {
                $tuple[] = $arr[$i];
            }
            $result[] = $tuple;
        }
        return new static($result);
    }

    // ─── Joining / String conversion ─────────────────────────

    /** Join elements into a string. */
    public function join(string $separator = ''): PyString
    {
        return new PyString(implode($separator, array_map('strval', $this->data)));
    }

    // ─── Grouping ────────────────────────────────────────────

    /** Group by key function → PyDict of PyLists. */
    public function groupby(callable $keyFn): PyDict
    {
        $groups = [];
        foreach ($this->data as $item) {
            $key = (string)$keyFn($item);
            $groups[$key][] = $item;
        }
        return new PyDict(array_map(fn($g) => new static($g), $groups));
    }

    /** Chunk into sublists of given size. */
    public function chunk(int $size): static
    {
        return new static(array_map(fn($c) => new static($c), array_chunk($this->data, $size)));
    }

    // ─── Conversion ──────────────────────────────────────────

    public function toPhp(): array
    {
        return $this->data;
    }

    public function toSet(): PySet
    {
        return new PySet($this->data);
    }

    public function toDict(): PyDict
    {
        // Expects list of [key, value] pairs
        $assoc = [];
        foreach ($this->data as $pair) {
            if (is_array($pair) && count($pair) === 2) {
                $assoc[$pair[0]] = $pair[1];
            }
        }
        return new PyDict($assoc);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    // ─── Concatenation ──────────────────────────────────────

    /** Concatenate lists: $a->concat($b) like Python a + b */
    public function concat(self|array $other): static
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        return new static(array_merge($this->data, $otherArr));
    }

    /** Repeat list: $list->repeat(3) like Python [1,2] * 3 */
    public function repeat(int $n): static
    {
        $result = [];
        for ($i = 0; $i < $n; $i++) {
            $result = array_merge($result, $this->data);
        }
        return new static($result);
    }

    // ─── Utilities ───────────────────────────────────────────

    /** copied — return a shallow copy. */
    public function copy(): static
    {
        return new static($this->data);
    }

    /** Apply callback for each item (for side effects). */
    public function each(callable $fn): static
    {
        foreach ($this->data as $i => $item) {
            $fn($item, $i);
        }
        return $this;
    }

    /** Take first n items. */
    public function take(int $n): static
    {
        return new static(array_slice($this->data, 0, $n));
    }

    /** Drop first n items. */
    public function drop(int $n): static
    {
        return new static(array_slice($this->data, $n));
    }

    /** Take items while predicate is true. */
    public function takewhile(callable $fn): static
    {
        $result = [];
        foreach ($this->data as $item) {
            if (!$fn($item)) break;
            $result[] = $item;
        }
        return new static($result);
    }

    /** Drop items while predicate is true. */
    public function dropwhile(callable $fn): static
    {
        $result = [];
        $dropping = true;
        foreach ($this->data as $item) {
            if ($dropping && $fn($item)) continue;
            $dropping = false;
            $result[] = $item;
        }
        return new static($result);
    }

    // ─── Python dunder operators ──────────────────────────────

    /** Python __add__: list + list → concat */
    public function __add(self|array $other): static
    {
        return $this->concat($other);
    }

    /** Python __mul__: list * n → repeat */
    public function __mul(int $n): static
    {
        return $this->repeat($n);
    }

    /** Python __contains__: x in list */
    public function __contains(mixed $value): bool
    {
        return in_array($value, $this->data, true);
    }

    /** Python __eq__: list == list */
    public function __eq(self|array $other): bool
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        return $this->data === $otherArr;
    }

    // ─── Internal helpers ────────────────────────────────────

    private function reprValue(mixed $v): string
    {
        if (is_string($v)) return "'" . addslashes($v) . "'";
        if (is_bool($v)) return $v ? 'True' : 'False';
        if (is_null($v)) return 'None';
        if (is_array($v)) return (new static($v))->__repr();
        if ($v instanceof \Stringable) return (string)$v;
        if (is_object($v)) return get_class($v) . '(...)';
        return (string)$v;
    }
}
