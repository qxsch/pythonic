<?php

declare(strict_types=1);

namespace QXS\pythonic;

use Stringable;

/**
 * Base trait for all Pythonic objects.
 * Provides common Python-like behaviors: repr, bool, type, pipe.
 */
trait PyObject
{
    /**
     * Python-like repr() — human-readable representation.
     */
    abstract public function __repr(): string;

    /**
     * Python-like bool() — truthiness.
     */
    abstract public function __bool(): bool;

    /**
     * Python-like len().
     */
    abstract public function __len(): int;

    public function __toString(): string
    {
        return $this->__repr();
    }

    /**
     * Python-like type() — returns class short name.
     */
    public function type(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Pipe the value through a callable.
     *   py([1,2,3])->pipe(fn($l) => $l->sum())  // 6
     */
    public function pipe(callable $fn): mixed
    {
        return $fn($this);
    }

    /**
     * Tap — execute a side-effect without breaking the chain.
     *   py([3,1,2])->tap(fn($l) => print($l))->sorted()
     */
    public function tap(callable $fn): static
    {
        $fn($this);
        return $this;
    }

    /**
     * Python isinstance() equivalent.
     */
    public function isinstance(string ...$classes): bool
    {
        foreach ($classes as $class) {
            if ($this instanceof $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert to plain PHP value.
     */
    abstract public function toPhp(): mixed;

    /**
     * Parse a Python-style slice notation string.
     *
     * Accepts formats like "1:3", "::2", "::-1", "1:", ":5", "1:10:2".
     * Returns [start, stop, step] with nulls for omitted parts,
     * or null if the string is not a valid slice notation.
     */
    protected static function parseSliceNotation(string $offset): ?array
    {
        $offset = trim($offset);
        // Must contain at least one colon to be a slice
        if (!str_contains($offset, ':')) {
            return null;
        }
        // Match slice pattern: optional_int : optional_int (: optional_int)?
        if (!preg_match('/^(-?\d*):(-?\d*)(?::(-?\d*))?$/', $offset, $m)) {
            return null;
        }
        $start = ($m[1] !== '') ? (int)$m[1] : null;
        $stop  = ($m[2] !== '') ? (int)$m[2] : null;
        $step  = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : null;
        return [$start, $stop, $step];
    }
}
