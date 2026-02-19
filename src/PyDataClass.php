<?php

declare(strict_types=1);

namespace QXS\pythonic;

use JsonSerializable;

/**
 * Python-like dataclass for PHP.
 *
 * Extend this class to get automatic:
 *   - __repr__() → "ClassName(field1=val1, field2=val2)"
 *   - __eq__() → structural equality
 *   - asdict() → PyDict of fields
 *   - astuple() → PyList of values
 *   - copy() with overrides
 *   - JSON serialization
 *
 * Usage:
 *   class User extends PyDataClass {
 *       public function __construct(
 *           public string $name,
 *           public int $age,
 *           public string $email = '',
 *       ) { parent::__construct(); }
 *   }
 *
 *   $u = new User('Alice', 30);
 *   echo $u;           // User(name='Alice', age=30, email='')
 *   $u->asdict();      // PyDict{'name': 'Alice', 'age': 30, 'email': ''}
 *   $u->copy(age: 31); // User(name='Alice', age=31, email='')
 */
abstract class PyDataClass implements JsonSerializable, \Stringable
{
    public function __construct()
    {
        // Subclasses call parent::__construct() after their own promoted params
    }

    // ─── repr ────────────────────────────────────────────────

    public function __repr(): string
    {
        $class = (new \ReflectionClass($this))->getShortName();
        $fields = $this->getFields();
        $pairs = [];
        foreach ($fields as $name => $value) {
            $pairs[] = "{$name}=" . $this->reprValue($value);
        }
        return $class . '(' . implode(', ', $pairs) . ')';
    }

    public function __toString(): string
    {
        return $this->__repr();
    }

    // ─── Equality ────────────────────────────────────────────

    /** Structural equality (like Python dataclass __eq__). */
    public function eq(self $other): bool
    {
        if (get_class($this) !== get_class($other)) return false;
        return $this->getFields() === $other->getFields();
    }

    // ─── Conversion ──────────────────────────────────────────

    /** Convert to PyDict. */
    public function asdict(): PyDict
    {
        return new PyDict($this->getFields());
    }

    /** Convert to PyList of values. */
    public function astuple(): PyList
    {
        return new PyList(array_values($this->getFields()));
    }

    /** Copy with field overrides. */
    public function copy(mixed ...$overrides): static
    {
        $fields = array_merge($this->getFields(), $overrides);
        $ref = new \ReflectionClass(static::class);
        return $ref->newInstanceArgs($fields);
    }

    /** Replace — alias for copy (like dataclasses.replace). */
    public function replace(mixed ...$overrides): static
    {
        return $this->copy(...$overrides);
    }

    public function jsonSerialize(): array
    {
        return $this->getFields();
    }

    // ─── Field introspection ─────────────────────────────────

    /** Get all public property values as an associative array. */
    public function getFields(): array
    {
        $ref = new \ReflectionClass($this);
        $fields = [];
        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) continue;
            $fields[$prop->getName()] = $prop->getValue($this);
        }
        return $fields;
    }

    /** Get field names as PyList. */
    public function fieldNames(): PyList
    {
        return new PyList(array_keys($this->getFields()));
    }

    // ─── Hashing (for use in sets/dicts) ─────────────────────

    public function hash(): string
    {
        return md5(serialize($this->getFields()));
    }

    // ─── Internal helpers ────────────────────────────────────

    private function reprValue(mixed $v): string
    {
        if (is_string($v)) return "'" . addslashes($v) . "'";
        if (is_bool($v)) return $v ? 'True' : 'False';
        if (is_null($v)) return 'None';
        if (is_array($v)) return json_encode($v);
        if ($v instanceof \Stringable) return (string)$v;
        if (is_object($v)) return get_class($v) . '(...)';
        return (string)$v;
    }
}
