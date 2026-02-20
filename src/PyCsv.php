<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python csv module for PHP.
 *
 * Provides csv.reader(), csv.writer(), csv.DictReader, csv.DictWriter
 * functionality with automatic wrapping into Pythonic data structures.
 *
 * All reader methods return framework types:
 *   - reader()     → PyList of PyList rows
 *   - DictReader() → PyList of PyDict rows
 *   - writer()     → writes to file/handle
 *   - DictWriter() → writes dicts to file/handle
 *
 * Usage:
 *   // Reading a CSV file
 *   $rows = PyCsv::reader('/path/to/file.csv');
 *   // → PyList([PyList(['Alice', '30']), PyList(['Bob', '25'])])
 *
 *   $rows = PyCsv::DictReader('/path/to/file.csv');
 *   // → PyList([PyDict({'name': 'Alice', 'age': '30'}), ...])
 *
 *   // Reading from a string
 *   $rows = PyCsv::reader_from_string("name,age\nAlice,30\nBob,25");
 *
 *   // Writing
 *   PyCsv::writer('/path/to/out.csv', [['name', 'age'], ['Alice', '30']]);
 *   PyCsv::DictWriter('/path/to/out.csv', ['name', 'age'], [
 *       ['name' => 'Alice', 'age' => '30'],
 *   ]);
 */
class PyCsv
{
    /**
     * Read a CSV file → PyList of PyList rows.
     *
     * Python equivalent: csv.reader(open(path))
     *
     * @param string $path       File path to read.
     * @param string $delimiter  Column delimiter (default: ',').
     * @param string $enclosure  Field enclosure character (default: '"').
     * @param string $escape     Escape character (default: '\\').
     * @return PyList<PyList> Each row is a PyList of string values.
     *
     * @throws \RuntimeException If file cannot be read.
     */
    public static function reader(
        string $path,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): PyList {
        $handle = self::openFile($path, 'r');
        try {
            return self::readRows($handle, $delimiter, $enclosure, $escape);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Read CSV from a string → PyList of PyList rows.
     *
     * @param string $csv        The CSV content as a string.
     * @param string $delimiter  Column delimiter (default: ',').
     * @param string $enclosure  Field enclosure character (default: '"').
     * @param string $escape     Escape character (default: '\\').
     * @return PyList<PyList>
     */
    public static function reader_from_string(
        string $csv,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): PyList {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csv);
        rewind($handle);
        try {
            return self::readRows($handle, $delimiter, $enclosure, $escape);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Read a CSV file with headers → PyList of PyDict rows.
     *
     * Python equivalent: csv.DictReader(open(path))
     *
     * The first row is used as field names. Each subsequent row becomes a PyDict
     * with PyString keys matching the header and PyString values.
     *
     * @param string       $path       File path to read.
     * @param array|null   $fieldnames Optional explicit field names (overrides header row).
     * @param string       $delimiter  Column delimiter (default: ',').
     * @param string       $enclosure  Field enclosure character (default: '"').
     * @param string       $escape     Escape character (default: '\\').
     * @return PyList<PyDict>
     *
     * @throws \RuntimeException If file cannot be read.
     */
    public static function DictReader(
        string $path,
        ?array $fieldnames = null,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): PyList {
        $handle = self::openFile($path, 'r');
        try {
            return self::readDictRows($handle, $fieldnames, $delimiter, $enclosure, $escape);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Read CSV from a string with headers → PyList of PyDict rows.
     *
     * @param string     $csv        The CSV content as a string.
     * @param array|null $fieldnames Optional explicit field names.
     * @param string     $delimiter  Column delimiter (default: ',').
     * @param string     $enclosure  Field enclosure character (default: '"').
     * @param string     $escape     Escape character (default: '\\').
     * @return PyList<PyDict>
     */
    public static function DictReader_from_string(
        string $csv,
        ?array $fieldnames = null,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): PyList {
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $csv);
        rewind($handle);
        try {
            return self::readDictRows($handle, $fieldnames, $delimiter, $enclosure, $escape);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Write rows to a CSV file.
     *
     * Python equivalent: csv.writer(open(path, 'w')).writerows(rows)
     *
     * @param string          $path       File path to write.
     * @param iterable        $rows       Rows to write (each row is an iterable of values).
     * @param string          $delimiter  Column delimiter (default: ',').
     * @param string          $enclosure  Field enclosure character (default: '"').
     * @param string          $escape     Escape character (default: '\\').
     * @return void
     *
     * @throws \RuntimeException If file cannot be written.
     */
    public static function writer(
        string $path,
        iterable $rows,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): void {
        $handle = self::openFile($path, 'w');
        try {
            foreach ($rows as $row) {
                $rowArr = self::rowToArray($row);
                fputcsv($handle, $rowArr, $delimiter, $enclosure, $escape);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Write dicts to a CSV file with a header row.
     *
     * Python equivalent: csv.DictWriter(open(path, 'w'), fieldnames=fieldnames)
     *
     * @param string          $path       File path to write.
     * @param array           $fieldnames Column names (used as header and key order).
     * @param iterable        $rows       Rows to write (each row is an associative array/PyDict).
     * @param string          $delimiter  Column delimiter.
     * @param string          $enclosure  Field enclosure character.
     * @param string          $escape     Escape character.
     * @return void
     *
     * @throws \RuntimeException If file cannot be written.
     */
    public static function DictWriter(
        string $path,
        array $fieldnames,
        iterable $rows,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): void {
        $handle = self::openFile($path, 'w');
        try {
            // Write header
            fputcsv($handle, $fieldnames, $delimiter, $enclosure, $escape);

            // Write data rows
            foreach ($rows as $row) {
                $rowArr = ($row instanceof PyDict || $row instanceof PyOrderedDict) ? $row->toPhp() : (array)$row;
                $orderedRow = [];
                foreach ($fieldnames as $field) {
                    $orderedRow[] = (string)($rowArr[$field] ?? '');
                }
                fputcsv($handle, $orderedRow, $delimiter, $enclosure, $escape);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Write rows to a CSV string (in-memory).
     *
     * @param iterable $rows      Rows to write.
     * @param string   $delimiter Column delimiter.
     * @param string   $enclosure Field enclosure character.
     * @param string   $escape    Escape character.
     * @return PyString The CSV content as a PyString.
     */
    public static function writer_to_string(
        iterable $rows,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): PyString {
        $handle = fopen('php://memory', 'r+');
        foreach ($rows as $row) {
            $rowArr = self::rowToArray($row);
            fputcsv($handle, $rowArr, $delimiter, $enclosure, $escape);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        return new PyString($content);
    }

    /**
     * Write dicts to a CSV string with header row (in-memory).
     *
     * @param array    $fieldnames Column names.
     * @param iterable $rows       Rows to write.
     * @param string   $delimiter  Column delimiter.
     * @param string   $enclosure  Field enclosure character.
     * @param string   $escape     Escape character.
     * @return PyString The CSV content as a PyString.
     */
    public static function DictWriter_to_string(
        array $fieldnames,
        iterable $rows,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
    ): PyString {
        $handle = fopen('php://memory', 'r+');

        // header
        fputcsv($handle, $fieldnames, $delimiter, $enclosure, $escape);

        foreach ($rows as $row) {
            $rowArr = ($row instanceof PyDict || $row instanceof PyOrderedDict) ? $row->toPhp() : (array)$row;
            $orderedRow = [];
            foreach ($fieldnames as $field) {
                $orderedRow[] = (string)($rowArr[$field] ?? '');
            }
            fputcsv($handle, $orderedRow, $delimiter, $enclosure, $escape);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        return new PyString($content);
    }

    // ─── Internal helpers ────────────────────────────────────

    /**
     * @throws \RuntimeException
     */
    private static function openFile(string $path, string $mode): mixed
    {
        if ($mode === 'r' && !file_exists($path)) {
            throw new \RuntimeException("FileNotFoundError: No such file: '{$path}'");
        }
        $handle = fopen($path, $mode);
        if ($handle === false) {
            throw new \RuntimeException("IOError: Could not open file: '{$path}'");
        }
        return $handle;
    }

    /**
     * Read all rows from a file handle into PyList of PyList.
     * @param resource $handle
     */
    private static function readRows(
        mixed $handle,
        string $delimiter,
        string $enclosure,
        string $escape,
    ): PyList {
        $rows = [];
        while (($line = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
            $rows[] = new PyList(array_map(fn($v) => new PyString((string)$v), $line));
        }
        return new PyList($rows);
    }

    /**
     * Read dict rows from a file handle.
     * @param resource $handle
     */
    private static function readDictRows(
        mixed $handle,
        ?array $fieldnames,
        string $delimiter,
        string $enclosure,
        string $escape,
    ): PyList {
        // Get headers
        if ($fieldnames === null) {
            $headerLine = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
            if ($headerLine === false) {
                return new PyList();
            }
            $fieldnames = $headerLine;
        }

        $rows = [];
        while (($line = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
            $dict = [];
            foreach ($fieldnames as $i => $field) {
                $dict[$field] = new PyString((string)($line[$i] ?? ''));
            }
            $rows[] = new PyDict($dict);
        }
        return new PyList($rows);
    }

    /**
     * Convert a row (PyList, PyTuple, array) to a plain PHP array of strings.
     */
    private static function rowToArray(mixed $row): array
    {
        if ($row instanceof PyList || $row instanceof PyTuple) {
            return array_map('strval', $row->toPhp());
        }
        if (is_array($row)) {
            return array_map('strval', $row);
        }
        return [(string)$row];
    }
}
