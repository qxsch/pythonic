<?php

declare(strict_types=1);

namespace QXS\pythonic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python-like tuple for PHP — an immutable sequence.
 *
 * Features:
 *   - Immutable: no assignment, no append, no remove
 *   - Negative indexing: $tuple[-1]
 *   - Slicing: $tuple->slice(1, -1), $tuple["1:3"]
 *   - Hashable: usable as dict key via hash()
 *   - All read-only Python tuple methods: index, count, contains
 *   - Functional: map, filter → return new PyTuple
 *   - Conversions: toList(), toPhp(), toSet()
 *
 * Usage:
 *   $t = new PyTuple([1, 2, 3]);
 *   $t = py_tuple(1, 2, 3);
 *   $t = Py::tuple(1, 2, 3);
 *   $t[0];         // 1
 *   $t[-1];        // 3
 *   $t["1:3"];     // PyTuple(2, 3)
 *   $t->hash();    // deterministic hash string
 */
class PyTuple implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    /** @var array<int, mixed> */
    private readonly array $data;

    public function __construct(array $items = [])
    {
        $this->data = array_values($items);
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        $items = array_map(fn($v) => $this->reprValue($v), $this->data);
        if (count($this->data) === 1) {
            return '(' . $items[0] . ',)';
        }
        return '(' . implode(', ', $items) . ')';
    }

    public function __bool(): bool
    {
        return count($this->data) > 0;
    }

    public function __len(): int
    {
        return count($this->data);
    }

    // ─── Hash (makes tuples usable as dict keys) ─────────────

    /**
     * Deterministic hash of tuple contents.
     * Like Python tuple.__hash__().
     */
    public function hash(): string
    {
        return md5(serialize($this->data));
    }

    // ─── Indexing (negative index support) ───────────────────

    private function resolveIndex(int $index): int
    {
        $len = count($this->data);
        if ($index < 0) {
            $index += $len;
        }
        if ($index < 0 || $index >= $len) {
            throw new \OutOfRangeException("IndexError: tuple index {$index} out of range for tuple of length {$len}");
        }
        return $index;
    }

    // ─── ArrayAccess (read-only) ─────────────────────────────

    public function offsetExists(mixed $offset): bool
    {
        if (is_string($offset) && static::parseSliceNotation($offset) !== null) {
            return true;
        }
        try {
            $this->resolveIndex((int)$offset);
            return true;
        } catch (\OutOfRangeException) {
            return false;
        }
    }

    /**
     * Get item by index or Python-style slice notation string.
     *
     *   $tuple[0]       // first element
     *   $tuple[-1]      // last element
     *   $tuple["1:3"]   // slice(1, 3)  → PyTuple
     *   $tuple["::2"]   // slice(null, null, 2)  → PyTuple
     *   $tuple["::-1"]  // reversed → PyTuple
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (is_string($offset)) {
            $slice = static::parseSliceNotation($offset);
            if ($slice !== null) {
                [$start, $stop, $step] = $slice;
                return $this->slice($start, $stop, $step ?? 1);
            }
        }
        return $this->data[$this->resolveIndex((int)$offset)];
    }

    /** @throws \RuntimeException Tuples are immutable */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException("TypeError: 'tuple' object does not support item assignment");
    }

    /** @throws \RuntimeException Tuples are immutable */
    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException("TypeError: 'tuple' object does not support item deletion");
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
     * Python-style slice: $tuple->slice(start, stop, step)
     * Returns a new PyTuple (immutable).
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

    // ─── Python tuple methods ────────────────────────────────

    /** Return index of first occurrence (like Python tuple.index()). */
    public function index(mixed $value, int $start = 0, ?int $stop = null): int
    {
        $stop = $stop ?? count($this->data);
        for ($i = $start; $i < $stop; $i++) {
            if ($this->data[$i] === $value) {
                return $i;
            }
        }
        throw new \ValueError("'{$value}' is not in tuple");
    }

    /** Count occurrences of a value (like Python tuple.count()). */
    public function countOf(mixed $value): int
    {
        $c = 0;
        foreach ($this->data as $item) {
            if ($item === $value) $c++;
        }
        return $c;
    }

    // ─── Python 'in' operator ────────────────────────────────

    /** Python `x in tuple` */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->data, true);
    }

    public function in(mixed $value): bool
    {
        return $this->contains($value);
    }

    // ─── Functional (all return new PyTuple) ─────────────────

    /** Map each item, return new tuple. */
    public function map(callable $fn): static
    {
        return new static(array_map($fn, $this->data));
    }

    /** Filter items, return new tuple. */
    public function filter(?callable $fn = null): static
    {
        if ($fn === null) {
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

    // ─── Concatenation ──────────────────────────────────────

    /** Concatenate tuples: $a->concat($b) like Python a + b → new tuple */
    public function concat(self|array $other): static
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        return new static(array_merge($this->data, $otherArr));
    }

    /** Repeat tuple: $tuple->repeat(3) like Python (1, 2) * 3 */
    public function repeat(int $n): static
    {
        $result = [];
        for ($i = 0; $i < $n; $i++) {
            $result = array_merge($result, $this->data);
        }
        return new static($result);
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

    // ─── Enumerate / Zip ─────────────────────────────────────

    /** Python enumerate() — yields [index, value] pairs as a new PyTuple of tuples. */
    public function enumerate(int $start = 0): PyList
    {
        $result = [];
        foreach ($this->data as $i => $v) {
            $result[] = new static([$start + $i, $v]);
        }
        return new PyList($result);
    }

    // ─── Conversion ──────────────────────────────────────────

    public function toPhp(): array
    {
        return $this->data;
    }

    /** Convert to mutable PyList. */
    public function toList(): PyList
    {
        return new PyList($this->data);
    }

    /** Convert to PySet. */
    public function toSet(): PySet
    {
        return new PySet($this->data);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    /** Return a copy (tuples are immutable, so this is the same). */
    public function copy(): static
    {
        return new static($this->data);
    }

    // ─── Python dunder operators ──────────────────────────────

    /** Python __add__: tuple + tuple → concat */
    public function __add(self|array $other): static
    {
        return $this->concat($other);
    }

    /** Python __mul__: tuple * n → repeat */
    public function __mul(int $n): static
    {
        return $this->repeat($n);
    }

    /** Python __contains__: x in tuple */
    public function __contains(mixed $value): bool
    {
        return $this->contains($value);
    }

    /** Python __eq__: tuple == tuple */
    public function __eq(self|array $other): bool
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        return $this->data === $otherArr;
    }

    // ─── Sorting (returns new — immutable) ───────────────────

    /** Return a new sorted tuple (like sorted() on a tuple). */
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

    /** Return a new reversed tuple. */
    public function reversed(): static
    {
        return new static(array_reverse($this->data));
    }

    // ─── String join ─────────────────────────────────────────

    /** Join elements into a string. */
    public function join(string $separator = ''): PyString
    {
        return new PyString(implode($separator, array_map('strval', $this->data)));
    }

    // ─── Internal helpers ────────────────────────────────────

    private function reprValue(mixed $v): string
    {
        if (is_string($v)) return "'" . addslashes($v) . "'";
        if (is_bool($v)) return $v ? 'True' : 'False';
        if (is_null($v)) return 'None';
        if (is_array($v)) return (new PyList($v))->__repr();
        if ($v instanceof \Stringable) return (string)$v;
        if (is_object($v)) return get_class($v) . '(...)';
        return (string)$v;
    }
}
