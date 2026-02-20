<?php

declare(strict_types=1);

namespace QXS\pythonic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python collections.ChainMap for PHP.
 *
 * A ChainMap groups multiple dicts (maps) together to create a single,
 * updateable view. Lookups search the underlying mappings successively
 * until a key is found. Writes, updates, and deletions only affect the
 * first mapping.
 *
 * Usage:
 *   $defaults = ['color' => 'red', 'size' => 'medium'];
 *   $overrides = ['color' => 'blue'];
 *   $cm = new PyChainMap($overrides, $defaults);
 *   $cm['color'];          // 'blue'  (found in first map)
 *   $cm['size'];           // 'medium' (falls through to second)
 *
 *   $cm->new_child(['color' => 'green']);   // new layer on top
 *   $cm->parents;                           // ChainMap without first map
 */
class PyChainMap implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    /**
     * The list of mappings (first map is the "active" one for writes).
     * @var PyDict[]
     */
    public array $maps;

    /**
     * @param array ...$maps One or more associative arrays (or PyDicts). If none given, starts with one empty map.
     */
    public function __construct(PyDict|array ...$maps)
    {
        if (empty($maps)) {
            $this->maps = [new PyDict()];
        } else {
            $this->maps = array_map(
                fn($m) => $m instanceof PyDict ? $m : new PyDict($m),
                $maps
            );
        }
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        $parts = [];
        foreach ($this->maps as $map) {
            $parts[] = $map->__repr();
        }
        return 'ChainMap(' . implode(', ', $parts) . ')';
    }

    public function __bool(): bool
    {
        return $this->count() > 0;
    }

    public function __len(): int
    {
        return $this->count();
    }

    // ─── Key lookup (searches all maps) ──────────────────────

    /**
     * Get a value by key, searching all maps in order.
     * Throws KeyError if not found in any map.
     */
    public function offsetGet(mixed $offset): mixed
    {
        foreach ($this->maps as $map) {
            if ($map->contains($offset)) {
                return $map[$offset];
            }
        }
        throw new KeyError((string)$offset);
    }

    /**
     * Writes only go to the first map.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            throw new \InvalidArgumentException("ChainMap requires a key");
        }
        $this->maps[0][$offset] = $value;
    }

    /**
     * Deletes only from the first map.
     * Raises KeyError if the key is not in the first map.
     */
    public function offsetUnset(mixed $offset): void
    {
        if (!$this->maps[0]->contains($offset)) {
            throw new KeyError("Key '{$offset}' not found in the first mapping");
        }
        unset($this->maps[0][$offset]);
    }

    /**
     * Check if key exists in any map.
     */
    public function offsetExists(mixed $offset): bool
    {
        foreach ($this->maps as $map) {
            if ($map->contains($offset)) {
                return true;
            }
        }
        return false;
    }

    // ─── Magic property access ───────────────────────────────

    public function __get(string $name): mixed
    {
        if ($name === 'parents') {
            return $this->parents();
        }
        // Lookup through maps
        foreach ($this->maps as $map) {
            if ($map->contains($name)) {
                return $map[$name];
            }
        }
        throw new KeyError($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->maps[0][$name] = $value;
    }

    public function __isset(string $name): bool
    {
        if ($name === 'parents') return true;
        return $this->contains($name);
    }

    // ─── Countable & IteratorAggregate ───────────────────────

    /**
     * Count of unique keys across all maps.
     */
    public function count(): int
    {
        return count($this->allKeys());
    }

    /**
     * Iterate over the merged view (first-map-wins order).
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toPhp());
    }

    // ─── Python ChainMap methods ─────────────────────────────

    /**
     * new_child(m=None) — Return a new ChainMap with a new map followed by all
     * previous maps. If m is specified, it becomes the new child map.
     */
    public function new_child(PyDict|array|null $m = null): static
    {
        $child = $m instanceof PyDict ? $m : new PyDict($m ?? []);
        return new static($child, ...$this->maps);
    }

    /**
     * parents — Property-like method returning a new ChainMap containing all
     * maps except the first. Useful for skipping the first map in searches.
     */
    public function parents(): static
    {
        if (count($this->maps) <= 1) {
            return new static();
        }
        return new static(...array_slice($this->maps, 1));
    }

    // ─── Dict-like methods ───────────────────────────────────

    /** Get value for key with optional default (no exception). */
    public function get(string|int $key, mixed $default = null): mixed
    {
        foreach ($this->maps as $map) {
            if ($map->contains($key)) {
                return $map[$key];
            }
        }
        return $default;
    }

    /** Check if key exists in any map. */
    public function contains(string|int $key): bool
    {
        foreach ($this->maps as $map) {
            if ($map->contains($key)) {
                return true;
            }
        }
        return false;
    }

    public function in(string|int $key): bool
    {
        return $this->contains($key);
    }

    /** Get all unique keys as PyList (first-map-wins). */
    public function keys(): PyList
    {
        return new PyList($this->allKeys());
    }

    /** Get all values as PyList (first-map-wins for duplicate keys). */
    public function values(): PyList
    {
        $merged = $this->toPhp();
        return new PyList(array_values($merged));
    }

    /** Get [key, value] pairs as PyList. */
    public function items(): PyList
    {
        $items = [];
        foreach ($this->toPhp() as $k => $v) {
            $items[] = [$k, $v];
        }
        return new PyList($items);
    }

    /** Pop key from the first mapping. */
    public function pop(string|int $key, mixed $default = null): mixed
    {
        if ($this->maps[0]->contains($key)) {
            return $this->maps[0]->pop($key);
        }
        if (func_num_args() >= 2) {
            return $default;
        }
        throw new KeyError((string)$key);
    }

    /** Clear the first mapping. */
    public function clear(): static
    {
        $this->maps[0]->clear();
        return $this;
    }

    // ─── Dunder operators ────────────────────────────────────

    /** Python __contains__: key in chainmap */
    public function __contains(string|int $key): bool
    {
        return $this->contains($key);
    }

    /** Python __eq__ */
    public function __eq(self|array $other): bool
    {
        $otherArr = $other instanceof self ? $other->toPhp() : $other;
        return $this->toPhp() == $otherArr;
    }

    /** Python __or__: chainmap | dict → new ChainMap with merged first map */
    public function __or(self|PyDict|array $other): static
    {
        $merged = $this->toPhp();
        $otherArr = match (true) {
            $other instanceof self => $other->toPhp(),
            $other instanceof PyDict => $other->toPhp(),
            default => $other,
        };
        return new static(array_merge($merged, $otherArr));
    }

    // ─── Conversion ──────────────────────────────────────────

    /**
     * Flatten all maps into a single PHP array (first-map-wins).
     */
    public function toPhp(): array
    {
        $result = [];
        // Iterate in reverse so first map overwrites later maps
        for ($i = count($this->maps) - 1; $i >= 0; $i--) {
            foreach ($this->maps[$i]->toPhp() as $k => $v) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    public function jsonSerialize(): array
    {
        return $this->toPhp();
    }

    public function copy(): static
    {
        $copies = array_map(fn(PyDict $m) => $m->copy(), $this->maps);
        return new static(...$copies);
    }

    /** Convert the merged view to a PyDict. */
    public function toDict(): PyDict
    {
        return new PyDict($this->toPhp());
    }

    // ─── Internal helpers ────────────────────────────────────

    /**
     * Get all unique keys across maps (first-map-wins).
     */
    private function allKeys(): array
    {
        $seen = [];
        foreach ($this->maps as $map) {
            foreach ($map->keys()->toPhp() as $key) {
                if (!array_key_exists($key, $seen)) {
                    $seen[$key] = true;
                }
            }
        }
        return array_keys($seen);
    }
}
