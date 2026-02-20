<?php

declare(strict_types=1);

namespace QXS\pythonic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python collections.OrderedDict for PHP.
 *
 * While PHP arrays preserve insertion order, OrderedDict adds:
 *   - move_to_end($key, last: true)   — reposition a key
 *   - Order-sensitive equality: two OrderedDicts with same keys/values
 *     in different order are NOT equal (unlike PyDict)
 *   - popitem(last: true)             — pop from either end
 *   - reversed()                      — iterate in reverse order
 *
 * Usage:
 *   $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
 *   $od->move_to_end('a');         // a goes last: b=2, c=3, a=1
 *   $od->move_to_end('c', false);  // c goes first: c=3, b=2, a=1
 *   $od->popitem();                // ['a', 1] — last item
 *   $od->popitem(last: false);     // ['c', 3] — first item
 */
class PyOrderedDict implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        if (empty($this->data)) {
            return 'OrderedDict()';
        }
        $pairs = [];
        foreach ($this->data as $k => $v) {
            $pairs[] = '(' . $this->reprValue($k) . ', ' . $this->reprValue($v) . ')';
        }
        return 'OrderedDict([' . implode(', ', $pairs) . '])';
    }

    public function __bool(): bool
    {
        return !empty($this->data);
    }

    public function __len(): int
    {
        return count($this->data);
    }

    // ─── Attribute access (magic) ────────────────────────────

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        throw new \OutOfRangeException("KeyError: '{$name}'");
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function __unset(string $name): void
    {
        unset($this->data[$name]);
    }

    // ─── ArrayAccess ─────────────────────────────────────────

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!array_key_exists($offset, $this->data)) {
            throw new \OutOfRangeException("KeyError: '{$offset}'");
        }
        return $this->data[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            throw new \InvalidArgumentException("OrderedDict requires a key");
        }
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
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

    // ─── OrderedDict-specific methods ────────────────────────

    /**
     * Move an existing key to either end of the ordered dict.
     *
     * Python: od.move_to_end(key, last=True)
     *   last=true  → move to the end (right)
     *   last=false → move to the beginning (left)
     *
     * @throws \OutOfRangeException if key does not exist
     */
    public function move_to_end(string|int $key, bool $last = true): static
    {
        if (!array_key_exists($key, $this->data)) {
            throw new \OutOfRangeException("KeyError: '{$key}'");
        }
        $value = $this->data[$key];
        unset($this->data[$key]);

        if ($last) {
            $this->data[$key] = $value;
        } else {
            $this->data = [$key => $value] + $this->data;
        }
        return $this;
    }

    /**
     * Remove and return a (key, value) pair.
     *
     * Python: od.popitem(last=True)
     *   last=true  → LIFO (pop from end)
     *   last=false → FIFO (pop from beginning)
     *
     * @return array [key, value]
     * @throws \OutOfRangeException if dict is empty
     */
    public function popitem(bool $last = true): array
    {
        if (empty($this->data)) {
            throw new \OutOfRangeException("KeyError: 'popitem(): dictionary is empty'");
        }
        if ($last) {
            $key = array_key_last($this->data);
        } else {
            $key = array_key_first($this->data);
        }
        $value = $this->data[$key];
        unset($this->data[$key]);
        return [$key, $value];
    }

    /**
     * Return a new OrderedDict with keys in reverse order.
     * Python: reversed(od)
     */
    public function reversed(): static
    {
        return new static(array_reverse($this->data, true));
    }

    // ─── Positional access & manipulation ────────────────────

    /**
     * Return the 0-based numeric position of a key.
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->index_of('b'); // 1
     *
     * @throws \OutOfRangeException if key does not exist
     */
    public function index_of(string|int $key): int
    {
        $i = 0;
        foreach ($this->data as $k => $v) {
            if ($k === $key) {
                return $i;
            }
            $i++;
        }
        throw new \OutOfRangeException("KeyError: '{$key}'");
    }

    /**
     * Return the key at a 0-based position. Negative indices count from the end.
     *
     *   $od->key_at(0);   // first key
     *   $od->key_at(-1);  // last key
     *
     * @throws \OutOfRangeException if index is out of range
     */
    public function key_at(int $index): string|int
    {
        $keys = array_keys($this->data);
        $len = count($keys);
        if ($index < 0) {
            $index += $len;
        }
        if ($index < 0 || $index >= $len) {
            throw new \OutOfRangeException("IndexError: index {$index} out of range");
        }
        return $keys[$index];
    }

    /**
     * Return [key, value] at a 0-based position. Negative indices count from the end.
     *
     *   $od->item_at(0);   // [firstKey, firstValue]
     *   $od->item_at(-1);  // [lastKey, lastValue]
     *
     * @return array{0: string|int, 1: mixed}
     * @throws \OutOfRangeException if index is out of range
     */
    public function item_at(int $index): array
    {
        $key = $this->key_at($index);
        return [$key, $this->data[$key]];
    }

    /**
     * Insert a new key-value pair at a specific 0-based position.
     * Negative indices count from the end (-1 = before the last element).
     *
     * If the key already exists it is first removed, then re-inserted at the
     * requested position (i.e. it acts like a "move + update").
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->insert_at(1, 'x', 99);  // a=1, x=99, b=2, c=3
     */
    public function insert_at(int $index, string|int $key, mixed $value): static
    {
        // Remove existing key first (keeps position logic simple)
        $existed = array_key_exists($key, $this->data);
        if ($existed) {
            unset($this->data[$key]);
        }

        $keys = array_keys($this->data);
        $vals = array_values($this->data);
        $len = count($keys);

        if ($index < 0) {
            $index += $len;
        }
        $index = max(0, min($index, $len));

        array_splice($keys, $index, 0, [$key]);
        array_splice($vals, $index, 0, [$value]);

        $this->data = array_combine($keys, $vals);
        return $this;
    }

    /**
     * Insert a new key-value pair immediately before an existing reference key.
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->insert_before('b', 'x', 99);  // a=1, x=99, b=2, c=3
     *
     * @throws \OutOfRangeException if reference key does not exist
     */
    public function insert_before(string|int $ref, string|int $key, mixed $value): static
    {
        return $this->insert_at($this->index_of($ref), $key, $value);
    }

    /**
     * Insert a new key-value pair immediately after an existing reference key.
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->insert_after('a', 'x', 99);  // a=1, x=99, b=2, c=3
     *
     * @throws \OutOfRangeException if reference key does not exist
     */
    public function insert_after(string|int $ref, string|int $key, mixed $value): static
    {
        return $this->insert_at($this->index_of($ref) + 1, $key, $value);
    }

    /**
     * Move an existing key to a specific 0-based position.
     * Negative indices count from the end.
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->move_to('c', 0);  // c=3, a=1, b=2
     *
     * @throws \OutOfRangeException if key does not exist
     */
    public function move_to(string|int $key, int $index): static
    {
        if (!array_key_exists($key, $this->data)) {
            throw new \OutOfRangeException("KeyError: '{$key}'");
        }
        $value = $this->data[$key];
        unset($this->data[$key]);

        $keys = array_keys($this->data);
        $vals = array_values($this->data);
        $len = count($keys);

        if ($index < 0) {
            $index += $len;
        }
        $index = max(0, min($index, $len));

        array_splice($keys, $index, 0, [$key]);
        array_splice($vals, $index, 0, [$value]);

        $this->data = array_combine($keys, $vals);
        return $this;
    }

    /**
     * Move an existing key to the position immediately before a reference key.
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->move_before('c', 'a');  // c=3, a=1, b=2
     *
     * @throws \OutOfRangeException if either key does not exist
     */
    public function move_before(string|int $key, string|int $ref): static
    {
        if (!array_key_exists($key, $this->data)) {
            throw new \OutOfRangeException("KeyError: '{$key}'");
        }
        $value = $this->data[$key];
        unset($this->data[$key]);
        $refIndex = $this->index_of($ref);
        return $this->insert_at($refIndex, $key, $value);
    }

    /**
     * Move an existing key to the position immediately after a reference key.
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->move_after('a', 'c');  // b=2, c=3, a=1
     *
     * @throws \OutOfRangeException if either key does not exist
     */
    public function move_after(string|int $key, string|int $ref): static
    {
        if (!array_key_exists($key, $this->data)) {
            throw new \OutOfRangeException("KeyError: '{$key}'");
        }
        $value = $this->data[$key];
        unset($this->data[$key]);
        $refIndex = $this->index_of($ref);
        return $this->insert_at($refIndex + 1, $key, $value);
    }

    /**
     * Swap the positions of two existing keys (values stay with their keys).
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->swap('a', 'c');  // c=3, b=2, a=1
     *
     * @throws \OutOfRangeException if either key does not exist
     */
    public function swap(string|int $key1, string|int $key2): static
    {
        if (!array_key_exists($key1, $this->data)) {
            throw new \OutOfRangeException("KeyError: '{$key1}'");
        }
        if (!array_key_exists($key2, $this->data)) {
            throw new \OutOfRangeException("KeyError: '{$key2}'");
        }
        if ($key1 === $key2) {
            return $this;
        }
        $keys = array_keys($this->data);
        $i1 = array_search($key1, $keys, true);
        $i2 = array_search($key2, $keys, true);
        $keys[$i1] = $key2;
        $keys[$i2] = $key1;

        $newData = [];
        foreach ($keys as $k) {
            $newData[$k] = $this->data[$k];
        }
        $this->data = $newData;
        return $this;
    }

    /**
     * Reorder entries to match the given key sequence.
     * All current keys must appear exactly once in $keys.
     *
     *   $od = new PyOrderedDict(['a'=>1,'b'=>2,'c'=>3]);
     *   $od->reorder(['c','a','b']);  // c=3, a=1, b=2
     *
     * @param array<string|int> $keys
     * @throws \InvalidArgumentException if key set doesn't match
     */
    public function reorder(array $keys): static
    {
        $currentKeys = array_keys($this->data);
        $sortedCurrent = $currentKeys;
        $sortedNew = $keys;
        sort($sortedCurrent);
        sort($sortedNew);
        if ($sortedCurrent !== $sortedNew) {
            throw new \InvalidArgumentException("reorder(): key set does not match");
        }
        $newData = [];
        foreach ($keys as $k) {
            $newData[$k] = $this->data[$k];
        }
        $this->data = $newData;
        return $this;
    }

    // ─── Standard dict methods ───────────────────────────────

    public function get(string|int $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function keys(): PyList
    {
        return new PyList(array_keys($this->data));
    }

    public function values(): PyList
    {
        return new PyList(array_values($this->data));
    }

    public function items(): PyList
    {
        $items = [];
        foreach ($this->data as $k => $v) {
            $items[] = [$k, $v];
        }
        return new PyList($items);
    }

    public function setdefault(string|int $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = $default;
        }
        return $this->data[$key];
    }

    public function update(self|PyDict|array $other): static
    {
        $otherArr = ($other instanceof self || $other instanceof PyDict) ? $other->toPhp() : $other;
        foreach ($otherArr as $k => $v) {
            $this->data[$k] = $v;
        }
        return $this;
    }

    public function pop(string|int $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->data)) {
            $value = $this->data[$key];
            unset($this->data[$key]);
            return $value;
        }
        if (func_num_args() >= 2) {
            return $default;
        }
        throw new \OutOfRangeException("KeyError: '{$key}'");
    }

    public function clear(): static
    {
        $this->data = [];
        return $this;
    }

    public function contains(string|int $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function in(string|int $key): bool
    {
        return $this->contains($key);
    }

    public function merge(self|PyDict|array ...$others): static
    {
        $result = $this->data;
        foreach ($others as $other) {
            $arr = ($other instanceof self || $other instanceof PyDict) ? $other->toPhp() : $other;
            foreach ($arr as $k => $v) {
                $result[$k] = $v;
            }
        }
        return new static($result);
    }

    // ─── Sorting ─────────────────────────────────────────────

    /** Return new OrderedDict sorted by keys. */
    public function sortedByKeys(bool $reverse = false): static
    {
        $copy = $this->data;
        if ($reverse) krsort($copy);
        else ksort($copy);
        return new static($copy);
    }

    /** Return new OrderedDict sorted by values. */
    public function sortedByValues(?callable $key = null, bool $reverse = false): static
    {
        $copy = $this->data;
        if ($key !== null) {
            uasort($copy, function ($a, $b) use ($key, $reverse) {
                $cmp = $key($a) <=> $key($b);
                return $reverse ? -$cmp : $cmp;
            });
        } else {
            $reverse ? arsort($copy) : asort($copy);
        }
        return new static($copy);
    }

    /** Map values (keys preserved). */
    public function mapValues(callable $fn): static
    {
        $result = [];
        foreach ($this->data as $k => $v) {
            $result[$k] = $fn($v, $k);
        }
        return new static($result);
    }

    /** Filter by predicate. */
    public function filter(callable $fn): static
    {
        $result = [];
        foreach ($this->data as $k => $v) {
            if ($fn($k, $v)) {
                $result[$k] = $v;
            }
        }
        return new static($result);
    }

    // ─── Equality (order-sensitive!) ─────────────────────────

    /**
     * Order-sensitive equality. Unlike PyDict, two OrderedDicts
     * with the same keys/values in DIFFERENT order are NOT equal.
     */
    public function __eq(self|array $other): bool
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        // Check same keys in same order AND same values
        return array_keys($this->data) === array_keys($otherArr)
            && array_values($this->data) === array_values($otherArr);
    }

    // ─── Conversion ──────────────────────────────────────────

    public function toPhp(): array
    {
        return $this->data;
    }

    /** Convert to a regular PyDict. */
    public function toDict(): PyDict
    {
        return new PyDict($this->data);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    public function copy(): static
    {
        return new static($this->data);
    }

    // ─── Static constructors ─────────────────────────────────

    /** Create from keys with a default value. */
    public static function fromkeys(iterable $keys, mixed $value = null): static
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $value;
        }
        return new static($data);
    }

    // ─── Internal helpers ────────────────────────────────────

    private function reprValue(mixed $v): string
    {
        if (is_string($v)) return "'" . addslashes($v) . "'";
        if (is_bool($v)) return $v ? 'True' : 'False';
        if (is_null($v)) return 'None';
        if (is_int($v) || is_float($v)) return (string)$v;
        if ($v instanceof \Stringable) return (string)$v;
        if (is_array($v)) return json_encode($v);
        return (string)$v;
    }
}
