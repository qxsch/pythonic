<?php

declare(strict_types=1);

namespace QXS\pythonic;

use Generator;

/**
 * Python itertools module — lazy generator-based combinatoric & iteration utilities.
 *
 * All methods return Generators for memory efficiency, or PyList when materialized.
 *
 * Usage:
 *   use QXS\pythonic\Itertools;
 *
 *   Itertools::chain([1,2], [3,4])               → 1, 2, 3, 4
 *   Itertools::cycle([1,2,3])                     → 1, 2, 3, 1, 2, 3, ...
 *   Itertools::repeat('x', 5)                     → x, x, x, x, x
 *   Itertools::islice(range(100), 2, 8, 2)        → 2, 4, 6
 *   Itertools::accumulate([1,2,3,4])              → 1, 3, 6, 10
 *   Itertools::product([1,2], ['a','b'])           → [1,'a'], [1,'b'], [2,'a'], [2,'b']
 *   Itertools::permutations([1,2,3], 2)            → [1,2], [1,3], [2,1], ...
 *   Itertools::combinations([1,2,3], 2)            → [1,2], [1,3], [2,3]
 *   Itertools::combinations_with_replacement(...)   → includes repeated picks
 *   Itertools::starmap(fn($a,$b) => $a+$b, [[1,2],[3,4]])  → 3, 7
 *   Itertools::pairwise([1,2,3,4])                → [1,2], [2,3], [3,4]
 *   Itertools::groupby(iterable, keyFn)            → groups
 *   Itertools::takewhile(predicate, iterable)      → items while true
 *   Itertools::dropwhile(predicate, iterable)      → items after first false
 *   Itertools::zip_longest(...)                    → zip with fill value
 *   Itertools::compress(data, selectors)           → filtered by truthy selectors
 *   Itertools::count(start, step)                  → infinite counter
 *   Itertools::tee(iterable, n)                    → n independent copies
 */
class Itertools
{
    // ─── Infinite iterators ──────────────────────────────────

    /**
     * count(start=0, step=1) — infinite counter.
     */
    public static function count(int|float $start = 0, int|float $step = 1): Generator
    {
        $n = $start;
        while (true) {
            yield $n;
            $n += $step;
        }
    }

    /**
     * cycle(iterable) — repeat iterable forever.
     */
    public static function cycle(iterable $iterable): Generator
    {
        $saved = [];
        foreach ($iterable as $item) {
            yield $item;
            $saved[] = $item;
        }
        if (empty($saved)) return;
        while (true) {
            foreach ($saved as $item) {
                yield $item;
            }
        }
    }

    /**
     * repeat(value, times=null) — repeat a value, optionally limited.
     */
    public static function repeat(mixed $value, ?int $times = null): Generator
    {
        if ($times === null) {
            while (true) {
                yield $value;
            }
        } else {
            for ($i = 0; $i < $times; $i++) {
                yield $value;
            }
        }
    }

    // ─── Finite iterators ────────────────────────────────────

    /**
     * chain(...iterables) — flatten multiple iterables into one.
     */
    public static function chain(iterable ...$iterables): Generator
    {
        foreach ($iterables as $it) {
            yield from $it;
        }
    }

    /**
     * chain_from_iterable(iterable_of_iterables)
     */
    public static function chain_from_iterable(iterable $iterable): Generator
    {
        foreach ($iterable as $it) {
            yield from $it;
        }
    }

    /**
     * compress(data, selectors) — filter data by truthy selectors.
     *   compress('ABCDEF', [1,0,1,0,1,1]) → A, C, E, F
     */
    public static function compress(iterable $data, iterable $selectors): Generator
    {
        $dataArr = self::toArray($data);
        $selArr = self::toArray($selectors);
        $len = min(count($dataArr), count($selArr));
        for ($i = 0; $i < $len; $i++) {
            if ($selArr[$i]) {
                yield $dataArr[$i];
            }
        }
    }

    /**
     * islice(iterable, stop) or islice(iterable, start, stop, step)
     *   Slice an iterator lazily.
     */
    public static function islice(iterable $iterable, int $startOrStop, ?int $stop = null, int $step = 1): Generator
    {
        if ($stop === null) {
            $start = 0;
            $stop = $startOrStop;
        } else {
            $start = $startOrStop;
        }

        $i = 0;
        $next = $start;
        foreach ($iterable as $item) {
            if ($i >= $stop) break;
            if ($i === $next) {
                yield $item;
                $next += $step;
            }
            $i++;
        }
    }

    /**
     * accumulate(iterable, fn=null, initial=null) — running totals.
     *   accumulate([1,2,3,4])                 → 1, 3, 6, 10
     *   accumulate([1,2,3], fn($a,$b)=>$a*$b) → 1, 2, 6
     */
    public static function accumulate(iterable $iterable, ?callable $fn = null, mixed $initial = null): Generator
    {
        $fn ??= fn($a, $b) => $a + $b;
        $total = $initial;
        $started = ($initial !== null);

        if ($started) {
            yield $total;
        }

        foreach ($iterable as $item) {
            if (!$started) {
                $total = $item;
                $started = true;
            } else {
                $total = $fn($total, $item);
            }
            yield $total;
        }
    }

    /**
     * starmap(fn, iterable) — apply fn to each item unpacked as args.
     *   starmap(fn($a,$b) => $a + $b, [[1,2],[3,4]]) → 3, 7
     */
    public static function starmap(callable $fn, iterable $iterable): Generator
    {
        foreach ($iterable as $args) {
            yield $fn(...$args);
        }
    }

    /**
     * pairwise(iterable) — successive overlapping pairs.
     *   pairwise([1,2,3,4]) → [1,2], [2,3], [3,4]
     */
    public static function pairwise(iterable $iterable): Generator
    {
        $prev = null;
        $hasPrev = false;
        foreach ($iterable as $item) {
            if ($hasPrev) {
                yield [$prev, $item];
            }
            $prev = $item;
            $hasPrev = true;
        }
    }

    /**
     * takewhile(predicate, iterable) — yield while predicate is true.
     */
    public static function takewhile(callable $predicate, iterable $iterable): Generator
    {
        foreach ($iterable as $item) {
            if (!$predicate($item)) break;
            yield $item;
        }
    }

    /**
     * dropwhile(predicate, iterable) — skip while predicate is true, then yield rest.
     */
    public static function dropwhile(callable $predicate, iterable $iterable): Generator
    {
        $dropping = true;
        foreach ($iterable as $item) {
            if ($dropping && $predicate($item)) continue;
            $dropping = false;
            yield $item;
        }
    }

    /**
     * groupby(iterable, key=null) — group consecutive elements by key.
     * Returns generator of [key, Generator] pairs.
     *
     * Note: Like Python, this only groups consecutive identical keys.
     * Sort first for full grouping.
     */
    public static function groupby(iterable $iterable, ?callable $key = null): Generator
    {
        $key ??= fn($x) => $x;
        $currentKey = null;
        $currentGroup = [];
        $started = false;

        foreach ($iterable as $item) {
            $k = $key($item);
            if (!$started) {
                $currentKey = $k;
                $currentGroup = [$item];
                $started = true;
            } elseif ($k === $currentKey) {
                $currentGroup[] = $item;
            } else {
                yield [$currentKey, new PyList($currentGroup)];
                $currentKey = $k;
                $currentGroup = [$item];
            }
        }

        if ($started) {
            yield [$currentKey, new PyList($currentGroup)];
        }
    }

    /**
     * zip_longest(...iterables, fillvalue=null) — zip, padding shorter iterables.
     */
    public static function zip_longest(mixed $fillvalue = null, iterable ...$iterables): Generator
    {
        $arrays = array_map(fn($it) => self::toArray($it), $iterables);
        if (empty($arrays)) return;
        $maxLen = max(...array_map('count', $arrays));
        for ($i = 0; $i < $maxLen; $i++) {
            $tuple = [];
            foreach ($arrays as $arr) {
                $tuple[] = $arr[$i] ?? $fillvalue;
            }
            yield $tuple;
        }
    }

    // ─── Combinatoric iterators ──────────────────────────────

    /**
     * product(...iterables) — Cartesian product.
     *   product([1,2], ['a','b']) → [1,'a'], [1,'b'], [2,'a'], [2,'b']
     */
    public static function product(iterable ...$iterables): Generator
    {
        $pools = array_map(fn($it) => self::toArray($it), $iterables);
        if (empty($pools)) {
            yield [];
            return;
        }

        $indices = array_fill(0, count($pools), 0);
        $lengths = array_map('count', $pools);

        // Check for any empty pool
        foreach ($lengths as $len) {
            if ($len === 0) return;
        }

        while (true) {
            $tuple = [];
            foreach ($indices as $i => $idx) {
                $tuple[] = $pools[$i][$idx];
            }
            yield $tuple;

            // Increment indices from right
            $pos = count($pools) - 1;
            while ($pos >= 0) {
                $indices[$pos]++;
                if ($indices[$pos] < $lengths[$pos]) break;
                $indices[$pos] = 0;
                $pos--;
            }
            if ($pos < 0) break;
        }
    }

    /**
     * permutations(iterable, r=null) — all r-length permutations.
     */
    public static function permutations(iterable $iterable, ?int $r = null): Generator
    {
        $pool = self::toArray($iterable);
        $n = count($pool);
        $r ??= $n;

        if ($r > $n) return;

        $indices = range(0, $n - 1);
        $cycles = range($n, $n - $r + 1, -1);

        $result = [];
        for ($i = 0; $i < $r; $i++) {
            $result[] = $pool[$indices[$i]];
        }
        yield $result;

        if ($n === 0) return;

        while (true) {
            $found = false;
            for ($i = $r - 1; $i >= 0; $i--) {
                $cycles[$i]--;
                if ($cycles[$i] === 0) {
                    // Move index to end
                    $tmp = $indices[$i];
                    for ($j = $i; $j < $n - 1; $j++) {
                        $indices[$j] = $indices[$j + 1];
                    }
                    $indices[$n - 1] = $tmp;
                    $cycles[$i] = $n - $i;
                } else {
                    $j = $n - $cycles[$i];
                    [$indices[$i], $indices[$j]] = [$indices[$j], $indices[$i]];
                    $result = [];
                    for ($k = 0; $k < $r; $k++) {
                        $result[] = $pool[$indices[$k]];
                    }
                    yield $result;
                    $found = true;
                    break;
                }
            }
            if (!$found) break;
        }
    }

    /**
     * combinations(iterable, r) — all r-length combinations, order doesn't matter.
     */
    public static function combinations(iterable $iterable, int $r): Generator
    {
        $pool = self::toArray($iterable);
        $n = count($pool);
        if ($r > $n) return;

        $indices = range(0, $r - 1);
        $result = [];
        for ($i = 0; $i < $r; $i++) {
            $result[] = $pool[$indices[$i]];
        }
        yield $result;

        while (true) {
            $found = false;
            for ($i = $r - 1; $i >= 0; $i--) {
                if ($indices[$i] !== $i + $n - $r) {
                    $found = true;
                    break;
                }
            }
            if (!$found) break;

            $indices[$i]++;
            for ($j = $i + 1; $j < $r; $j++) {
                $indices[$j] = $indices[$j - 1] + 1;
            }
            $result = [];
            for ($k = 0; $k < $r; $k++) {
                $result[] = $pool[$indices[$k]];
            }
            yield $result;
        }
    }

    /**
     * combinations_with_replacement(iterable, r) — r-length combinations allowing repeated picks.
     */
    public static function combinations_with_replacement(iterable $iterable, int $r): Generator
    {
        $pool = self::toArray($iterable);
        $n = count($pool);
        if ($n === 0 && $r > 0) return;
        if ($r === 0) {
            yield [];
            return;
        }

        $indices = array_fill(0, $r, 0);
        $result = [];
        for ($i = 0; $i < $r; $i++) {
            $result[] = $pool[0];
        }
        yield $result;

        while (true) {
            $found = false;
            for ($i = $r - 1; $i >= 0; $i--) {
                if ($indices[$i] !== $n - 1) {
                    $found = true;
                    break;
                }
            }
            if (!$found) break;

            $newVal = $indices[$i] + 1;
            for ($j = $i; $j < $r; $j++) {
                $indices[$j] = $newVal;
            }
            $result = [];
            for ($k = 0; $k < $r; $k++) {
                $result[] = $pool[$indices[$k]];
            }
            yield $result;
        }
    }

    // ─── Materializers ────────────────────────────────────────

    /**
     * Collect a generator/iterable into a PyList.
     */
    public static function toList(iterable $iterable): PyList
    {
        return new PyList(self::toArray($iterable));
    }

    // ─── Internal ─────────────────────────────────────────────

    private static function toArray(iterable $iterable): array
    {
        if (is_array($iterable)) return array_values($iterable);
        if ($iterable instanceof PyList) return $iterable->toPhp();
        if ($iterable instanceof PySet) return $iterable->toPhp();
        return iterator_to_array($iterable, false);
    }
}
