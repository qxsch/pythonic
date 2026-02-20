<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python operator module for PHP.
 *
 * Provides callable helpers commonly used as key functions for sorting,
 * filtering, and mapping — modeled after Python's operator module.
 *
 * Usage:
 *   // itemgetter — fetch item(s) by key/index
 *   $getName = Operator::itemgetter('name');
 *   $getName(['name' => 'Alice']); // 'Alice'
 *
 *   $getMulti = Operator::itemgetter('name', 'age');
 *   $getMulti(['name' => 'Alice', 'age' => 30]); // PyTuple('Alice', 30)
 *
 *   // attrgetter — fetch attribute(s) from objects
 *   $getX = Operator::attrgetter('x');
 *   $getX($point); // $point->x
 *
 *   // methodcaller — call a method with optional args
 *   $upper = Operator::methodcaller('upper');
 *   $upper(new PyString('hello')); // 'HELLO'
 *
 *   // Arithmetic / comparison operators as callables
 *   $add = Operator::add();
 *   $add(2, 3); // 5
 */
class Operator
{
    // ─── Item / Attribute / Method accessors ─────────────────

    /**
     * Return a callable that fetches item(s) from an array-like object.
     *
     * Python equivalent: operator.itemgetter(key, ...)
     *
     * Single key → returns the value.
     * Multiple keys → returns a PyTuple of values.
     *
     * @param string|int ...$keys One or more keys to retrieve.
     * @return \Closure
     */
    public static function itemgetter(string|int ...$keys): \Closure
    {
        if (count($keys) === 1) {
            $key = $keys[0];
            return function (mixed $obj) use ($key): mixed {
                if ($obj instanceof \ArrayAccess) {
                    return $obj[$key];
                }
                if (is_array($obj)) {
                    return $obj[$key] ?? null;
                }
                throw new \TypeError("itemgetter: object is not subscriptable");
            };
        }
        return function (mixed $obj) use ($keys): PyTuple {
            $values = [];
            foreach ($keys as $k) {
                if ($obj instanceof \ArrayAccess) {
                    $values[] = $obj[$k];
                } elseif (is_array($obj)) {
                    $values[] = $obj[$k] ?? null;
                } else {
                    throw new \TypeError("itemgetter: object is not subscriptable");
                }
            }
            return new PyTuple($values);
        };
    }

    /**
     * Return a callable that fetches attribute(s) from an object.
     *
     * Python equivalent: operator.attrgetter(attr, ...)
     *
     * Supports dotted notation for nested attributes: 'a.b.c'
     *
     * Single attr → returns the value.
     * Multiple attrs → returns a PyTuple.
     *
     * @param string ...$attrs One or more attribute names.
     * @return \Closure
     */
    public static function attrgetter(string ...$attrs): \Closure
    {
        $resolve = function (object $obj, string $attr): mixed {
            $parts = explode('.', $attr);
            $current = $obj;
            foreach ($parts as $part) {
                if (!is_object($current)) {
                    throw new \TypeError("attrgetter: intermediate value is not an object");
                }
                $current = $current->$part;
            }
            return $current;
        };

        if (count($attrs) === 1) {
            $attr = $attrs[0];
            return function (object $obj) use ($resolve, $attr): mixed {
                return $resolve($obj, $attr);
            };
        }
        return function (object $obj) use ($resolve, $attrs): PyTuple {
            $values = [];
            foreach ($attrs as $a) {
                $values[] = $resolve($obj, $a);
            }
            return new PyTuple($values);
        };
    }

    /**
     * Return a callable that calls a method on an object.
     *
     * Python equivalent: operator.methodcaller(name, *args, **kwargs)
     *
     * @param string $method Method name.
     * @param mixed  ...$args Arguments to pass to the method.
     * @return \Closure
     */
    public static function methodcaller(string $method, mixed ...$args): \Closure
    {
        return function (object $obj) use ($method, $args): mixed {
            return $obj->$method(...$args);
        };
    }

    // ─── Arithmetic operators ────────────────────────────────

    /** Return a+b */
    public static function add(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a + $b;
    }

    /** Return a-b */
    public static function sub(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a - $b;
    }

    /** Return a*b */
    public static function mul(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a * $b;
    }

    /** Return a/b (true division) */
    public static function truediv(): \Closure
    {
        return fn(mixed $a, mixed $b): float => $a / $b;
    }

    /** Return a//b (floor division) */
    public static function floordiv(): \Closure
    {
        return fn(mixed $a, mixed $b): int => (int)floor($a / $b);
    }

    /** Return a%b */
    public static function mod(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a % $b;
    }

    /** Return a**b */
    public static function pow(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a ** $b;
    }

    /** Return -a */
    public static function neg(): \Closure
    {
        return fn(mixed $a): mixed => -$a;
    }

    /** Return +a */
    public static function pos(): \Closure
    {
        return fn(mixed $a): mixed => +$a;
    }

    /** Return abs(a) */
    public static function abs(): \Closure
    {
        return fn(mixed $a): mixed => \abs($a);
    }

    // ─── Comparison operators ────────────────────────────────

    /** Return a < b */
    public static function lt(): \Closure
    {
        return fn(mixed $a, mixed $b): bool => $a < $b;
    }

    /** Return a <= b */
    public static function le(): \Closure
    {
        return fn(mixed $a, mixed $b): bool => $a <= $b;
    }

    /** Return a == b */
    public static function eq(): \Closure
    {
        return fn(mixed $a, mixed $b): bool => $a == $b;
    }

    /** Return a != b */
    public static function ne(): \Closure
    {
        return fn(mixed $a, mixed $b): bool => $a != $b;
    }

    /** Return a >= b */
    public static function ge(): \Closure
    {
        return fn(mixed $a, mixed $b): bool => $a >= $b;
    }

    /** Return a > b */
    public static function gt(): \Closure
    {
        return fn(mixed $a, mixed $b): bool => $a > $b;
    }

    // ─── Logical / Bitwise operators ─────────────────────────

    /** Return a & b */
    public static function and_(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a & $b;
    }

    /** Return a | b */
    public static function or_(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a | $b;
    }

    /** Return a ^ b */
    public static function xor_(): \Closure
    {
        return fn(mixed $a, mixed $b): mixed => $a ^ $b;
    }

    /** Return ~a */
    public static function invert(): \Closure
    {
        return fn(mixed $a): int => ~$a;
    }

    /** Return !a (not) */
    public static function not_(): \Closure
    {
        return fn(mixed $a): bool => !$a;
    }

    /** Return bool(a) (truth value) */
    public static function truth(): \Closure
    {
        return fn(mixed $a): bool => (bool)$a;
    }

    // ─── Sequence / Container operators ──────────────────────

    /**
     * Return a callable that checks containment: val in obj
     *
     * Python equivalent: operator.contains(obj, val)
     *
     * @return \Closure
     */
    public static function contains(): \Closure
    {
        return function (mixed $obj, mixed $val): bool {
            if ($obj instanceof PyList || $obj instanceof PyTuple || $obj instanceof PySet || $obj instanceof PyFrozenSet) {
                return $obj->contains($val);
            }
            if ($obj instanceof PyDict || $obj instanceof PyOrderedDict) {
                return $obj->contains($val);
            }
            if ($obj instanceof PyString) {
                return $obj->contains((string)$val);
            }
            if (is_array($obj)) {
                return in_array($val, $obj, true);
            }
            if (is_string($obj) && is_string($val)) {
                return str_contains($obj, $val);
            }
            throw new \TypeError("contains: object does not support containment test");
        };
    }

    /**
     * Return a callable that concatenates two sequences: a + b
     *
     * Python equivalent: operator.concat(a, b)
     *
     * @return \Closure
     */
    public static function concat(): \Closure
    {
        return function (mixed $a, mixed $b): mixed {
            if ($a instanceof PyString && ($b instanceof PyString || is_string($b))) {
                return $a->concat((string)$b);
            }
            if ($a instanceof PyList) {
                $bArr = ($b instanceof PyList) ? $b->toPhp() : (array)$b;
                return new PyList(array_merge($a->toPhp(), $bArr));
            }
            if (is_string($a) && is_string($b)) {
                return new PyString($a . $b);
            }
            if (is_array($a) && is_array($b)) {
                return new PyList(array_merge($a, $b));
            }
            throw new \TypeError("concat: unsupported operand types");
        };
    }

    /**
     * Return a callable that gets the length of a container.
     *
     * Python equivalent: operator.length_hint(obj)
     *
     * @return \Closure
     */
    public static function length_hint(): \Closure
    {
        return function (mixed $obj): int {
            if ($obj instanceof \Countable) {
                return count($obj);
            }
            if (is_array($obj)) {
                return count($obj);
            }
            if (is_string($obj)) {
                return strlen($obj);
            }
            throw new \TypeError("length_hint: object has no length");
        };
    }

    /**
     * Return obj[key] — callable version of subscript.
     *
     * Python equivalent: operator.getitem(obj, key)
     *
     * @return \Closure
     */
    public static function getitem(): \Closure
    {
        return function (mixed $obj, mixed $key): mixed {
            if ($obj instanceof \ArrayAccess) {
                return $obj[$key];
            }
            if (is_array($obj)) {
                return $obj[$key] ?? null;
            }
            throw new \TypeError("getitem: object is not subscriptable");
        };
    }

    /**
     * Set obj[key] = value — callable version.
     *
     * Python equivalent: operator.setitem(obj, key, value)
     *
     * @return \Closure
     */
    public static function setitem(): \Closure
    {
        return function (mixed &$obj, mixed $key, mixed $value): void {
            if ($obj instanceof \ArrayAccess) {
                $obj[$key] = $value;
            } elseif (is_array($obj)) {
                $obj[$key] = $value;
            } else {
                throw new \TypeError("setitem: object does not support item assignment");
            }
        };
    }

    /**
     * Delete obj[key] — callable version.
     *
     * Python equivalent: operator.delitem(obj, key)
     *
     * @return \Closure
     */
    public static function delitem(): \Closure
    {
        return function (mixed &$obj, mixed $key): void {
            if ($obj instanceof \ArrayAccess) {
                unset($obj[$key]);
            } elseif (is_array($obj)) {
                unset($obj[$key]);
            } else {
                throw new \TypeError("delitem: object does not support item deletion");
            }
        };
    }
}
