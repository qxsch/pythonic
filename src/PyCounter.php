<?php

declare(strict_types=1);

namespace QXS\pythonic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python collections.Counter for PHP.
 *
 * A dict subclass for counting hashable objects.
 *
 * Usage:
 *   $c = new PyCounter(['a', 'b', 'a', 'c', 'a', 'b']);
 *   $c['a']              // 3
 *   $c->most_common(2)   // [['a', 3], ['b', 2]]
 *   $c->elements()       // ['a', 'a', 'a', 'b', 'b', 'c']
 *
 * Arithmetic:
 *   $c1->add($c2)        // counter addition
 *   $c1->subtract($c2)   // counter subtraction
 *   $c1->intersect($c2)  // min of counts
 *   $c1->union($c2)      // max of counts
 */
class PyCounter implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    /** @var array<string|int, int> */
    protected array $data = [];

    /**
     * @param iterable|string $elements Items to count. Strings are split into chars.
     */
    public function __construct(iterable|string $elements = [])
    {
        if (is_string($elements)) {
            $elements = mb_str_split($elements);
        }
        foreach ($elements as $item) {
            $key = $this->toKey($item);
            $this->data[$key] = ($this->data[$key] ?? 0) + 1;
        }
    }

    /**
     * Create from an associative array of counts.
     */
    public static function fromMapping(array $mapping): static
    {
        $counter = new static();
        $counter->data = $mapping;
        return $counter;
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        $pairs = [];
        foreach ($this->most_common() as [$key, $count]) {
            $pairs[] = $this->reprValue($key) . ': ' . $count;
        }
        return 'Counter({' . implode(', ', $pairs) . '})';
    }

    public function __bool(): bool
    {
        return !empty($this->data);
    }

    public function __len(): int
    {
        // Total count of all elements
        return (int)array_sum($this->data);
    }

    public function toPhp(): array
    {
        return $this->data;
    }

    // ─── Core Counter methods ────────────────────────────────

    /**
     * most_common(n=null) — list of (element, count) pairs, most common first.
     */
    public function most_common(?int $n = null): PyList
    {
        $items = $this->data;
        arsort($items);
        $result = [];
        foreach ($items as $key => $count) {
            $result[] = [$key, $count];
            if ($n !== null && count($result) >= $n) break;
        }
        return new PyList($result);
    }

    /**
     * elements() — iterator over elements repeating each count times.
     * Elements with count < 1 are ignored.
     */
    public function elements(): PyList
    {
        $result = [];
        foreach ($this->data as $key => $count) {
            for ($i = 0; $i < $count; $i++) {
                $result[] = $key;
            }
        }
        return new PyList($result);
    }

    /**
     * update(iterable) — add counts from another iterable or Counter.
     */
    public function update(iterable|string $elements): static
    {
        if ($elements instanceof self) {
            foreach ($elements->data as $key => $count) {
                $this->data[$key] = ($this->data[$key] ?? 0) + $count;
            }
        } else {
            if (is_string($elements)) {
                $elements = mb_str_split($elements);
            }
            foreach ($elements as $item) {
                $key = $this->toKey($item);
                $this->data[$key] = ($this->data[$key] ?? 0) + 1;
            }
        }
        return $this;
    }

    /**
     * subtract(iterable) — subtract counts (can go negative).
     */
    public function subtract(iterable|string $elements): static
    {
        if ($elements instanceof self) {
            foreach ($elements->data as $key => $count) {
                $this->data[$key] = ($this->data[$key] ?? 0) - $count;
            }
        } else {
            if (is_string($elements)) {
                $elements = mb_str_split($elements);
            }
            foreach ($elements as $item) {
                $key = $this->toKey($item);
                $this->data[$key] = ($this->data[$key] ?? 0) - 1;
            }
        }
        return $this;
    }

    /**
     * total() — sum of all counts.
     */
    public function total(): int
    {
        return (int)array_sum($this->data);
    }

    /**
     * clear() — reset all counts.
     */
    public function clear(): static
    {
        $this->data = [];
        return $this;
    }

    // ─── Arithmetic (returning new Counters) ─────────────────

    /**
     * Counter addition: c1 + c2 (add counts, keep only positive).
     */
    public function add(self $other): static
    {
        $result = new static();
        $allKeys = array_unique(array_merge(array_keys($this->data), array_keys($other->data)));
        foreach ($allKeys as $key) {
            $count = ($this->data[$key] ?? 0) + ($other->data[$key] ?? 0);
            if ($count > 0) {
                $result->data[$key] = $count;
            }
        }
        return $result;
    }

    /**
     * Counter subtraction: c1 - c2 (subtract counts, keep only positive).
     */
    public function sub(self $other): static
    {
        $result = new static();
        $allKeys = array_unique(array_merge(array_keys($this->data), array_keys($other->data)));
        foreach ($allKeys as $key) {
            $count = ($this->data[$key] ?? 0) - ($other->data[$key] ?? 0);
            if ($count > 0) {
                $result->data[$key] = $count;
            }
        }
        return $result;
    }

    /**
     * Intersection: min(c1[x], c2[x]) — keep only positive.
     */
    public function intersect(self $other): static
    {
        $result = new static();
        foreach ($this->data as $key => $count) {
            if (isset($other->data[$key])) {
                $minCount = min($count, $other->data[$key]);
                if ($minCount > 0) {
                    $result->data[$key] = $minCount;
                }
            }
        }
        return $result;
    }

    /**
     * Union: max(c1[x], c2[x]).
     */
    public function union(self $other): static
    {
        $result = new static();
        $allKeys = array_unique(array_merge(array_keys($this->data), array_keys($other->data)));
        foreach ($allKeys as $key) {
            $maxCount = max($this->data[$key] ?? 0, $other->data[$key] ?? 0);
            if ($maxCount > 0) {
                $result->data[$key] = $maxCount;
            }
        }
        return $result;
    }

    // ─── Dict-like accessors ─────────────────────────────────

    /**
     * Get count for a key (returns 0 for missing keys, like Python Counter).
     */
    public function get(string|int $key): int
    {
        return $this->data[$key] ?? 0;
    }

    /**
     * Get all unique keys.
     */
    public function keys(): PyList
    {
        return new PyList(array_keys($this->data));
    }

    /**
     * Get all counts.
     */
    public function values(): PyList
    {
        return new PyList(array_values($this->data));
    }

    /**
     * Get [key, count] pairs.
     */
    public function items(): PyList
    {
        $result = [];
        foreach ($this->data as $k => $v) {
            $result[] = [$k, $v];
        }
        return new PyList($result);
    }

    /**
     * Does the counter contain this key?
     */
    public function contains(string|int $key): bool
    {
        return isset($this->data[$key]) && $this->data[$key] > 0;
    }

    // ─── ArrayAccess ─────────────────────────────────────────

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): int
    {
        return $this->data[$offset] ?? 0; // Python Counter returns 0 for missing
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = (int)$value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
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

    // ─── Python dunder operators ──────────────────────────────

    /** Python __add__: Counter + Counter */
    public function __add(self $other): static
    {
        return $this->add($other);
    }

    /** Python __sub__: Counter - Counter */
    public function __sub(self $other): static
    {
        return $this->sub($other);
    }

    /** Python __and__: Counter & Counter → min of counts */
    public function __and(self $other): static
    {
        return $this->intersect($other);
    }

    /** Python __or__: Counter | Counter → max of counts */
    public function __or(self $other): static
    {
        return $this->union($other);
    }

    /** Python __eq__: Counter == Counter */
    public function __eq(self $other): bool
    {
        return $this->data == $other->toPhp();
    }

    /** Python __contains__: key in Counter */
    public function __contains(string|int $key): bool
    {
        return isset($this->data[$key]) && $this->data[$key] > 0;
    }

    // ─── Internal ────────────────────────────────────────────

    private function toKey(mixed $item): string|int
    {
        if (is_string($item) || is_int($item)) return $item;
        return serialize($item);
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
