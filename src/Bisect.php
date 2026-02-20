<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python bisect module for PHP.
 *
 * Provides O(log n) binary-search insertion into sorted sequences.
 * Works with plain PHP arrays and PyList instances.
 *
 * Functions:
 *   - bisect_left($a, $x, lo, hi, key)   — find leftmost insertion point
 *   - bisect_right($a, $x, lo, hi, key)  — find rightmost insertion point
 *   - bisect($a, $x, lo, hi, key)        — alias for bisect_right
 *   - insort_left(&$a, $x, lo, hi, key)  — insert keeping sorted (left)
 *   - insort_right(&$a, $x, lo, hi, key) — insert keeping sorted (right)
 *   - insort(&$a, $x, lo, hi, key)       — alias for insort_right
 *
 * Usage:
 *   use QXS\pythonic\Bisect;
 *
 *   $sorted = [1, 3, 5, 7, 9];
 *   Bisect::bisect_left($sorted, 5);    // 2
 *   Bisect::bisect_right($sorted, 5);   // 3
 *
 *   Bisect::insort($sorted, 4);         // $sorted → [1, 3, 4, 5, 7, 9]
 *
 *   // With PyList:
 *   $list = new PyList([10, 20, 30]);
 *   Bisect::insort($list, 25);          // $list → [10, 20, 25, 30]
 *
 *   // With key function:
 *   $items = [['age' => 20], ['age' => 30], ['age' => 40]];
 *   Bisect::bisect_left($items, 30, key: fn($x) => $x['age']);  // 1
 */
class Bisect
{
    // ─── bisect (search) ─────────────────────────────────────

    /**
     * Locate the leftmost insertion point for $x in $a to maintain sorted order.
     *
     * If $x is already in $a, the insertion point will be BEFORE (to the left of)
     * any existing entries equal to $x.
     *
     * Python: bisect.bisect_left(a, x, lo=0, hi=len(a), *, key=None)
     *
     * @param array|PyList          $a   Sorted sequence.
     * @param mixed                 $x   Value to locate.
     * @param int                   $lo  Lower bound of slice to search (inclusive).
     * @param int|null              $hi  Upper bound of slice to search (exclusive). null = len(a).
     * @param callable|null         $key A one-argument ordering function.
     * @return int The insertion index.
     */
    public static function bisect_left(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        $arr = $a instanceof PyList ? $a->toPhp() : $a;
        $hi  = $hi ?? count($arr);

        if ($lo < 0) {
            throw new \ValueError('lo must be non-negative');
        }

        $xKey = $key !== null ? $key($x) : $x;

        while ($lo < $hi) {
            $mid    = intdiv($lo + $hi, 2);
            $midKey = $key !== null ? $key($arr[$mid]) : $arr[$mid];
            if ($midKey < $xKey) {
                $lo = $mid + 1;
            } else {
                $hi = $mid;
            }
        }

        return $lo;
    }

    /**
     * Locate the rightmost insertion point for $x in $a to maintain sorted order.
     *
     * If $x is already in $a, the insertion point will be AFTER (to the right of)
     * any existing entries equal to $x.
     *
     * Python: bisect.bisect_right(a, x, lo=0, hi=len(a), *, key=None)
     *
     * @param array|PyList          $a   Sorted sequence.
     * @param mixed                 $x   Value to locate.
     * @param int                   $lo  Lower bound of slice to search (inclusive).
     * @param int|null              $hi  Upper bound of slice to search (exclusive). null = len(a).
     * @param callable|null         $key A one-argument ordering function.
     * @return int The insertion index.
     */
    public static function bisect_right(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        $arr = $a instanceof PyList ? $a->toPhp() : $a;
        $hi  = $hi ?? count($arr);

        if ($lo < 0) {
            throw new \ValueError('lo must be non-negative');
        }

        $xKey = $key !== null ? $key($x) : $x;

        while ($lo < $hi) {
            $mid    = intdiv($lo + $hi, 2);
            $midKey = $key !== null ? $key($arr[$mid]) : $arr[$mid];
            if ($xKey < $midKey) {
                $hi = $mid;
            } else {
                $lo = $mid + 1;
            }
        }

        return $lo;
    }

    /**
     * Alias for bisect_right().
     *
     * Python: bisect.bisect(a, x, lo=0, hi=len(a), *, key=None)
     */
    public static function bisect(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        return self::bisect_right($a, $x, $lo, $hi, $key);
    }

    // ─── insort (insert) ─────────────────────────────────────

    /**
     * Insert $x into $a keeping it sorted (leftmost position).
     *
     * Equivalent to a.insert(bisect_left(a, x, lo, hi, key), x).
     * Works with both plain arrays (by reference) and PyList instances.
     *
     * Python: bisect.insort_left(a, x, lo=0, hi=len(a), *, key=None)
     *
     * @param array|PyList          $a   Sorted sequence (mutated in-place).
     * @param mixed                 $x   Value to insert.
     * @param int                   $lo  Lower bound.
     * @param int|null              $hi  Upper bound.
     * @param callable|null         $key Ordering function.
     */
    public static function insort_left(array|PyList &$a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): void
    {
        $idx = self::bisect_left($a, $x, $lo, $hi, $key);

        if ($a instanceof PyList) {
            $a->insert($idx, $x);
        } else {
            array_splice($a, $idx, 0, [$x]);
        }
    }

    /**
     * Insert $x into $a keeping it sorted (rightmost position).
     *
     * Equivalent to a.insert(bisect_right(a, x, lo, hi, key), x).
     * Works with both plain arrays (by reference) and PyList instances.
     *
     * Python: bisect.insort_right(a, x, lo=0, hi=len(a), *, key=None)
     *
     * @param array|PyList          $a   Sorted sequence (mutated in-place).
     * @param mixed                 $x   Value to insert.
     * @param int                   $lo  Lower bound.
     * @param int|null              $hi  Upper bound.
     * @param callable|null         $key Ordering function.
     */
    public static function insort_right(array|PyList &$a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): void
    {
        $idx = self::bisect_right($a, $x, $lo, $hi, $key);

        if ($a instanceof PyList) {
            $a->insert($idx, $x);
        } else {
            array_splice($a, $idx, 0, [$x]);
        }
    }

    /**
     * Alias for insort_right().
     *
     * Python: bisect.insort(a, x, lo=0, hi=len(a), *, key=None)
     */
    public static function insort(array|PyList &$a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): void
    {
        self::insort_right($a, $x, $lo, $hi, $key);
    }

    // ─── Convenience helpers ────────────────────────────────

    /**
     * Find the index of $x in sorted sequence $a, or return -1 if not found.
     *
     * Uses binary search for O(log n) lookup — the sorted-sequence equivalent
     * of list.index().
     *
     * Python recipe: commonly implemented as index() in bisect examples.
     *
     * @param array|PyList          $a   Sorted sequence.
     * @param mixed                 $x   Value to find.
     * @param int                   $lo  Lower bound.
     * @param int|null              $hi  Upper bound.
     * @param callable|null         $key Ordering function.
     * @return int Index of $x, or -1 if not present.
     */
    public static function index(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        $arr = $a instanceof PyList ? $a->toPhp() : $a;
        $hi  = $hi ?? count($arr);
        $idx = self::bisect_left($a, $x, $lo, $hi, $key);

        if ($idx < $hi) {
            $val = $key !== null ? $key($arr[$idx]) : $arr[$idx];
            $xKey = $key !== null ? $key($x) : $x;
            if ($val == $xKey) {
                return $idx;
            }
        }

        return -1;
    }

    /**
     * Count the number of occurrences of $x in sorted sequence $a.
     *
     * Uses two binary searches for O(log n) counting.
     *
     * @param array|PyList          $a   Sorted sequence.
     * @param mixed                 $x   Value to count.
     * @param int                   $lo  Lower bound.
     * @param int|null              $hi  Upper bound.
     * @param callable|null         $key Ordering function.
     * @return int Number of occurrences.
     */
    public static function count(array|PyList $a, mixed $x, int $lo = 0, ?int $hi = null, ?callable $key = null): int
    {
        return self::bisect_right($a, $x, $lo, $hi, $key) - self::bisect_left($a, $x, $lo, $hi, $key);
    }

    /**
     * Check whether $x exists in sorted sequence $a.
     *
     * O(log n) membership test — much faster than in_array() for sorted data.
     *
     * @param array|PyList          $a   Sorted sequence.
     * @param mixed                 $x   Value to find.
     * @param callable|null         $key Ordering function.
     * @return bool True if $x is present.
     */
    public static function contains(array|PyList $a, mixed $x, ?callable $key = null): bool
    {
        return self::index($a, $x, key: $key) !== -1;
    }
}
