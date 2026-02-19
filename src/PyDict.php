<?php

declare(strict_types=1);

namespace QXS\pythonic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python-like dict for PHP.
 *
 * Features:
 *   - Attribute access: $dict->name (magic __get/__set)
 *   - $dict['key'] (ArrayAccess)
 *   - .get(key, default), .keys(), .values(), .items()
 *   - .setdefault(), .update(), .pop(), .popitem()
 *   - .merge() for {**d1, **d2}
 *   - Comprehension: $dict->comp(fn($k,$v) => [$k, $v*2], fn($k,$v) => $v > 0)
 *   - Fluent chaining
 */
class PyDict implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    protected array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        $pairs = [];
        foreach ($this->data as $k => $v) {
            $pairs[] = $this->reprValue($k) . ': ' . $this->reprValue($v);
        }
        return '{' . implode(', ', $pairs) . '}';
    }

    public function __bool(): bool
    {
        return count($this->data) > 0;
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
            throw new \InvalidArgumentException("Dict requires a key");
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

    // ─── Python dict methods ─────────────────────────────────

    /** Get value for key with optional default (no exception). */
    public function get(string|int $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    /** Get keys as PyList. */
    public function keys(): PyList
    {
        return new PyList(array_keys($this->data));
    }

    /** Get values as PyList. */
    public function values(): PyList
    {
        return new PyList(array_values($this->data));
    }

    /** Get [key, value] pairs as PyList of arrays. */
    public function items(): PyList
    {
        $items = [];
        foreach ($this->data as $k => $v) {
            $items[] = [$k, $v];
        }
        return new PyList($items);
    }

    /** Set default: return value if key exists, else set & return default. */
    public function setdefault(string|int $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->data)) {
            $this->data[$key] = $default;
        }
        return $this->data[$key];
    }

    /** Update dict with key-value pairs from another dict/array (mutates). */
    public function update(self|array $other): static
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        $this->data = array_merge($this->data, $otherArr);
        return $this;
    }

    /** Pop key and return its value (with optional default). */
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

    /** Pop and return the last inserted [key, value] pair. */
    public function popitem(): array
    {
        if (empty($this->data)) {
            throw new \OutOfRangeException("KeyError: 'popitem(): dictionary is empty'");
        }
        $key = array_key_last($this->data);
        $value = $this->data[$key];
        unset($this->data[$key]);
        return [$key, $value];
    }

    /** Clear all items. */
    public function clear(): static
    {
        $this->data = [];
        return $this;
    }

    /** Python `key in dict` */
    public function contains(string|int $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function in(string|int $key): bool
    {
        return $this->contains($key);
    }

    // ─── Merge (Python {**d1, **d2}) ─────────────────────────

    /** Merge with other dicts/arrays → new PyDict (does NOT mutate). */
    public function merge(self|array ...$others): static
    {
        $result = $this->data;
        foreach ($others as $other) {
            $otherArr = $other instanceof self ? $other->toPhp() : $other;
            $result = array_merge($result, $otherArr);
        }
        return new static($result);
    }

    // ─── Comprehension / Functional ─────────────────────────

    /**
     * Dict comprehension:
     *   $d->comp(fn($k,$v) => [$k, $v*2])               // transform
     *   $d->comp(fn($k,$v) => [$k, $v], fn($k,$v) => $v > 0) // filter + transform
     *
     * Transform must return [newKey, newValue].
     */
    public function comp(callable $transform, ?callable $filter = null): static
    {
        $result = [];
        foreach ($this->data as $k => $v) {
            if ($filter !== null && !$filter($k, $v)) continue;
            [$newK, $newV] = $transform($k, $v);
            $result[$newK] = $newV;
        }
        return new static($result);
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

    /** Map keys (values preserved). */
    public function mapKeys(callable $fn): static
    {
        $result = [];
        foreach ($this->data as $k => $v) {
            $result[$fn($k, $v)] = $v;
        }
        return new static($result);
    }

    /** Filter entries by predicate. */
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

    /** Apply callback for each entry (for side effects). */
    public function each(callable $fn): static
    {
        foreach ($this->data as $k => $v) {
            $fn($k, $v);
        }
        return $this;
    }

    // ─── Sorting ─────────────────────────────────────────────

    /** Return new dict sorted by keys. */
    public function sortedByKeys(bool $reverse = false): static
    {
        $copy = $this->data;
        if ($reverse) {
            krsort($copy);
        } else {
            ksort($copy);
        }
        return new static($copy);
    }

    /** Return new dict sorted by values. */
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

    // ─── Conversion ──────────────────────────────────────────

    public function toPhp(): array
    {
        return $this->data;
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

    /** Create from keys with a default value (Python dict.fromkeys()). */
    public static function fromkeys(iterable $keys, mixed $value = null): static
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $value;
        }
        return new static($data);
    }

    // ─── Python dunder operators ──────────────────────────────

    /** Python __or__ (3.9+): dict | dict → merge */
    public function __or(self|array $other): static
    {
        return $this->merge($other);
    }

    /** Python __ior__ (3.9+): dict |= dict → in-place update */
    public function __ior(self|array $other): static
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        $this->data = array_merge($this->data, $otherArr);
        return $this;
    }

    /** Python __contains__: key in dict */
    public function __contains(string|int $key): bool
    {
        return $this->contains($key);
    }

    /** Python __eq__: dict == dict */
    public function __eq(self|array $other): bool
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        return $this->data == $otherArr;
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
