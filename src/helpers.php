<?php

declare(strict_types=1);

/**
 * Global helper functions — the magic entry points.
 *
 * These make the library feel natural and Pythonic:
 *
 *   py([1, 2, 3])                   → PyList
 *   py(["a" => 1])                  → PyDict
 *   py("hello")                     → PyString
 *   py_range(10)                    → PyRange
 *   py_enumerate([...])             → PyList of [i, val]
 *   py_zip([1,2], [3,4])            → PyList of [a, b]
 *   py_sorted([3,1,2])              → PyList [1,2,3]
 *   py_reversed([1,2,3])            → PyList [3,2,1]
 *   py_map(fn, [...])               → PyList
 *   py_filter(fn, [...])            → PyList
 *   py_sum([1,2,3])                 → 6
 *   py_min([3,1,2])                 → 1
 *   py_max([3,1,2])                 → 3
 *   py_any([...])                   → bool
 *   py_all([...])                   → bool
 *   py_len($obj)                    → int
 *   py_type($val)                   → string
 *   py_print(...)                   → void (Python-style print)
 *   py_with($resource, $fn)         → context manager
 *   py_match($val, $cases)          → pattern matching
 */

use QXS\pythonic\Py;
use QXS\pythonic\PyList;
use QXS\pythonic\PyDict;
use QXS\pythonic\PyString;
use QXS\pythonic\PySet;
use QXS\pythonic\PyRange;
use QXS\pythonic\PyCounter;
use QXS\pythonic\PyDefaultDict;
use QXS\pythonic\PyDeque;
use QXS\pythonic\PyFrozenSet;
use QXS\pythonic\PyPath;
use QXS\pythonic\Itertools;

if (!function_exists('py')) {
    /**
     * The magic function — auto-wraps any PHP value into a Pythonic object.
     *
     *   py("hello world")              → PyString
     *   py([1, 2, 3])                  → PyList
     *   py(["name" => "Alice"])        → PyDict
     *   py([1, 2, 3])->filter(...)     → chaining!
     *
     * @return PyList|PyDict|PyString|PySet|mixed
     */
    function py(mixed $value): mixed
    {
        return Py::of($value);
    }
}

if (!function_exists('py_list')) {
    /** Create a PyList from variadic args: py_list(1, 2, 3) */
    function py_list(mixed ...$items): PyList
    {
        return new PyList($items);
    }
}

if (!function_exists('py_dict')) {
    /** Create a PyDict: py_dict(["a" => 1, "b" => 2]) */
    function py_dict(array $data = []): PyDict
    {
        return new PyDict($data);
    }
}

if (!function_exists('py_str')) {
    /** Create a PyString: py_str("hello") */
    function py_str(string $value = ''): PyString
    {
        return new PyString($value);
    }
}

if (!function_exists('py_set')) {
    /** Create a PySet: py_set([1, 2, 3]) */
    function py_set(iterable $items = []): PySet
    {
        return new PySet($items);
    }
}

if (!function_exists('py_range')) {
    /**
     * Python range():
     *   py_range(10)         → 0..9
     *   py_range(2, 10)      → 2..9
     *   py_range(0, 10, 2)   → 0, 2, 4, 6, 8
     */
    function py_range(int $startOrStop, ?int $stop = null, int $step = 1): PyRange
    {
        return new PyRange($startOrStop, $stop, $step);
    }
}

if (!function_exists('py_enumerate')) {
    /**
     * Python enumerate():
     *   foreach (py_enumerate(["a", "b"]) as [$i, $val]) { ... }
     */
    function py_enumerate(iterable $iterable, int $start = 0): PyList
    {
        return Py::enumerate($iterable, $start);
    }
}

if (!function_exists('py_zip')) {
    /**
     * Python zip():
     *   foreach (py_zip([1,2], ['a','b']) as [$num, $letter]) { ... }
     */
    function py_zip(iterable ...$iterables): PyList
    {
        return Py::zip(...$iterables);
    }
}

if (!function_exists('py_sorted')) {
    /** Python sorted() → new PyList */
    function py_sorted(iterable $iterable, ?callable $key = null, bool $reverse = false): PyList
    {
        return Py::sorted($iterable, $key, $reverse);
    }
}

if (!function_exists('py_reversed')) {
    /** Python reversed() → new PyList */
    function py_reversed(iterable $iterable): PyList
    {
        return Py::reversed($iterable);
    }
}

if (!function_exists('py_map')) {
    /** Python map(fn, iterable, ...) */
    function py_map(callable $fn, iterable ...$iterables): PyList
    {
        return Py::map($fn, ...$iterables);
    }
}

if (!function_exists('py_filter')) {
    /** Python filter(fn, iterable) */
    function py_filter(?callable $fn, iterable $iterable): PyList
    {
        return Py::filter($fn, $iterable);
    }
}

if (!function_exists('py_sum')) {
    /** Python sum() */
    function py_sum(iterable $iterable, int|float $start = 0): int|float
    {
        return Py::sum($iterable, $start);
    }
}

if (!function_exists('py_min')) {
    /** Python min() */
    function py_min(iterable $iterable, ?callable $key = null): mixed
    {
        return Py::min($iterable, $key);
    }
}

if (!function_exists('py_max')) {
    /** Python max() */
    function py_max(iterable $iterable, ?callable $key = null): mixed
    {
        return Py::max($iterable, $key);
    }
}

if (!function_exists('py_any')) {
    /** Python any() */
    function py_any(iterable $iterable): bool
    {
        return Py::any($iterable);
    }
}

if (!function_exists('py_all')) {
    /** Python all() */
    function py_all(iterable $iterable): bool
    {
        return Py::all($iterable);
    }
}

if (!function_exists('py_len')) {
    /** Python len() */
    function py_len(mixed $obj): int
    {
        return Py::len($obj);
    }
}

if (!function_exists('py_type')) {
    /** Python type() */
    function py_type(mixed $value): string
    {
        return Py::type($value);
    }
}

if (!function_exists('py_print')) {
    /** Python print() */
    function py_print(mixed ...$args): void
    {
        Py::print(...$args);
    }
}

if (!function_exists('py_with')) {
    /**
     * Python `with` statement:
     *   py_with(fopen('file.txt', 'r'), fn($f) => fgets($f));
     */
    function py_with(mixed $resource, callable $fn): mixed
    {
        return Py::with($resource, $fn);
    }
}

if (!function_exists('py_match')) {
    /**
     * Python match/case:
     *   py_match($code, [200 => 'OK', 404 => 'Not Found', '_' => 'Unknown'])
     */
    function py_match(mixed $value, array $cases): mixed
    {
        return Py::match($value, $cases);
    }
}

if (!function_exists('py_match_when')) {
    /**
     * Pattern matching with predicates:
     *   py_match_when($age, [
     *       [fn($x) => $x < 13, 'child'],
     *       [fn($x) => $x < 20, 'teenager'],
     *       [null, 'adult'],
     *   ])
     */
    function py_match_when(mixed $value, array $cases): mixed
    {
        return Py::matchWhen($value, $cases);
    }
}

if (!function_exists('py_isinstance')) {
    /** Python isinstance() */
    function py_isinstance(mixed $value, string ...$classes): bool
    {
        return Py::isinstance($value, ...$classes);
    }
}

if (!function_exists('py_hash')) {
    /** Python hash() */
    function py_hash(mixed $value): string
    {
        return Py::hash($value);
    }
}

if (!function_exists('py_abs')) {
    /** Python abs() */
    function py_abs(int|float $x): int|float
    {
        return abs($x);
    }
}

if (!function_exists('py_divmod')) {
    /** Python divmod() */
    function py_divmod(int $a, int $b): array
    {
        return Py::divmod($a, $b);
    }
}

if (!function_exists('py_input')) {
    /** Python input() — read from stdin with optional prompt. */
    function py_input(string $prompt = ''): string
    {
        return Py::input($prompt);
    }
}

// ─── New collection helpers ──────────────────────────────────

if (!function_exists('py_counter')) {
    /** Create a PyCounter: py_counter(['a', 'b', 'a']) */
    function py_counter(iterable|string $elements = []): PyCounter
    {
        return new PyCounter($elements);
    }
}

if (!function_exists('py_defaultdict')) {
    /**
     * Create a PyDefaultDict:
     *   py_defaultdict(fn() => 0)
     *   py_defaultdict(fn() => [], ['key' => [1, 2]])
     */
    function py_defaultdict(callable $factory, array $data = []): PyDefaultDict
    {
        return new PyDefaultDict($factory, $data);
    }
}

if (!function_exists('py_deque')) {
    /**
     * Create a PyDeque:
     *   py_deque([1, 2, 3])
     *   py_deque([1, 2, 3], maxlen: 5)
     */
    function py_deque(iterable $items = [], ?int $maxlen = null): PyDeque
    {
        return new PyDeque($items, $maxlen);
    }
}

if (!function_exists('py_frozenset')) {
    /** Create a PyFrozenSet: py_frozenset([1, 2, 3]) */
    function py_frozenset(iterable $items = []): PyFrozenSet
    {
        return new PyFrozenSet($items);
    }
}

if (!function_exists('py_path')) {
    /** Create a PyPath: py_path('/home/user/file.txt') */
    function py_path(string $path): PyPath
    {
        return new PyPath($path);
    }
}

if (!function_exists('py_itertools')) {
    /**
     * Access itertools (returns class name for static calls):
     *   py_itertools()::chain([1,2], [3,4])
     *   py_itertools()::product([1,2], [3,4])
     *
     * Or use Itertools directly:
     *   Itertools::chain([1,2], [3,4])
     */
    function py_itertools(): string
    {
        return Itertools::class;
    }
}
