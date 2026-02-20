<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python functools module for PHP.
 *
 * Provides higher-order functions and utilities for working with callables:
 *   - partial($fn, ...$args)       — partial application
 *   - reduce($fn, $iterable, $initial) — fold/reduce
 *   - lru_cache($fn, $maxsize)     — memoize with LRU eviction
 *   - cache($fn)                   — simple unbounded memoize
 *   - wraps($wrapper, $wrapped)    — attach original callable metadata to a wrapper
 *   - wrapped($wrapper)            — retrieve metadata set by wraps()
 *   - cmp_to_key($cmpFn)          — convert old-style cmp to key function
 *
 * Usage:
 *   use QXS\pythonic\Functools;
 *
 *   // partial
 *   $add = fn($a, $b) => $a + $b;
 *   $add5 = Functools::partial($add, 5);
 *   $add5(3);  // 8
 *
 *   // lru_cache
 *   $fib = Functools::lru_cache(function(int $n) use (&$fib): int {
 *       return $n < 2 ? $n : $fib($n - 1) + $fib($n - 2);
 *   }, maxsize: 128);
 *   $fib(10);  // 55
 *
 *   // reduce
 *   Functools::reduce(fn($a, $b) => $a + $b, [1, 2, 3, 4]);  // 10
 */
class Functools
{
    /**
     * Partial application — freeze some arguments of a callable.
     *
     * Python: functools.partial(func, *args, **kwargs)
     *
     * @param callable $fn       The function to partially apply.
     * @param mixed    ...$args  Arguments to freeze (positional, left-to-right).
     * @return \Closure A new callable with the frozen args prepended.
     */
    public static function partial(callable $fn, mixed ...$args): \Closure
    {
        return function (mixed ...$rest) use ($fn, $args): mixed {
            return $fn(...$args, ...$rest);
        };
    }

    /**
     * Reduce an iterable to a single value by applying a binary function cumulatively.
     *
     * Python: functools.reduce(function, iterable[, initializer])
     *
     * @param callable          $fn       Binary function: fn($accumulator, $current) => $result
     * @param iterable          $iterable The iterable to reduce.
     * @param mixed             $initial  Optional initial value. If omitted, first element is used.
     * @return mixed
     *
     * @throws \ValueError If iterable is empty and no initial value is provided.
     */
    public static function reduce(callable $fn, iterable $iterable, mixed $initial = null): mixed
    {
        $arr = self::iterableToArray($iterable);

        if ($initial === null && func_num_args() < 3) {
            if (empty($arr)) {
                throw new ValueError('reduce() of empty iterable with no initial value');
            }
            $initial = array_shift($arr);
        }

        return array_reduce($arr, $fn, $initial);
    }

    /**
     * LRU (Least Recently Used) cache decorator.
     *
     * Python: @functools.lru_cache(maxsize=128)
     *
     * Returns a memoized closure. When maxsize is reached, the least
     * recently used cached result is discarded.
     *
     * The returned closure also has these properties accessible via the
     * returned array: ['fn' => $callable, 'cache_info' => $infoFn, 'cache_clear' => $clearFn]
     *
     * @param callable $fn       The function to memoize.
     * @param int      $maxsize  Maximum cache entries (default: 128). Use 0 for unlimited.
     * @return \Closure The memoized function.
     */
    public static function lru_cache(callable $fn, int $maxsize = 128): \Closure
    {
        $cache = [];
        $hits = 0;
        $misses = 0;
        $order = []; // LRU tracking: key => insertion order

        $memoized = function () use ($fn, &$cache, &$hits, &$misses, &$order, $maxsize): mixed {
            $args = func_get_args();
            $key = serialize($args);

            if (array_key_exists($key, $cache)) {
                $hits++;
                // Move to end (most recently used)
                unset($order[$key]);
                $order[$key] = true;
                return $cache[$key];
            }

            $misses++;
            $result = $fn(...$args);

            // Evict LRU if at capacity
            if ($maxsize > 0 && count($cache) >= $maxsize) {
                $lruKey = array_key_first($order);
                unset($cache[$lruKey], $order[$lruKey]);
            }

            $cache[$key] = $result;
            $order[$key] = true;
            return $result;
        };

        // Attach cache_info and cache_clear as static properties
        // Access via Functools::cache_info($memoized) won't work in PHP,
        // but we store the info for retrieval
        $infoRef = new \stdClass();
        $infoRef->hits = &$hits;
        $infoRef->misses = &$misses;
        $infoRef->cache = &$cache;
        $infoRef->order = &$order;

        // Store metadata on the closure using a static registry
        $infoRef->maxsize = $maxsize;
        self::$cacheRegistry[spl_object_id($memoized)] = $infoRef;

        return $memoized;
    }

    /** @var array<int, \stdClass> Registry of cached function metadata. */
    private static array $cacheRegistry = [];

    /**
     * Get cache info for a memoized function (like Python's f.cache_info()).
     *
     * @return PyDict {hits: int, misses: int, maxsize: int, currsize: int}
     */
    public static function cache_info(\Closure $memoized): PyDict
    {
        $id = spl_object_id($memoized);
        if (!isset(self::$cacheRegistry[$id])) {
            throw new \ValueError('Function was not created by lru_cache');
        }
        $info = self::$cacheRegistry[$id];
        return new PyDict([
            'hits' => $info->hits,
            'misses' => $info->misses,
            'maxsize' => $info->maxsize,
            'currsize' => count($info->cache),
        ]);
    }

    /**
     * Clear cache for a memoized function (like Python's f.cache_clear()).
     */
    public static function cache_clear(\Closure $memoized): void
    {
        $id = spl_object_id($memoized);
        if (!isset(self::$cacheRegistry[$id])) {
            throw new \ValueError('Function was not created by lru_cache');
        }
        $info = self::$cacheRegistry[$id];
        $info->cache = [];
        $info->order = [];
    }

    /**
     * Simple unbounded cache (memoize all calls forever).
     *
     * Python: @functools.cache (== @lru_cache(maxsize=None))
     *
     * @param callable $fn The function to memoize.
     * @return \Closure Memoized function.
     */
    public static function cache(callable $fn): \Closure
    {
        return self::lru_cache($fn, maxsize: 0);
    }

    /**
     * Convert a Python 2-style cmp function to a key function for sorting.
     *
     * Python: functools.cmp_to_key(cmp_func)
     *
     * @param callable $cmpFn A comparison function: fn($a, $b) => int (<0, 0, >0)
     * @return \Closure A key function suitable for usort() or py_sorted(key: ...).
     */
    public static function cmp_to_key(callable $cmpFn): \Closure
    {
        return function (mixed $a, mixed $b) use ($cmpFn): int {
            return $cmpFn($a, $b);
        };
    }

    /**
     * Return a new callable that calls the given methods in sequence,
     * returning the result of the last one.
     *
     * Similar concept to functools.reduce but for function composition.
     *
     * @param callable ...$fns Functions to compose (left to right).
     * @return \Closure
     */
    public static function compose(callable ...$fns): \Closure
    {
        return function (mixed $value) use ($fns): mixed {
            foreach ($fns as $fn) {
                $value = $fn($value);
            }
            return $value;
        };
    }

    /**
     * identity(x) → x. A no-op function, useful as a default.
     */
    public static function identity(mixed $x): mixed
    {
        return $x;
    }

    /**
     * Decorator that copies metadata from a wrapped function onto a wrapper.
     *
     * Python: @functools.wraps(wrapped)
     *
     * In PHP closures have no __name__ or __doc__, so this attaches the
     * original callable and optional metadata as a stdClass on the wrapper.
     * Retrieve metadata later with Functools::wrapped($wrapper).
     *
     * @param  \Closure  $wrapper  The wrapper closure.
     * @param  callable  $wrapped  The original function being wrapped.
     * @param  array     $extra    Additional metadata to store.
     * @return \Closure  The same $wrapper, with metadata attached.
     */
    public static function wraps(\Closure $wrapper, callable $wrapped, array $extra = []): \Closure
    {
        $meta = new \stdClass();
        $meta->wrapped = $wrapped;
        $meta->name    = self::callableName($wrapped);
        foreach ($extra as $k => $v) {
            $meta->{$k} = $v;
        }
        self::$wrapsRegistry[spl_object_id($wrapper)] = $meta;
        return $wrapper;
    }

    /** @var array<int, \stdClass> Registry of wrapped function metadata. */
    private static array $wrapsRegistry = [];

    /**
     * Retrieve metadata attached by wraps().
     *
     * @return \stdClass|null  The metadata, or null if none was set.
     */
    public static function wrapped(\Closure $wrapper): ?\stdClass
    {
        return self::$wrapsRegistry[spl_object_id($wrapper)] ?? null;
    }

    // ─── Internal helpers ────────────────────────────────────

    /**
     * Best-effort human-readable name for a callable.
     */
    private static function callableName(callable $fn): string
    {
        if (is_string($fn)) return $fn;
        if (is_array($fn)) {
            $class = is_object($fn[0]) ? get_class($fn[0]) : $fn[0];
            return $class . '::' . $fn[1];
        }
        if ($fn instanceof \Closure) {
            $ref = new \ReflectionFunction($fn);
            return 'Closure@' . basename($ref->getFileName()) . ':' . $ref->getStartLine();
        }
        if (is_object($fn) && method_exists($fn, '__invoke')) {
            return get_class($fn) . '::__invoke';
        }
        return 'unknown';
    }

    private static function iterableToArray(iterable $iterable): array
    {
        if (is_array($iterable)) return $iterable;
        if ($iterable instanceof PyList) return $iterable->toPhp();
        if ($iterable instanceof PyTuple) return $iterable->toPhp();
        if ($iterable instanceof PySet) return $iterable->toPhp();
        if ($iterable instanceof PyDeque) return $iterable->toPhp();
        return iterator_to_array($iterable, false);
    }
}
