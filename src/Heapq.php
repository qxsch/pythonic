<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python heapq module for PHP.
 *
 * Provides heap operations on PyList instances, maintaining the
 * min-heap invariant (smallest element at index 0).
 *
 * All operations work on PyList in-place (except nlargest, nsmallest, merge).
 *
 * Usage:
 *   $heap = new PyList();
 *   Heapq::heappush($heap, 5);
 *   Heapq::heappush($heap, 1);
 *   Heapq::heappush($heap, 3);
 *   Heapq::heappop($heap);  // 1
 *   Heapq::heappop($heap);  // 3
 *
 *   $data = new PyList([3, 1, 4, 1, 5, 9]);
 *   Heapq::heapify($data);  // in-place transform to heap
 *
 *   Heapq::nlargest(3, [5, 1, 8, 3, 9, 2]); // PyList([9, 8, 5])
 *   Heapq::nsmallest(3, [5, 1, 8, 3, 9, 2]); // PyList([1, 2, 3])
 */
class Heapq
{
    /**
     * Push an item onto the heap, maintaining the heap invariant.
     *
     * Python equivalent: heapq.heappush(heap, item)
     *
     * @param PyList $heap The heap (min-heap).
     * @param mixed  $item The item to push.
     * @return void
     */
    public static function heappush(PyList $heap, mixed $item): void
    {
        $heap->append($item);
        self::siftDown($heap, 0, count($heap) - 1);
    }

    /**
     * Pop and return the smallest item from the heap.
     *
     * Python equivalent: heapq.heappop(heap)
     *
     * @param PyList $heap The heap.
     * @return mixed The smallest item.
     * @throws \UnderflowException If heap is empty.
     */
    public static function heappop(PyList $heap): mixed
    {
        $n = count($heap);
        if ($n === 0) {
            throw new \UnderflowException("IndexError: index out of range (heap is empty)");
        }
        $last = $heap[$n - 1];
        unset($heap[$n - 1]); // remove last

        if ($n - 1 > 0) {
            $returnItem = $heap[0];
            $heap[0] = $last;
            self::siftUp($heap, 0);
            return $returnItem;
        }
        return $last;
    }

    /**
     * Pop the smallest item and push a new item — more efficient than
     * separate heappop + heappush.
     *
     * Python equivalent: heapq.heapreplace(heap, item)
     *
     * @param PyList $heap The heap.
     * @param mixed  $item The new item to push.
     * @return mixed The previous smallest item.
     */
    public static function heapreplace(PyList $heap, mixed $item): mixed
    {
        if (count($heap) === 0) {
            throw new \UnderflowException("IndexError: index out of range (heap is empty)");
        }
        $returnItem = $heap[0];
        $heap[0] = $item;
        self::siftUp($heap, 0);
        return $returnItem;
    }

    /**
     * Push then pop — more efficient than separate heappush + heappop.
     *
     * Python equivalent: heapq.heappushpop(heap, item)
     *
     * @param PyList $heap The heap.
     * @param mixed  $item The item to push.
     * @return mixed The smallest item (may be the item itself).
     */
    public static function heappushpop(PyList $heap, mixed $item): mixed
    {
        if (count($heap) > 0 && $heap[0] < $item) {
            $returnItem = $heap[0];
            $heap[0] = $item;
            self::siftUp($heap, 0);
            return $returnItem;
        }
        return $item;
    }

    /**
     * Transform a PyList into a heap, in-place.
     *
     * Python equivalent: heapq.heapify(x)
     *
     * @param PyList $list The list to heapify.
     * @return void
     */
    public static function heapify(PyList $list): void
    {
        $n = count($list);
        // Start from last parent node and sift up
        for ($i = intdiv($n, 2) - 1; $i >= 0; $i--) {
            self::siftUp($list, $i);
        }
    }

    /**
     * Return the n largest elements from the iterable.
     *
     * Python equivalent: heapq.nlargest(n, iterable, key=None)
     *
     * @param int            $n        Number of elements.
     * @param iterable       $iterable Source data.
     * @param \Closure|null  $key      Optional key function.
     * @return PyList Sorted descending (largest first).
     */
    public static function nlargest(int $n, iterable $iterable, ?\Closure $key = null): PyList
    {
        $arr = self::toArray($iterable);
        if ($key !== null) {
            usort($arr, fn($a, $b) => self::compare($key($b), $key($a)));
        } else {
            usort($arr, fn($a, $b) => self::compare($b, $a));
        }
        return new PyList(array_slice($arr, 0, $n));
    }

    /**
     * Return the n smallest elements from the iterable.
     *
     * Python equivalent: heapq.nsmallest(n, iterable, key=None)
     *
     * @param int            $n        Number of elements.
     * @param iterable       $iterable Source data.
     * @param \Closure|null  $key      Optional key function.
     * @return PyList Sorted ascending (smallest first).
     */
    public static function nsmallest(int $n, iterable $iterable, ?\Closure $key = null): PyList
    {
        $arr = self::toArray($iterable);
        if ($key !== null) {
            usort($arr, fn($a, $b) => self::compare($key($a), $key($b)));
        } else {
            usort($arr, fn($a, $b) => self::compare($a, $b));
        }
        return new PyList(array_slice($arr, 0, $n));
    }

    /**
     * Merge multiple sorted iterables into a single sorted iterator → PyList.
     *
     * Python equivalent: heapq.merge(*iterables, key=None, reverse=False)
     *
     * @param iterable      ...$iterables Multiple sorted iterables.
     * @return PyList Merged sorted list.
     */
    public static function merge(iterable ...$iterables): PyList
    {
        $merged = [];
        foreach ($iterables as $iter) {
            foreach ($iter as $item) {
                $merged[] = $item;
            }
        }
        usort($merged, fn($a, $b) => self::compare($a, $b));
        return new PyList($merged);
    }

    // ─── Internal heap operations ────────────────────────────

    /**
     * Sift down: move node at pos up toward root to restore heap property.
     * (Used after inserting at the end.)
     */
    private static function siftDown(PyList $heap, int $startPos, int $pos): void
    {
        $newItem = $heap[$pos];
        while ($pos > $startPos) {
            $parentPos = intdiv($pos - 1, 2);
            $parent = $heap[$parentPos];
            if (self::compare($newItem, $parent) < 0) {
                $heap[$pos] = $parent;
                $pos = $parentPos;
            } else {
                break;
            }
        }
        $heap[$pos] = $newItem;
    }

    /**
     * Sift up: move node at pos down to proper position.
     * (Used after replacing the root.)
     */
    private static function siftUp(PyList $heap, int $pos): void
    {
        $n = count($heap);
        $startPos = $pos;
        $newItem = $heap[$pos];
        // Bubble up the smaller child until hitting a leaf
        $childPos = 2 * $pos + 1;
        while ($childPos < $n) {
            // Set childPos to the smaller child
            $rightPos = $childPos + 1;
            if ($rightPos < $n && self::compare($heap[$childPos], $heap[$rightPos]) >= 0) {
                $childPos = $rightPos;
            }
            // Move the smaller child up
            $heap[$pos] = $heap[$childPos];
            $pos = $childPos;
            $childPos = 2 * $pos + 1;
        }
        // The item is now at a leaf; sift it back down from there
        $heap[$pos] = $newItem;
        self::siftDown($heap, $startPos, $pos);
    }

    /**
     * Generic comparison that works with numbers, strings, and comparable objects.
     */
    private static function compare(mixed $a, mixed $b): int
    {
        if ($a === $b) return 0;
        if ($a < $b) return -1;
        return 1;
    }

    /**
     * Convert any iterable to a plain PHP array.
     */
    private static function toArray(iterable $iterable): array
    {
        if ($iterable instanceof PyList || $iterable instanceof PyTuple) {
            return $iterable->toPhp();
        }
        if (is_array($iterable)) {
            return $iterable;
        }
        return iterator_to_array($iterable, false);
    }
}
