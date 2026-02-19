<?php

declare(strict_types=1);

namespace QXS\pythonic;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use JsonSerializable;

/**
 * Python-like string for PHP.
 *
 * Features:
 *   - Negative indexing: $s[-1]
 *   - Slicing: $s->slice(0, 5), $s->slice(null, null, -1) (reverse)
 *   - Python str methods: upper, lower, title, strip, split, join, etc.
 *   - f-string interpolation: $s->f(["name" => "World"])
 *   - Fluent chaining
 *   - Multiply: $s->repeat(3) like "abc" * 3
 */
class PyString implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use PyObject;

    protected string $data;

    public function __construct(string $data = '')
    {
        $this->data = $data;
    }

    // ─── Python repr / bool / len ────────────────────────────

    public function __repr(): string
    {
        return "'" . addslashes($this->data) . "'";
    }

    public function __bool(): bool
    {
        return $this->data !== '';
    }

    public function __len(): int
    {
        return mb_strlen($this->data);
    }

    // ─── Indexing (negative index support) ───────────────────

    private function resolveIndex(int $index): int
    {
        $len = mb_strlen($this->data);
        if ($index < 0) $index += $len;
        if ($index < 0 || $index >= $len) {
            throw new \OutOfRangeException("Index out of range");
        }
        return $index;
    }

    // ─── ArrayAccess (character access) ──────────────────────

    public function offsetExists(mixed $offset): bool
    {
        try {
            $this->resolveIndex((int)$offset);
            return true;
        } catch (\OutOfRangeException) {
            return false;
        }
    }

    public function offsetGet(mixed $offset): string
    {
        return mb_substr($this->data, $this->resolveIndex((int)$offset), 1);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException("Python strings are immutable");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException("Python strings are immutable");
    }

    // ─── Countable & IteratorAggregate ───────────────────────

    public function count(): int
    {
        return mb_strlen($this->data);
    }

    public function getIterator(): ArrayIterator
    {
        $chars = [];
        for ($i = 0; $i < mb_strlen($this->data); $i++) {
            $chars[] = mb_substr($this->data, $i, 1);
        }
        return new ArrayIterator($chars);
    }

    // ─── Slicing ─────────────────────────────────────────────

    /**
     * Python-style slice: $s->slice(start, stop, step)
     *   $s->slice(0, 5)           // first 5 chars
     *   $s->slice(-3)             // last 3 chars
     *   $s->slice(null, null, -1) // reverse the string
     */
    public function slice(?int $start = null, ?int $stop = null, int $step = 1): static
    {
        $chars = [];
        $len = mb_strlen($this->data);
        for ($i = 0; $i < $len; $i++) {
            $chars[] = mb_substr($this->data, $i, 1);
        }

        $list = new PyList($chars);
        $sliced = $list->slice($start, $stop, $step);
        return new static(implode('', $sliced->toPhp()));
    }

    // ─── Case methods ────────────────────────────────────────

    public function upper(): static { return new static(mb_strtoupper($this->data)); }
    public function lower(): static { return new static(mb_strtolower($this->data)); }

    public function title(): static
    {
        return new static(mb_convert_case($this->data, MB_CASE_TITLE));
    }

    public function capitalize(): static
    {
        if ($this->data === '') return new static('');
        return new static(
            mb_strtoupper(mb_substr($this->data, 0, 1)) .
            mb_strtolower(mb_substr($this->data, 1))
        );
    }

    public function swapcase(): static
    {
        $result = '';
        for ($i = 0; $i < mb_strlen($this->data); $i++) {
            $ch = mb_substr($this->data, $i, 1);
            $result .= $ch === mb_strtoupper($ch) ? mb_strtolower($ch) : mb_strtoupper($ch);
        }
        return new static($result);
    }

    public function casefold(): static
    {
        return $this->lower();
    }

    // ─── Stripping / Trimming ────────────────────────────────

    public function strip(?string $chars = null): static
    {
        return new static($chars === null ? trim($this->data) : trim($this->data, $chars));
    }

    public function lstrip(?string $chars = null): static
    {
        return new static($chars === null ? ltrim($this->data) : ltrim($this->data, $chars));
    }

    public function rstrip(?string $chars = null): static
    {
        return new static($chars === null ? rtrim($this->data) : rtrim($this->data, $chars));
    }

    // ─── Split / Join ────────────────────────────────────────

    /** Split string into PyList. */
    public function split(?string $sep = null, int $maxsplit = -1): PyList
    {
        if ($sep === null) {
            // Python split() with no args splits on whitespace and removes empty
            $parts = preg_split('/\s+/', trim($this->data), $maxsplit > 0 ? $maxsplit + 1 : -1, PREG_SPLIT_NO_EMPTY);
        } else {
            if ($maxsplit > 0) {
                $parts = explode($sep, $this->data, $maxsplit + 1);
            } else {
                $parts = explode($sep, $this->data);
            }
        }
        return new PyList(array_map(fn($s) => $s, $parts ?: []));
    }

    /** Split from right. */
    public function rsplit(?string $sep = null, int $maxsplit = -1): PyList
    {
        if ($maxsplit <= 0 || $sep === null) {
            return $this->split($sep, $maxsplit);
        }
        // Split from the right by reversing, splitting, and reversing back
        $reversed = strrev($this->data);
        $sepRev = strrev($sep);
        $parts = explode($sepRev, $reversed, $maxsplit + 1);
        return new PyList(array_reverse(array_map('strrev', $parts)));
    }

    /** Split into lines. */
    public function splitlines(bool $keepends = false): PyList
    {
        if ($keepends) {
            $parts = preg_split('/(?<=\r\n|\r|\n)/', $this->data, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $parts = preg_split('/\r\n|\r|\n/', $this->data);
        }
        return new PyList($parts ?: []);
    }

    /** Join an iterable of strings using this string as separator. */
    public function join(iterable $items): static
    {
        $arr = [];
        foreach ($items as $item) {
            $arr[] = (string)$item;
        }
        return new static(implode($this->data, $arr));
    }

    // ─── Search / Replace ────────────────────────────────────

    public function find(string $sub, int $start = 0, ?int $end = null): int
    {
        $haystack = $this->substringForSearch($start, $end);
        $pos = mb_strpos($haystack, $sub);
        return $pos === false ? -1 : $pos + $start;
    }

    public function rfind(string $sub, int $start = 0, ?int $end = null): int
    {
        $haystack = $this->substringForSearch($start, $end);
        $pos = mb_strrpos($haystack, $sub);
        return $pos === false ? -1 : $pos + $start;
    }

    public function index(string $sub, int $start = 0, ?int $end = null): int
    {
        $pos = $this->find($sub, $start, $end);
        if ($pos === -1) throw new \ValueError("substring not found");
        return $pos;
    }

    public function rindex(string $sub, int $start = 0, ?int $end = null): int
    {
        $pos = $this->rfind($sub, $start, $end);
        if ($pos === -1) throw new \ValueError("substring not found");
        return $pos;
    }

    public function replace(string $old, string $new, int $count = -1): static
    {
        if ($count < 0) {
            return new static(str_replace($old, $new, $this->data));
        }
        $result = $this->data;
        $pos = 0;
        for ($i = 0; $i < $count; $i++) {
            $found = strpos($result, $old, $pos);
            if ($found === false) break;
            $result = substr_replace($result, $new, $found, strlen($old));
            $pos = $found + strlen($new);
        }
        return new static($result);
    }

    public function contains(string $sub): bool
    {
        return str_contains($this->data, $sub);
    }

    public function in(string $sub): bool
    {
        return $this->contains($sub);
    }

    public function startswith(string $prefix, int $start = 0, ?int $end = null): bool
    {
        $haystack = $this->substringForSearch($start, $end);
        return str_starts_with($haystack, $prefix);
    }

    public function endswith(string $suffix, int $start = 0, ?int $end = null): bool
    {
        $haystack = $this->substringForSearch($start, $end);
        return str_ends_with($haystack, $suffix);
    }

    public function countOf(string $sub, int $start = 0, ?int $end = null): int
    {
        $haystack = $this->substringForSearch($start, $end);
        return mb_substr_count($haystack, $sub);
    }

    // ─── Padding / Alignment ─────────────────────────────────

    public function center(int $width, string $fillchar = ' '): static
    {
        $len = mb_strlen($this->data);
        if ($width <= $len) return new static($this->data);
        $total = $width - $len;
        $left = intdiv($total, 2);
        $right = $total - $left;
        return new static(str_repeat($fillchar, $left) . $this->data . str_repeat($fillchar, $right));
    }

    public function ljust(int $width, string $fillchar = ' '): static
    {
        $len = mb_strlen($this->data);
        if ($width <= $len) return new static($this->data);
        return new static($this->data . str_repeat($fillchar, $width - $len));
    }

    public function rjust(int $width, string $fillchar = ' '): static
    {
        $len = mb_strlen($this->data);
        if ($width <= $len) return new static($this->data);
        return new static(str_repeat($fillchar, $width - $len) . $this->data);
    }

    public function zfill(int $width): static
    {
        $len = mb_strlen($this->data);
        if ($width <= $len) return new static($this->data);
        $sign = '';
        $rest = $this->data;
        if ($rest !== '' && ($rest[0] === '+' || $rest[0] === '-')) {
            $sign = $rest[0];
            $rest = substr($rest, 1);
        }
        return new static($sign . str_repeat('0', $width - $len) . $rest);
    }

    // ─── Character tests ─────────────────────────────────────

    public function isdigit(): bool { return $this->data !== '' && ctype_digit($this->data); }
    public function isalpha(): bool { return $this->data !== '' && ctype_alpha($this->data); }
    public function isalnum(): bool { return $this->data !== '' && ctype_alnum($this->data); }
    public function isspace(): bool { return $this->data !== '' && ctype_space($this->data); }
    public function isupper(): bool { return $this->data !== '' && $this->data === mb_strtoupper($this->data) && preg_match('/[A-Z]/u', $this->data); }
    public function islower(): bool { return $this->data !== '' && $this->data === mb_strtolower($this->data) && preg_match('/[a-z]/u', $this->data); }

    public function isnumeric(): bool
    {
        return $this->data !== '' && is_numeric($this->data);
    }

    // ─── f-string interpolation ──────────────────────────────

    /**
     * f-string style interpolation:
     *   py("Hello {name}, you are {age}")->f(["name" => "Alice", "age" => 30])
     *   // "Hello Alice, you are 30"
     */
    public function f(array $vars): static
    {
        $result = $this->data;
        foreach ($vars as $key => $value) {
            $result = str_replace('{' . $key . '}', (string)$value, $result);
        }
        return new static($result);
    }

    /**
     * Python str.format() compatible:
     *   py("Hello {0}, you are {1}!")->format("Alice", 30)
     */
    public function format(mixed ...$args): static
    {
        $result = $this->data;
        foreach ($args as $i => $value) {
            $result = str_replace('{' . $i . '}', (string)$value, $result);
        }
        return new static($result);
    }

    // ─── Encoding / Misc ─────────────────────────────────────

    /** Repeat: $s->repeat(3) like "abc" * 3 */
    public function repeat(int $n): static
    {
        return new static(str_repeat($this->data, max(0, $n)));
    }

    /** Python-like partition(sep) → [before, sep, after] */
    public function partition(string $sep): PyList
    {
        $pos = mb_strpos($this->data, $sep);
        if ($pos === false) {
            return new PyList([$this->data, '', '']);
        }
        return new PyList([
            mb_substr($this->data, 0, $pos),
            $sep,
            mb_substr($this->data, $pos + mb_strlen($sep))
        ]);
    }

    public function rpartition(string $sep): PyList
    {
        $pos = mb_strrpos($this->data, $sep);
        if ($pos === false) {
            return new PyList(['', '', $this->data]);
        }
        return new PyList([
            mb_substr($this->data, 0, $pos),
            $sep,
            mb_substr($this->data, $pos + mb_strlen($sep))
        ]);
    }

    /** Expand tabs. */
    public function expandtabs(int $tabsize = 8): static
    {
        return new static(str_replace("\t", str_repeat(' ', $tabsize), $this->data));
    }

    /** Encode to bytes (returns plain string in PHP). */
    public function encode(string $encoding = 'utf-8'): string
    {
        return mb_convert_encoding($this->data, $encoding);
    }

    // ─── Map / functional on characters ──────────────────────

    /** Map each character. */
    public function map(callable $fn): static
    {
        $result = '';
        for ($i = 0; $i < mb_strlen($this->data); $i++) {
            $result .= $fn(mb_substr($this->data, $i, 1), $i);
        }
        return new static($result);
    }

    /** Filter characters by predicate. */
    public function filter(callable $fn): static
    {
        $result = '';
        for ($i = 0; $i < mb_strlen($this->data); $i++) {
            $ch = mb_substr($this->data, $i, 1);
            if ($fn($ch, $i)) {
                $result .= $ch;
            }
        }
        return new static($result);
    }

    // ─── Regex helpers ───────────────────────────────────────

    /** Match regex, return PyList of matches (or empty). */
    public function re_findall(string $pattern): PyList
    {
        preg_match_all($pattern, $this->data, $matches);
        return new PyList($matches[0] ?? []);
    }

    /** Replace with regex. */
    public function re_sub(string $pattern, string|callable $replacement, int $limit = -1): static
    {
        if (is_callable($replacement)) {
            $result = preg_replace_callback($pattern, $replacement, $this->data, $limit);
        } else {
            $result = preg_replace($pattern, $replacement, $this->data, $limit);
        }
        return new static($result ?? $this->data);
    }

    /** Check if string matches regex. */
    public function re_match(string $pattern): bool
    {
        return (bool)preg_match($pattern, $this->data);
    }

    // ─── Conversion ──────────────────────────────────────────

    public function toPhp(): string
    {
        return $this->data;
    }

    public function toList(): PyList
    {
        $chars = [];
        for ($i = 0; $i < mb_strlen($this->data); $i++) {
            $chars[] = mb_substr($this->data, $i, 1);
        }
        return new PyList($chars);
    }

    public function toInt(int $base = 10): int
    {
        return intval($this->data, $base);
    }

    public function toFloat(): float
    {
        return (float)$this->data;
    }

    public function jsonSerialize(): string
    {
        return $this->data;
    }

    public function copy(): static
    {
        return new static($this->data);
    }

    /** Get the raw PHP string. */
    public function raw(): string
    {
        return $this->data;
    }

    // ─── Concatenation ───────────────────────────────────────

    /** Concatenate: $s->concat(" world") */
    public function concat(string|self $other): static
    {
        $otherStr = $other instanceof self ? $other->raw() : $other;
        return new static($this->data . $otherStr);
    }

    // ─── Internal helpers ────────────────────────────────────

    private function substringForSearch(int $start, ?int $end): string
    {
        $len = mb_strlen($this->data);
        if ($start < 0) $start += $len;
        $start = max(0, $start);
        if ($end === null) $end = $len;
        if ($end < 0) $end += $len;
        $end = min($end, $len);
        return mb_substr($this->data, $start, $end - $start);
    }
}
