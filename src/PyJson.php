<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python-like json module for PHP.
 *
 * Provides json.dumps() and json.loads() with automatic wrapping
 * of results into Pythonic data structures (PyDict, PyList, PyString, etc.).
 *
 * Unlike PHP's json_encode/json_decode which return raw arrays/strings,
 * PyJson::loads() recursively wraps results into the framework's types:
 *   - JSON objects → PyDict (with nested values also wrapped)
 *   - JSON arrays  → PyList (with nested values also wrapped)
 *   - JSON strings → PyString
 *   - JSON numbers → int|float (unchanged)
 *   - JSON booleans → bool (unchanged)
 *   - JSON null    → null (unchanged)
 *
 * Usage:
 *   $data = PyJson::loads('{"name": "Alice", "scores": [95, 87]}');
 *   // → PyDict with PyString values and PyList of ints
 *   $data['name'];           // PyString("Alice")
 *   $data['scores'][0];      // 95
 *
 *   $json = PyJson::dumps($data);
 *   // → '{"name":"Alice","scores":[95,87]}'
 *
 *   $json = PyJson::dumps($data, indent: 4);
 *   // → pretty-printed JSON
 */
class PyJson
{
    /**
     * Decode a JSON string into Pythonic data structures (recursively).
     *
     * Python equivalent: json.loads(s)
     *
     * @param string $s              The JSON string to decode.
     * @param bool   $wrap           Whether to wrap results into Pythonic types (default: true).
     *                               Set to false to get plain PHP types (like json_decode).
     * @return PyDict|PyList|PyString|int|float|bool|null
     *
     * @throws \JsonException On invalid JSON.
     */
    public static function loads(string $s, bool $wrap = true): mixed
    {
        $decoded = json_decode($s, associative: true, flags: JSON_THROW_ON_ERROR);

        if (!$wrap) {
            return $decoded;
        }

        return self::wrapValue($decoded);
    }

    /**
     * Encode a value to a JSON string.
     *
     * Python equivalent: json.dumps(obj, indent=None, sort_keys=False, ensure_ascii=True)
     *
     * Accepts Pythonic objects (PyDict, PyList, PyTuple, PyString, etc.)
     * as well as plain PHP arrays, strings, numbers, booleans, and null.
     *
     * @param mixed    $obj          The value to encode.
     * @param int|null $indent       Number of spaces for pretty-printing (null = compact).
     * @param bool     $sort_keys    Whether to sort dictionary keys.
     * @param bool     $ensure_ascii Whether to escape non-ASCII characters (default: false for UTF-8).
     * @return string  The JSON string.
     *
     * @throws \JsonException On encoding failure.
     */
    public static function dumps(
        mixed $obj,
        ?int $indent = null,
        bool $sort_keys = false,
        bool $ensure_ascii = false,
    ): string {
        $plain = self::unwrapValue($obj);

        if ($sort_keys) {
            $plain = self::sortKeysRecursive($plain);
        }

        $flags = JSON_THROW_ON_ERROR;
        if ($indent !== null) {
            $flags |= JSON_PRETTY_PRINT;
        }
        if (!$ensure_ascii) {
            $flags |= JSON_UNESCAPED_UNICODE;
        }

        $result = json_encode($plain, $flags);

        // PHP's JSON_PRETTY_PRINT always uses 4 spaces.
        // If a different indent is requested, adjust it.
        if ($indent !== null && $indent !== 4) {
            $result = self::reindent($result, $indent);
        }

        return $result;
    }

    /**
     * Load JSON from a file and return Pythonic data structures.
     *
     * Python equivalent: json.load(fp)
     *
     * @param string $path  File path to read.
     * @param bool   $wrap  Whether to wrap results into Pythonic types.
     * @return mixed
     *
     * @throws \RuntimeException If file cannot be read.
     * @throws \JsonException On invalid JSON.
     */
    public static function load(string $path, bool $wrap = true): mixed
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("FileNotFoundError: No such file: '{$path}'");
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException("IOError: Could not read file: '{$path}'");
        }
        return self::loads($contents, $wrap);
    }

    /**
     * Encode a value and write it to a file.
     *
     * Python equivalent: json.dump(obj, fp)
     *
     * @param mixed    $obj          The value to encode.
     * @param string   $path         File path to write.
     * @param int|null $indent       Number of spaces for pretty-printing.
     * @param bool     $sort_keys    Whether to sort dictionary keys.
     * @param bool     $ensure_ascii Whether to escape non-ASCII characters.
     * @return void
     *
     * @throws \RuntimeException If file cannot be written.
     * @throws \JsonException On encoding failure.
     */
    public static function dump(
        mixed $obj,
        string $path,
        ?int $indent = null,
        bool $sort_keys = false,
        bool $ensure_ascii = false,
    ): void {
        $json = self::dumps($obj, $indent, $sort_keys, $ensure_ascii);
        $result = file_put_contents($path, $json);
        if ($result === false) {
            throw new \RuntimeException("IOError: Could not write to file: '{$path}'");
        }
    }

    // ─── Internal: wrap PHP values into Pythonic types ────────

    /**
     * Recursively wrap a decoded JSON value into Pythonic types.
     */
    private static function wrapValue(mixed $value): mixed
    {
        if (is_array($value)) {
            if (empty($value)) {
                // json_decode with associative:true returns [] for both {} and []
                // We default to PyList for empty; user can check JSON manually if needed
                return new PyList();
            }

            if (array_is_list($value)) {
                // Sequential array → PyList with recursively wrapped items
                return new PyList(array_map(
                    fn($item) => self::wrapValue($item),
                    $value,
                ));
            }

            // Associative array → PyDict with recursively wrapped values
            $wrapped = [];
            foreach ($value as $k => $v) {
                $wrapped[$k] = self::wrapValue($v);
            }
            return new PyDict($wrapped);
        }

        if (is_string($value)) {
            return new PyString($value);
        }

        // int, float, bool, null — return as-is
        return $value;
    }

    // ─── Internal: unwrap Pythonic types to plain PHP ────────

    /**
     * Recursively unwrap Pythonic objects to plain PHP values
     * suitable for json_encode.
     */
    private static function unwrapValue(mixed $value): mixed
    {
        if ($value instanceof PyDict) {
            $result = [];
            foreach ($value->toPhp() as $k => $v) {
                $result[$k] = self::unwrapValue($v);
            }
            return (object)$result; // Ensure JSON object {} output
        }

        if ($value instanceof PyList || $value instanceof PyTuple) {
            return array_map(
                fn($item) => self::unwrapValue($item),
                $value->toPhp(),
            );
        }

        if ($value instanceof PySet || $value instanceof PyFrozenSet) {
            return array_map(
                fn($item) => self::unwrapValue($item),
                $value->toPhp(),
            );
        }

        if ($value instanceof PyString) {
            return $value->toPhp();
        }

        if ($value instanceof PyDeque) {
            return array_map(
                fn($item) => self::unwrapValue($item),
                $value->toPhp(),
            );
        }

        if ($value instanceof PyCounter) {
            $result = [];
            foreach ($value->toPhp() as $k => $v) {
                $result[$k] = self::unwrapValue($v);
            }
            return (object)$result;
        }

        if ($value instanceof PyDefaultDict || $value instanceof PyChainMap) {
            $result = [];
            foreach ($value->toPhp() as $k => $v) {
                $result[$k] = self::unwrapValue($v);
            }
            return (object)$result;
        }

        if ($value instanceof \JsonSerializable) {
            return self::unwrapValue($value->jsonSerialize());
        }

        if (is_array($value)) {
            if (empty($value)) {
                return $value;
            }
            if (array_is_list($value)) {
                return array_map(fn($item) => self::unwrapValue($item), $value);
            }
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = self::unwrapValue($v);
            }
            return (object)$result;
        }

        // scalar: int, float, bool, null, string
        return $value;
    }

    // ─── Internal: sort keys recursively ─────────────────────

    private static function sortKeysRecursive(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $arr = (array)$value;
            ksort($arr);
            $sorted = [];
            foreach ($arr as $k => $v) {
                $sorted[$k] = self::sortKeysRecursive($v);
            }
            return (object)$sorted;
        }

        if (is_array($value)) {
            return array_map(fn($item) => self::sortKeysRecursive($item), $value);
        }

        return $value;
    }

    // ─── Internal: re-indent JSON ────────────────────────────

    /**
     * PHP's JSON_PRETTY_PRINT uses 4 spaces. This adjusts to the
     * requested indent width.
     */
    private static function reindent(string $json, int $indent): string
    {
        $lines = explode("\n", $json);
        $result = [];
        foreach ($lines as $line) {
            // Count leading spaces (PHP uses 4-space indent)
            $stripped = ltrim($line, ' ');
            $leadingSpaces = strlen($line) - strlen($stripped);
            $level = intdiv($leadingSpaces, 4);
            $result[] = str_repeat(' ', $level * $indent) . $stripped;
        }
        return implode("\n", $result);
    }
}
