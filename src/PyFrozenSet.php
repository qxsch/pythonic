<?php

declare(strict_types=1);

namespace QXS\pythonic;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python frozenset for PHP.
 *
 * An immutable set. Once created it cannot be modified.
 * Safe to use as a dictionary key (hashable).
 *
 * Usage:
 *   $fs = new PyFrozenSet([1, 2, 3]);
 *   $fs->contains(2);        // true
 *   $fs->union($other);      // new PyFrozenSet
 *   $fs->intersection($other);
 *   $fs->difference($other);
 *   $fs->symmetric_difference($other);
 */
class PyFrozenSet implements Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    /** @var array<string, mixed>  key → value (using toKey for consistent hashing) */
    private array $data = [];

    public function __construct(iterable $items = [])
    {
        foreach ($items as $item) {
            $this->data[$this->toKey($item)] = $item;
        }
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        if (empty($this->data)) return 'frozenset()';
        $items = array_map(fn($v) => $this->reprValue($v), array_values($this->data));
        return 'frozenset({' . implode(', ', $items) . '})';
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
        return array_values($this->data);
    }

    // ─── Hash for using as dict key ──────────────────────────

    /**
     * Similar to Python's frozenset.__hash__.
     * Returns a deterministic string hash of contents.
     */
    public function hash(): string
    {
        $keys = array_keys($this->data);
        sort($keys);
        return md5(implode("\0", $keys));
    }

    // ─── Membership / querying ───────────────────────────────

    public function contains(mixed $value): bool
    {
        return isset($this->data[$this->toKey($value)]);
    }

    public function issubset(self|PySet $other): bool
    {
        foreach ($this->data as $v) {
            if (!$other->contains($v)) return false;
        }
        return true;
    }

    public function issuperset(self|PySet $other): bool
    {
        foreach ($other as $v) {
            if (!$this->contains($v)) return false;
        }
        return true;
    }

    public function isdisjoint(self|PySet $other): bool
    {
        foreach ($this->data as $v) {
            if ($other->contains($v)) return false;
        }
        return true;
    }

    // ─── Set algebra (all return new PyFrozenSet) ────────────

    public function union(self|PySet|array ...$others): static
    {
        $items = array_values($this->data);
        foreach ($others as $other) {
            foreach ($other as $v) {
                $items[] = $v;
            }
        }
        return new static($items);
    }

    public function intersection(self|PySet|array ...$others): static
    {
        $result = [];
        foreach ($this->data as $v) {
            $inAll = true;
            foreach ($others as $other) {
                if (is_array($other)) {
                    if (!in_array($v, $other, true)) { $inAll = false; break; }
                } else {
                    if (!$other->contains($v)) { $inAll = false; break; }
                }
            }
            if ($inAll) $result[] = $v;
        }
        return new static($result);
    }

    public function difference(self|PySet|array ...$others): static
    {
        $result = [];
        foreach ($this->data as $v) {
            $inAny = false;
            foreach ($others as $other) {
                if (is_array($other)) {
                    if (in_array($v, $other, true)) { $inAny = true; break; }
                } else {
                    if ($other->contains($v)) { $inAny = true; break; }
                }
            }
            if (!$inAny) $result[] = $v;
        }
        return new static($result);
    }

    public function symmetric_difference(self|PySet|array $other): static
    {
        $result = [];
        foreach ($this->data as $v) {
            if (is_array($other)) {
                if (!in_array($v, $other, true)) $result[] = $v;
            } else {
                if (!$other->contains($v)) $result[] = $v;
            }
        }
        foreach ($other as $v) {
            if (!$this->contains($v)) $result[] = $v;
        }
        return new static($result);
    }

    // ─── Functional methods ──────────────────────────────────

    /** Apply a mapping function, return new frozenset. */
    public function map(callable $fn): static
    {
        return new static(array_map($fn, array_values($this->data)));
    }

    /** Filter items by predicate, return new frozenset. */
    public function filter(callable $fn): static
    {
        return new static(array_filter(array_values($this->data), $fn));
    }

    /** Convert to PyList. */
    public function toList(): PyList
    {
        return new PyList(array_values($this->data));
    }

    /** Convert to mutable PySet. */
    public function toSet(): PySet
    {
        return new PySet(array_values($this->data));
    }

    /** Return a copy. */
    public function copy(): static
    {
        return new static(array_values($this->data));
    }

    // ─── Comparison ──────────────────────────────────────────

    public function equals(self $other): bool
    {
        if (count($this->data) !== count($other->data)) return false;
        foreach ($this->data as $v) {
            if (!$other->contains($v)) return false;
        }
        return true;
    }

    // ─── Countable / Iterable / JSON ─────────────────────────

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->data));
    }

    public function jsonSerialize(): array
    {
        return array_values($this->data);
    }

    // ─── Internal ────────────────────────────────────────────

    private function toKey(mixed $v): string
    {
        if ($v instanceof self) return 'frozenset:' . $v->hash();
        if (is_object($v)) return 'obj:' . spl_object_id($v);
        return gettype($v) . ':' . serialize($v);
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
