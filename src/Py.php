<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Main static utility class — the Swiss-army knife.
 *
 * Provides Python built-in functions as static methods:
 *   Py::len($obj), Py::sorted($iterable), Py::reversed($iterable),
 *   Py::enumerate($iterable), Py::zip(...$iterables),
 *   Py::range(10), Py::sum($iterable), Py::min($iterable), Py::max($iterable),
 *   Py::any($iterable), Py::all($iterable), Py::map($fn, $iterable),
 *   Py::filter($fn, $iterable), Py::print(...$args),
 *   Py::type($val), Py::isinstance($val, ...$types),
 *   Py::with($resource, $fn) — context manager,
 *   Py::match($value, $cases) — structural pattern matching
 */
class Py
{
    // ─── Type wrapping (auto-detect) ─────────────────────────

    /**
     * Auto-wrap a PHP value into a Pythonic object.
     *   Py::of("hello")              → PyString
     *   Py::of([1, 2, 3])            → PyList
     *   Py::of(["a" => 1, "b" => 2]) → PyDict
     *   Py::of(PyList|PyDict|...)     → passthrough
     */
    public static function of(mixed $value): mixed
    {
        if ($value instanceof PyList || $value instanceof PyDict ||
            $value instanceof PyString || $value instanceof PySet ||
            $value instanceof PyRange || $value instanceof PyCounter ||
            $value instanceof PyDefaultDict || $value instanceof PyDeque ||
            $value instanceof PyFrozenSet || $value instanceof PyPath ||
            $value instanceof PyTuple || $value instanceof PyJson ||
            $value instanceof PyOrderedDict || $value instanceof PyDateTime ||
            $value instanceof PyTimeDelta) {
            return $value;
        }
        if (is_string($value)) {
            return new PyString($value);
        }
        if (is_array($value)) {
            if (empty($value)) return new PyList();
            // Check if sequential (list) or associative (dict)
            if (array_is_list($value)) {
                return new PyList($value);
            }
            return new PyDict($value);
        }
        return $value;
    }

    // ─── Python built-in functions ───────────────────────────

    /** len() */
    public static function len(mixed $obj): int
    {
        if ($obj instanceof \Countable) return count($obj);
        if (is_array($obj)) return count($obj);
        if (is_string($obj)) return mb_strlen($obj);
        if (method_exists($obj, '__len')) return $obj->__len();
        throw new \TypeError("object of type '" . get_debug_type($obj) . "' has no len()");
    }

    /** sorted() — return a new PyList. */
    public static function sorted(iterable $iterable, ?callable $key = null, bool $reverse = false): PyList
    {
        $arr = self::toArray($iterable);
        $list = new PyList($arr);
        return $list->sorted($key, $reverse);
    }

    /** reversed() */
    public static function reversed(iterable $iterable): PyList
    {
        return new PyList(array_reverse(self::toArray($iterable)));
    }

    /** enumerate() — yields [index, value] pairs. */
    public static function enumerate(iterable $iterable, int $start = 0): PyList
    {
        $result = [];
        $i = $start;
        foreach ($iterable as $v) {
            $result[] = [$i++, $v];
        }
        return new PyList($result);
    }

    /** zip() — zip multiple iterables together. */
    public static function zip(iterable ...$iterables): PyList
    {
        $arrays = array_map(fn($it) => self::toArray($it), $iterables);
        if (empty($arrays)) return new PyList();
        $len = min(...array_map('count', $arrays));
        $result = [];
        for ($i = 0; $i < $len; $i++) {
            $tuple = [];
            foreach ($arrays as $arr) {
                $tuple[] = $arr[$i];
            }
            $result[] = $tuple;
        }
        return new PyList($result);
    }

    /** range() */
    public static function range(int $startOrStop, ?int $stop = null, int $step = 1): PyRange
    {
        return new PyRange($startOrStop, $stop, $step);
    }

    /** sum() */
    public static function sum(iterable $iterable, int|float $start = 0): int|float
    {
        $total = $start;
        foreach ($iterable as $v) {
            $total += $v;
        }
        return $total;
    }

    /** min() */
    public static function min(iterable $iterable, ?callable $key = null): mixed
    {
        $arr = self::toArray($iterable);
        if (empty($arr)) throw new \ValueError("min() arg is an empty sequence");
        if ($key === null) return min($arr);
        return (new PyList($arr))->sorted($key)->first();
    }

    /** max() */
    public static function max(iterable $iterable, ?callable $key = null): mixed
    {
        $arr = self::toArray($iterable);
        if (empty($arr)) throw new \ValueError("max() arg is an empty sequence");
        if ($key === null) return max($arr);
        return (new PyList($arr))->sorted($key, reverse: true)->first();
    }

    /** any() */
    public static function any(iterable $iterable): bool
    {
        foreach ($iterable as $v) {
            if ((bool)$v) return true;
        }
        return false;
    }

    /** all() */
    public static function all(iterable $iterable): bool
    {
        foreach ($iterable as $v) {
            if (!(bool)$v) return false;
        }
        return true;
    }

    /** map() */
    public static function map(callable $fn, iterable ...$iterables): PyList
    {
        $arrays = array_map(fn($it) => self::toArray($it), $iterables);
        if (count($arrays) === 1) {
            return new PyList(array_map($fn, $arrays[0]));
        }
        // Multi-arg map like Python map(fn, iter1, iter2, ...)
        $len = min(...array_map('count', $arrays));
        $result = [];
        for ($i = 0; $i < $len; $i++) {
            $args = array_map(fn($a) => $a[$i], $arrays);
            $result[] = $fn(...$args);
        }
        return new PyList($result);
    }

    /** filter() */
    public static function filter(?callable $fn, iterable $iterable): PyList
    {
        $arr = self::toArray($iterable);
        if ($fn === null) {
            return new PyList(array_values(array_filter($arr)));
        }
        return new PyList(array_values(array_filter($arr, $fn)));
    }

    /** type() — get type name. */
    public static function type(mixed $value): string
    {
        if (is_object($value)) {
            if (method_exists($value, 'type')) return $value->type();
            return (new \ReflectionClass($value))->getShortName();
        }
        return get_debug_type($value);
    }

    /** isinstance() */
    public static function isinstance(mixed $value, string ...$classes): bool
    {
        foreach ($classes as $class) {
            if ($value instanceof $class) return true;
        }
        // Also match PHP type names
        $type = get_debug_type($value);
        return in_array($type, $classes, true);
    }

    /** bool() — Python truthiness. */
    public static function bool(mixed $value): bool
    {
        if (method_exists($value, '__bool')) return $value->__bool();
        return (bool)$value;
    }

    // ─── Context manager (with statement) ────────────────────

    /**
     * Python `with` statement equivalent:
     *   Py::with(fopen('file.txt', 'r'), function($f) {
     *       echo fgets($f);
     *   });
     *   // auto-closed after callback
     *
     * Also works with objects that have close()/disconnect()/release() methods.
     */
    public static function with(mixed $resource, callable $fn): mixed
    {
        try {
            $result = $fn($resource);
            return $result;
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            } elseif (is_object($resource)) {
                if (method_exists($resource, '__exit')) {
                    $resource->__exit();
                } elseif (method_exists($resource, 'close')) {
                    $resource->close();
                } elseif (method_exists($resource, 'disconnect')) {
                    $resource->disconnect();
                } elseif (method_exists($resource, 'release')) {
                    $resource->release();
                }
            }
        }
    }

    // ─── Pattern matching ────────────────────────────────────

    /**
     * Structural pattern matching (Python match/case):
     *
     *   Py::match($statusCode, [
     *       200 => fn() => 'OK',
     *       404 => fn() => 'Not Found',
     *       500 => fn() => 'Server Error',
     *       '_' => fn() => 'Unknown',
     *   ]);
     *
     * Supports:
     *   - Exact value matching
     *   - '_' as wildcard/default
     *   - Callable predicates as keys (via matchWhen)
     */
    public static function match(mixed $value, array $cases): mixed
    {
        foreach ($cases as $pattern => $handler) {
            if ($pattern === '_') continue; // process default last
            if ($pattern === $value || $pattern == $value) {
                return is_callable($handler) ? $handler($value) : $handler;
            }
        }
        if (array_key_exists('_', $cases)) {
            $handler = $cases['_'];
            return is_callable($handler) ? $handler($value) : $handler;
        }
        throw new \ValueError("No matching pattern for value");
    }

    /**
     * Match with predicate functions:
     *
     *   Py::matchWhen($age, [
     *       [fn($x) => $x < 13, fn() => 'child'],
     *       [fn($x) => $x < 20, fn() => 'teenager'],
     *       [fn($x) => $x < 65, fn() => 'adult'],
     *       [null, fn() => 'senior'],  // null = default
     *   ]);
     */
    public static function matchWhen(mixed $value, array $cases): mixed
    {
        foreach ($cases as [$predicate, $handler]) {
            if ($predicate === null || $predicate($value)) {
                return is_callable($handler) ? $handler($value) : $handler;
            }
        }
        throw new \ValueError("No matching pattern for value");
    }

    // ─── print() ──────────────────────────────────────────────

    /**
     * Python-like print:
     *   Py::print("hello", "world", sep: ", ", end: "\n")
     */
    public static function print(mixed ...$args): void
    {
        // Extract named args
        $sep = ' ';
        $end = "\n";
        $positional = [];

        foreach ($args as $key => $val) {
            if ($key === 'sep') {
                $sep = $val;
            } elseif ($key === 'end') {
                $end = $val;
            } else {
                $positional[] = $val;
            }
        }

        $strings = array_map(function ($v) {
            if ($v instanceof \Stringable) return (string)$v;
            if (is_bool($v)) return $v ? 'True' : 'False';
            if (is_null($v)) return 'None';
            if (is_array($v)) return json_encode($v);
            return (string)$v;
        }, $positional);

        echo implode($sep, $strings) . $end;
    }

    // ─── input() ─────────────────────────────────────────────

    /** Python input() — read from stdin. */
    public static function input(string $prompt = ''): string
    {
        if ($prompt !== '') echo $prompt;
        return rtrim(fgets(STDIN) ?: '', "\r\n");
    }

    // ─── Misc utilities ──────────────────────────────────────

    /** abs() */
    public static function abs(int|float $x): int|float
    {
        return abs($x);
    }

    /** round() */
    public static function round(int|float $x, int $ndigits = 0): int|float
    {
        return round($x, $ndigits);
    }

    /** divmod() */
    public static function divmod(int $a, int $b): array
    {
        return [intdiv($a, $b), $a % $b];
    }

    /** pow() */
    public static function pow(int|float $base, int|float $exp): int|float
    {
        return $base ** $exp;
    }

    /** hash() — hash any value. */
    public static function hash(mixed $value): string
    {
        return md5(serialize($value));
    }

    /** id() — unique object identifier. */
    public static function id(object $obj): int
    {
        return spl_object_id($obj);
    }

    /** callable() — check if value is callable. */
    public static function callable(mixed $value): bool
    {
        return is_callable($value);
    }

    // ─── Constructors ────────────────────────────────────────

    public static function list(mixed ...$items): PyList
    {
        return new PyList($items);
    }

    public static function dict(array $data = []): PyDict
    {
        return new PyDict($data);
    }

    public static function str(string $data = ''): PyString
    {
        return new PyString($data);
    }

    public static function set(iterable $items = []): PySet
    {
        return new PySet($items);
    }

    public static function tuple(mixed ...$items): PyTuple
    {
        return new PyTuple($items);
    }

    public static function counter(iterable|string $elements = []): PyCounter
    {
        return new PyCounter($elements);
    }

    public static function defaultdict(callable $factory, array $data = []): PyDefaultDict
    {
        return new PyDefaultDict($factory, $data);
    }

    public static function deque(iterable $items = [], ?int $maxlen = null): PyDeque
    {
        return new PyDeque($items, $maxlen);
    }

    public static function chainmap(PyDict|array ...$maps): PyChainMap
    {
        return new PyChainMap(...$maps);
    }

    public static function frozenset(iterable $items = []): PyFrozenSet
    {
        return new PyFrozenSet($items);
    }

    public static function path(string $path): PyPath
    {
        return new PyPath($path);
    }

    /** json.loads() — decode JSON to Pythonic types. */
    public static function json_loads(string $s, bool $wrap = true): mixed
    {
        return PyJson::loads($s, $wrap);
    }

    /** json.dumps() — encode to JSON string. */
    public static function json_dumps(mixed $obj, ?int $indent = null, bool $sort_keys = false, bool $ensure_ascii = false): string
    {
        return PyJson::dumps($obj, $indent, $sort_keys, $ensure_ascii);
    }

    // ─── OrderedDict bridge ──────────────────────────────────

    public static function ordereddict(array $data = []): PyOrderedDict
    {
        return new PyOrderedDict($data);
    }

    public static function partial(callable $fn, mixed ...$args): \Closure
    {
        return Functools::partial($fn, ...$args);
    }

    public static function reduce(callable $fn, iterable $iterable, mixed $initial = null): mixed
    {
        return Functools::reduce($fn, $iterable, $initial);
    }

    // ─── CSV bridge ──────────────────────────────────────────

    public static function csv_reader(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): PyList
    {
        return PyCsv::reader($path, $delimiter, $enclosure, $escape);
    }

    public static function csv_DictReader(string $path, ?array $fieldnames = null, string $delimiter = ','): PyList
    {
        return PyCsv::DictReader($path, $fieldnames, $delimiter);
    }

    public static function csv_writer(string $path, iterable $rows, string $delimiter = ','): void
    {
        PyCsv::writer($path, $rows, $delimiter);
    }

    public static function csv_DictWriter(string $path, array $fieldnames, iterable $rows, string $delimiter = ','): void
    {
        PyCsv::DictWriter($path, $fieldnames, $rows, $delimiter);
    }

    // ─── Operator bridge ─────────────────────────────────────

    public static function itemgetter(string|int ...$keys): \Closure
    {
        return Operator::itemgetter(...$keys);
    }

    public static function attrgetter(string ...$attrs): \Closure
    {
        return Operator::attrgetter(...$attrs);
    }

    public static function methodcaller(string $method, mixed ...$args): \Closure
    {
        return Operator::methodcaller($method, ...$args);
    }

    // ─── DateTime bridge ─────────────────────────────────────

    public static function datetime(\DateTimeImmutable|string|null $datetime = null, \DateTimeZone|string|null $timezone = null): PyDateTime
    {
        return new PyDateTime($datetime, $timezone);
    }

    public static function timedelta(int $days = 0, int $seconds = 0, int $microseconds = 0, int $minutes = 0, int $hours = 0, int $weeks = 0): PyTimeDelta
    {
        return new PyTimeDelta($days, $seconds, $microseconds, $minutes, $hours, $weeks);
    }

    // ─── Internal helpers ────────────────────────────────────

    private static function toArray(iterable $iterable): array
    {
        if (is_array($iterable)) return $iterable;
        if ($iterable instanceof PyList) return $iterable->toPhp();
        if ($iterable instanceof PyDict) return $iterable->toPhp();
        if ($iterable instanceof PySet) return $iterable->toPhp();
        if ($iterable instanceof PyCounter) return $iterable->toPhp();
        if ($iterable instanceof PyDeque) return $iterable->toPhp();
        if ($iterable instanceof PyFrozenSet) return $iterable->toPhp();
        if ($iterable instanceof PyTuple) return $iterable->toPhp();
        if ($iterable instanceof PyOrderedDict) return $iterable->toPhp();
        return iterator_to_array($iterable, false);
    }
}
