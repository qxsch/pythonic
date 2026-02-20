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
use QXS\pythonic\PyChainMap;
use QXS\pythonic\PyPath;
use QXS\pythonic\PyTuple;
use QXS\pythonic\PyJson;
use QXS\pythonic\Itertools;
use QXS\pythonic\PyOrderedDict;
use QXS\pythonic\Functools;
use QXS\pythonic\PyCsv;
use QXS\pythonic\Operator;
use QXS\pythonic\PyDateTime;
use QXS\pythonic\PyTimeDelta;
use QXS\pythonic\Heapq;
use QXS\pythonic\Bisect;
use QXS\pythonic\Shutil;

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

if (!function_exists('py_chainmap')) {
    /**
     * Create a PyChainMap:
     *   py_chainmap(['color' => 'blue'], ['color' => 'red', 'size' => 'M'])
     */
    function py_chainmap(PyDict|array ...$maps): PyChainMap
    {
        return new PyChainMap(...$maps);
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

if (!function_exists('py_tuple')) {
    /**
     * Create an immutable PyTuple from variadic args:
     *   py_tuple(1, 2, 3)
     *   py_tuple(...$items)
     */
    function py_tuple(mixed ...$items): PyTuple
    {
        return new PyTuple($items);
    }
}

if (!function_exists('py_json_loads')) {
    /**
     * Python json.loads() — decode JSON into Pythonic data structures.
     *   py_json_loads('{"name": "Alice"}')  → PyDict
     *   py_json_loads('[1, 2, 3]')            → PyList
     */
    function py_json_loads(string $s, bool $wrap = true): mixed
    {
        return PyJson::loads($s, $wrap);
    }
}

if (!function_exists('py_json_dumps')) {
    /**
     * Python json.dumps() — encode a value to JSON string.
     *   py_json_dumps($data)              → compact JSON
     *   py_json_dumps($data, indent: 2)   → pretty JSON
     */
    function py_json_dumps(mixed $obj, ?int $indent = null, bool $sort_keys = false, bool $ensure_ascii = false): string
    {
        return PyJson::dumps($obj, $indent, $sort_keys, $ensure_ascii);
    }
}

if (!function_exists('py_chain')) {
    /**
     * itertools.chain() — chain multiple iterables together.
     *   py_chain([1,2], [3,4])  → Generator yielding 1,2,3,4
     */
    function py_chain(iterable ...$iterables): Generator
    {
        return Itertools::chain(...$iterables);
    }
}

if (!function_exists('py_islice')) {
    /**
     * itertools.islice() — slice an iterable.
     *   py_islice($iter, 5)        → first 5 items
     *   py_islice($iter, 2, 8, 2)  → items 2,4,6
     */
    function py_islice(iterable $iterable, int $startOrStop, ?int $stop = null, int $step = 1): Generator
    {
        return Itertools::islice($iterable, $startOrStop, $stop, $step);
    }
}

if (!function_exists('py_accumulate')) {
    /**
     * itertools.accumulate() — running totals / accumulated results.
     *   py_accumulate([1,2,3,4])  → Generator yielding 1,3,6,10
     */
    function py_accumulate(iterable $iterable, ?callable $fn = null, mixed $initial = null): Generator
    {
        return Itertools::accumulate($iterable, $fn, $initial);
    }
}

if (!function_exists('py_groupby')) {
    /**
     * itertools.groupby() — group consecutive elements by key.
     *   py_groupby($items, fn($x) => $x['category'])
     */
    function py_groupby(iterable $iterable, ?callable $key = null): Generator
    {
        return Itertools::groupby($iterable, $key);
    }
}

if (!function_exists('py_product')) {
    /**
     * itertools.product() — Cartesian product of iterables.
     *   py_product([1,2], ['a','b'])  → [1,'a'], [1,'b'], [2,'a'], [2,'b']
     */
    function py_product(iterable ...$iterables): Generator
    {
        return Itertools::product(...$iterables);
    }
}

if (!function_exists('py_permutations')) {
    /**
     * itertools.permutations() — r-length permutations of an iterable.
     *   py_permutations([1,2,3], 2)  → [1,2], [1,3], [2,1], ...
     */
    function py_permutations(iterable $iterable, ?int $r = null): Generator
    {
        return Itertools::permutations($iterable, $r);
    }
}

if (!function_exists('py_combinations')) {
    /**
     * itertools.combinations() — r-length combinations of an iterable.
     *   py_combinations([1,2,3], 2)  → [1,2], [1,3], [2,3]
     */
    function py_combinations(iterable $iterable, int $r): Generator
    {
        return Itertools::combinations($iterable, $r);
    }
}

if (!function_exists('py_zip_longest')) {
    /**
     * itertools.zip_longest() — zip iterables, filling shorter with $fillvalue.
     *   py_zip_longest(null, [1,2,3], ['a','b'])  → [1,'a'], [2,'b'], [3,null]
     */
    function py_zip_longest(mixed $fillvalue = null, iterable ...$iterables): Generator
    {
        return Itertools::zip_longest($fillvalue, ...$iterables);
    }
}

if (!function_exists('py_takewhile')) {
    /**
     * itertools.takewhile() — yield elements while predicate is true.
     *   py_takewhile(fn($x) => $x < 5, [1,3,6,2])  → 1, 3
     */
    function py_takewhile(callable $predicate, iterable $iterable): Generator
    {
        return Itertools::takewhile($predicate, $iterable);
    }
}

if (!function_exists('py_dropwhile')) {
    /**
     * itertools.dropwhile() — drop elements while predicate is true, then yield rest.
     *   py_dropwhile(fn($x) => $x < 5, [1,3,6,2])  → 6, 2
     */
    function py_dropwhile(callable $predicate, iterable $iterable): Generator
    {
        return Itertools::dropwhile($predicate, $iterable);
    }
}

if (!function_exists('py_starmap')) {
    /**
     * itertools.starmap() — apply function to each unpacked element.
     *   py_starmap(fn($a,$b) => $a+$b, [[1,2],[3,4]])  → 3, 7
     */
    function py_starmap(callable $fn, iterable $iterable): Generator
    {
        return Itertools::starmap($fn, $iterable);
    }
}

if (!function_exists('py_filterfalse')) {
    /**
     * itertools.filterfalse() — yield elements where predicate is false.
     *   py_filterfalse(fn($x) => $x % 2, [1,2,3,4])  → 2, 4
     */
    function py_filterfalse(?callable $predicate, iterable $iterable): Generator
    {
        return Itertools::filterfalse($predicate, $iterable);
    }
}

if (!function_exists('py_pairwise')) {
    /**
     * itertools.pairwise() — yield consecutive overlapping pairs.
     *   py_pairwise([1,2,3,4])  → [1,2], [2,3], [3,4]
     */
    function py_pairwise(iterable $iterable): Generator
    {
        return Itertools::pairwise($iterable);
    }
}

if (!function_exists('py_compress')) {
    /**
     * itertools.compress() — filter data by selectors.
     *   py_compress(['a','b','c'], [1,0,1])  → 'a', 'c'
     */
    function py_compress(iterable $data, iterable $selectors): Generator
    {
        return Itertools::compress($data, $selectors);
    }
}

if (!function_exists('py_ordereddict')) {
    /**
     * Create a PyOrderedDict:
     *   py_ordereddict(['a' => 1, 'b' => 2])
     */
    function py_ordereddict(array $data = []): PyOrderedDict
    {
        return new PyOrderedDict($data);
    }
}

if (!function_exists('py_lru_cache')) {
    /**
     * functools.lru_cache() — memoize a function with an LRU cache.
     *   $cached = py_lru_cache($fn, 128);
     */
    function py_lru_cache(callable $fn, int $maxsize = 128): \Closure
    {
        return Functools::lru_cache($fn, $maxsize);
    }
}

if (!function_exists('py_cache')) {
    /**
     * functools.cache() — simple unbounded memoization.
     *   $cached = py_cache($fn);
     */
    function py_cache(callable $fn): \Closure
    {
        return Functools::cache($fn);
    }
}

if (!function_exists('py_cmp_to_key')) {
    /**
     * functools.cmp_to_key() — convert a cmp function to a key function for sorting.
     *   py_sorted($items, key: py_cmp_to_key(fn($a,$b) => $a <=> $b))
     */
    function py_cmp_to_key(callable $cmpFn): \Closure
    {
        return Functools::cmp_to_key($cmpFn);
    }
}

if (!function_exists('py_compose')) {
    /**
     * functools.compose() — compose multiple functions (right to left).
     *   $fn = py_compose($f, $g, $h);  // $fn($x) === $f($g($h($x)))
     */
    function py_compose(callable ...$fns): \Closure
    {
        return Functools::compose(...$fns);
    }
}

if (!function_exists('py_partial')) {
    /**
     * functools.partial() — create a partially applied function.
     *   py_partial($fn, 1, 2)(3)  → $fn(1, 2, 3)
     */
    function py_partial(callable $fn, mixed ...$frozenArgs): \Closure
    {
        return Functools::partial($fn, ...$frozenArgs);
    }
}

if (!function_exists('py_reduce')) {
    /**
     * functools.reduce() — apply a function of two arguments cumulatively.
     *   py_reduce(fn($a, $b) => $a + $b, [1,2,3,4])  → 10
     */
    function py_reduce(callable $fn, iterable $iterable, mixed $initial = null): mixed
    {
        return Functools::reduce($fn, $iterable, $initial);
    }
}

if (!function_exists('py_csv_reader')) {
    /**
     * csv.reader() — read CSV file as PyList of PyList rows.
     *   py_csv_reader('/path/to/file.csv')
     */
    function py_csv_reader(string $path, string $delimiter = ',', string $enclosure = '"', string $escape = '\\'): PyList
    {
        return PyCsv::reader($path, $delimiter, $enclosure, $escape);
    }
}

if (!function_exists('py_csv_dictreader')) {
    /**
     * csv.DictReader() — read CSV file as PyList of PyDict rows.
     *   py_csv_dictreader('/path/to/file.csv')
     */
    function py_csv_dictreader(string $path, ?array $fieldnames = null, string $delimiter = ','): PyList
    {
        return PyCsv::DictReader($path, $fieldnames, $delimiter);
    }
}

if (!function_exists('py_methodcaller')) {
    /**
     * operator.methodcaller() — return a callable that calls a method on its operand.
     *   $upper = py_methodcaller('upper');
     *   $upper(py_str('hello'))  → PyString('HELLO')
     */
    function py_methodcaller(string $method, mixed ...$args): \Closure
    {
        return Operator::methodcaller($method, ...$args);
    }
}

if (!function_exists('py_itemgetter')) {
    /**
     * operator.itemgetter() — return a callable that fetches items by key.
     *   $getName = py_itemgetter('name');
     *   $getName(['name' => 'Alice'])  → 'Alice'
     */
    function py_itemgetter(string|int ...$keys): \Closure
    {
        return Operator::itemgetter(...$keys);
    }
}

if (!function_exists('py_attrgetter')) {
    /**
     * operator.attrgetter() — return a callable that fetches attributes.
     *   $getX = py_attrgetter('x');
     *   $getX($point)  → $point->x
     */
    function py_attrgetter(string ...$attrs): \Closure
    {
        return Operator::attrgetter(...$attrs);
    }
}

if (!function_exists('py_datetime')) {
    /**
     * Create a PyDateTime:
     *   py_datetime()                → now
     *   py_datetime('2024-01-15')   → specific date
     */
    function py_datetime(\DateTimeImmutable|string|null $datetime = null, \DateTimeZone|string|null $timezone = null): PyDateTime
    {
        return new PyDateTime($datetime, $timezone);
    }
}

if (!function_exists('py_timedelta')) {
    /**
     * Create a PyTimeDelta:
     *   py_timedelta(days: 5, hours: 3)
     */
    function py_timedelta(int $days = 0, int $seconds = 0, int $microseconds = 0, int $minutes = 0, int $hours = 0, int $weeks = 0): PyTimeDelta
    {
        return new PyTimeDelta($days, $seconds, $microseconds, $minutes, $hours, $weeks);
    }
}

if (!function_exists('py_heappush')) {
    /**
     * heapq.heappush() — push an item onto a heap.
     *   py_heappush($heap, 5)
     */
    function py_heappush(PyList $heap, mixed $item): void
    {
        Heapq::heappush($heap, $item);
    }
}

if (!function_exists('py_heappop')) {
    /**
     * heapq.heappop() — pop the smallest item from a heap.
     *   $smallest = py_heappop($heap)
     */
    function py_heappop(PyList $heap): mixed
    {
        return Heapq::heappop($heap);
    }
}

if (!function_exists('py_heapify')) {
    /**
     * heapq.heapify() — transform a PyList into a heap in-place.
     *   py_heapify($list)
     */
    function py_heapify(PyList $list): void
    {
        Heapq::heapify($list);
    }
}

if (!function_exists('py_nlargest')) {
    /**
     * heapq.nlargest() — return the n largest elements.
     *   py_nlargest(3, [1,8,2,7,3])  → PyList [8,7,3]
     */
    function py_nlargest(int $n, iterable $iterable, ?\Closure $key = null): PyList
    {
        return Heapq::nlargest($n, $iterable, $key);
    }
}

if (!function_exists('py_nsmallest')) {
    /**
     * heapq.nsmallest() — return the n smallest elements.
     *   py_nsmallest(3, [1,8,2,7,3])  → PyList [1,2,3]
     */
    function py_nsmallest(int $n, iterable $iterable, ?\Closure $key = null): PyList
    {
        return Heapq::nsmallest($n, $iterable, $key);
    }
}

if (!function_exists('py_heapmerge')) {
    /**
     * heapq.merge() — merge multiple sorted inputs into a single sorted output.
     *   py_heapmerge([1,3,5], [2,4,6])  → PyList [1,2,3,4,5,6]
     */
    function py_heapmerge(iterable ...$iterables): PyList
    {
        return Heapq::merge(...$iterables);
    }
}

if (!function_exists('py_bisect_index')) {
    /**
     * bisect.index() — O(log n) find index of $x in sorted sequence, or -1.
     *   py_bisect_index([1, 3, 5], 3)  → 1
     */
    function py_bisect_index(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        return Bisect::index($a, $x, $lo, $hi, $key);
    }
}

if (!function_exists('py_bisect_contains')) {
    /**
     * bisect.contains() — O(log n) membership test on a sorted sequence.
     *   py_bisect_contains([1, 3, 5], 3)  → true
     */
    function py_bisect_contains(array|PyList $a, mixed $x, ?callable $key = null): bool
    {
        return Bisect::contains($a, $x, $key);
    }
}

if (!function_exists('py_insort_left')) {
    /**
     * bisect.insort_left() — insert maintaining sorted order (left of existing).
     *   $arr = [1, 3, 5]; py_insort_left($arr, 3);  → $arr = [1, 3, 3, 5]
     */
    function py_insort_left(array|PyList &$a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): void
    {
        Bisect::insort_left($a, $x, $lo, $hi, $key);
    }
}

if (!function_exists('py_insort_right')) {
    /**
     * bisect.insort_right() — insert maintaining sorted order (right of existing).
     *   $arr = [1, 3, 5]; py_insort_right($arr, 3);  → $arr = [1, 3, 3, 5]
     */
    function py_insort_right(array|PyList &$a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): void
    {
        Bisect::insort_right($a, $x, $lo, $hi, $key);
    }
}

if (!function_exists('py_bisect_left')) {
    /**
     * bisect.bisect_left() — find leftmost insertion point.
     *   py_bisect_left([1, 3, 5], 3)  → 1
     */
    function py_bisect_left(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        return Bisect::bisect_left($a, $x, $lo, $hi, $key);
    }
}

if (!function_exists('py_bisect_right')) {
    /**
     * bisect.bisect_right() — find rightmost insertion point.
     *   py_bisect_right([1, 3, 5], 3)  → 2
     */
    function py_bisect_right(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        return Bisect::bisect_right($a, $x, $lo, $hi, $key);
    }
}

if (!function_exists('py_insort')) {
    /**
     * bisect.insort() — insert into sorted sequence maintaining order.
     *   $arr = [1, 3, 5]; py_insort($arr, 4);  → $arr = [1, 3, 4, 5]
     */
    function py_insort(array|PyList &$a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): void
    {
        Bisect::insort($a, $x, $lo, $hi, $key);
    }
}

if (!function_exists('py_copyfile')) {
    /**
     * shutil.copyfile() — copy file content only (no metadata).
     *   py_copyfile('/src/a.txt', '/dst/a.txt')
     */
    function py_copyfile(string|PyPath $src, string|PyPath $dst): PyPath
    {
        return Shutil::copyfile($src, $dst);
    }
}

if (!function_exists('py_copy')) {
    /**
     * shutil.copy() — copy file preserving permissions.
     * If $dst is a directory, copies into it with the same name.
     *   py_copy('/src/a.txt', '/dst/')
     */
    function py_copy(string|PyPath $src, string|PyPath $dst): PyPath
    {
        return Shutil::copy($src, $dst);
    }
}

if (!function_exists('py_copy2')) {
    /**
     * shutil.copy2() — copy file preserving permissions + timestamps.
     *   py_copy2('/src/a.txt', '/dst/a.txt')
     */
    function py_copy2(string|PyPath $src, string|PyPath $dst): PyPath
    {
        return Shutil::copy2($src, $dst);
    }
}

if (!function_exists('py_copytree')) {
    /**
     * shutil.copytree() — recursively copy a directory tree.
     *   py_copytree('/src/project', '/backup/project')
     */
    function py_copytree(string|PyPath $src, string|PyPath $dst, bool $dirs_exist_ok = false, ?callable $ignore = null): PyPath
    {
        return Shutil::copytree($src, $dst, $dirs_exist_ok, $ignore);
    }
}

if (!function_exists('py_rmtree')) {
    /**
     * shutil.rmtree() — recursively remove a directory tree.
     *   py_rmtree('/tmp/build')
     */
    function py_rmtree(string|PyPath $path, bool $ignore_errors = false): void
    {
        Shutil::rmtree($path, $ignore_errors);
    }
}

if (!function_exists('py_move')) {
    /**
     * shutil.move() — move file or directory. If $dst is an existing dir, moves inside it.
     *   py_move('/old/file.txt', '/new/file.txt')
     */
    function py_move(string|PyPath $src, string|PyPath $dst): PyPath
    {
        return Shutil::move($src, $dst);
    }
}

if (!function_exists('py_disk_usage')) {
    /**
     * shutil.disk_usage() — return disk space as ['total', 'used', 'free'] in bytes.
     *   py_disk_usage('/')['free']
     */
    function py_disk_usage(string|PyPath $path): array
    {
        return Shutil::disk_usage($path);
    }
}

if (!function_exists('py_which')) {
    /**
     * shutil.which() — locate an executable in PATH.
     *   py_which('php')  → PyPath('/usr/bin/php')
     */
    function py_which(string $name): ?PyPath
    {
        return Shutil::which($name);
    }
}

if (!function_exists('py_make_archive')) {
    /**
     * shutil.make_archive() — create an archive (.zip or .tar.gz).
     *   py_make_archive('/tmp/backup', 'zip', '/src/project')
     */
    function py_make_archive(string $baseName, string $format, string|PyPath $rootDir): PyPath
    {
        return Shutil::make_archive($baseName, $format, $rootDir);
    }
}

if (!function_exists('py_unpack_archive')) {
    /**
     * shutil.unpack_archive() — extract an archive.
     *   py_unpack_archive('/tmp/backup.zip', '/dst')
     */
    function py_unpack_archive(string|PyPath $filename, string|PyPath $extractDir): void
    {
        Shutil::unpack_archive($filename, $extractDir);
    }
}
