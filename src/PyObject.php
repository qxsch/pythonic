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
}
