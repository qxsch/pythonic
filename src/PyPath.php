<?php

declare(strict_types=1);

namespace QXS\pythonic;

use Stringable;
use JsonSerializable;

/**
 * Python pathlib.Path for PHP.
 *
 * Object-oriented filesystem paths — immutable, chainable, expressive.
 *
 * Usage:
 *   $p = new PyPath('/home/user/docs/file.txt');
 *   $p->parent             // PyPath('/home/user/docs')
 *   $p->name               // 'file.txt'
 *   $p->stem               // 'file'
 *   $p->suffix             // '.txt'
 *   $p->suffixes           // ['.txt']
 *   $p->parts              // ['/', 'home', 'user', 'docs', 'file.txt']
 *
 *   $p->join('sub', 'file.txt')    // PyPath('/home/user/docs/sub/file.txt')
 *   $p->with_name('other.md')      // PyPath('/home/user/docs/other.md')
 *   $p->with_suffix('.md')         // PyPath('/home/user/docs/file.md')
 *
 *   $p->exists()                   // bool
 *   $p->is_file()                  // bool
 *   $p->is_dir()                   // bool
 *   $p->read_text()                // string
 *   $p->write_text('content')      // int (bytes written)
 *   $p->read_bytes()               // string (binary)
 *   $p->write_bytes('data')        // int
 *   $p->glob('*.txt')              // PyList of PyPath
 *   $p->iterdir()                  // PyList of PyPath (directory entries)
 *   $p->mkdir(recursive: true)     // create directory
 *   $p->unlink()                   // delete file
 *   $p->rmdir()                    // delete empty directory
 *   $p->rename($target)            // rename/move
 *   $p->stat()                     // array of file stats
 *   $p->touch()                    // create file or update mtime
 */
class PyPath implements Stringable, JsonSerializable
{
    protected string $path;

    public function __construct(string $path)
    {
        // Normalize separators to /
        $this->path = rtrim(str_replace('\\', '/', $path), '/') ?: '/';
    }

    // ─── String conversion ───────────────────────────────────

    public function __toString(): string
    {
        return $this->path;
    }

    public function __repr(): string
    {
        return "PyPath('" . $this->path . "')";
    }

    public function jsonSerialize(): string
    {
        return $this->path;
    }

    // ─── Path components (properties via magic __get) ────────

    public function __get(string $name): mixed
    {
        return match ($name) {
            'parent' => $this->parent(),
            'name' => $this->name(),
            'stem' => $this->stem(),
            'suffix' => $this->suffix(),
            'suffixes' => $this->suffixes(),
            'parts' => $this->parts(),
            'anchor' => $this->anchor(),
            'root' => $this->root(),
            default => throw new \OutOfRangeException("PyPath has no property '{$name}'"),
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['parent', 'name', 'stem', 'suffix', 'suffixes', 'parts', 'anchor', 'root']);
    }

    /**
     * Parent directory.
     */
    public function parent(): static
    {
        $dir = dirname($this->path);
        return new static($dir === '.' ? '' : $dir);
    }

    /**
     * Just the filename: 'file.txt'
     */
    public function name(): string
    {
        return basename($this->path);
    }

    /**
     * Filename without final suffix: 'file'
     */
    public function stem(): string
    {
        $name = $this->name();
        $pos = strrpos($name, '.');
        return $pos === false ? $name : substr($name, 0, $pos);
    }

    /**
     * Final file suffix: '.txt'
     */
    public function suffix(): string
    {
        $name = $this->name();
        $pos = strrpos($name, '.');
        return $pos === false ? '' : substr($name, $pos);
    }

    /**
     * All suffixes: '.tar.gz' → ['.tar', '.gz']
     */
    public function suffixes(): array
    {
        $name = $this->name();
        $parts = explode('.', $name);
        if (count($parts) <= 1) return [];
        array_shift($parts); // remove stem
        return array_map(fn($s) => '.' . $s, $parts);
    }

    /**
     * Tuple of path components.
     */
    public function parts(): array
    {
        $parts = [];
        $path = $this->path;

        // Handle root/anchor
        if (str_starts_with($path, '/')) {
            $parts[] = '/';
            $path = ltrim($path, '/');
        } elseif (preg_match('#^([a-zA-Z]:)/#', $path, $m)) {
            $parts[] = $m[1] . '/';
            $path = substr($path, strlen($m[1]) + 1);
        }

        if ($path !== '') {
            $parts = array_merge($parts, explode('/', $path));
        }

        return $parts;
    }

    /**
     * The root of the path: '/' or 'C:/'
     */
    public function root(): string
    {
        if (str_starts_with($this->path, '/')) return '/';
        if (preg_match('#^([a-zA-Z]:)/#', $this->path, $m)) return $m[1] . '/';
        return '';
    }

    /**
     * Alias for root.
     */
    public function anchor(): string
    {
        return $this->root();
    }

    // ─── Path operations ─────────────────────────────────────

    /**
     * Join path segments (like / operator in Python pathlib).
     *   $p->join('sub', 'file.txt')
     */
    public function join(string ...$segments): static
    {
        $result = $this->path;
        foreach ($segments as $seg) {
            $seg = str_replace('\\', '/', $seg);
            // If segment is absolute, it replaces the path
            if (str_starts_with($seg, '/') || preg_match('#^[a-zA-Z]:/#', $seg)) {
                $result = $seg;
            } else {
                $result = rtrim($result, '/') . '/' . $seg;
            }
        }
        return new static($result);
    }

    /**
     * Shorthand for join — use $p / 'subdir' style if PHP supported it,
     * or use $p->div('subdir') as an alternative.
     */
    public function div(string ...$segments): static
    {
        return $this->join(...$segments);
    }

    /**
     * Return a new path with the filename changed.
     */
    public function with_name(string $name): static
    {
        return $this->parent()->join($name);
    }

    /**
     * Return a new path with the suffix changed.
     */
    public function with_suffix(string $suffix): static
    {
        return $this->parent()->join($this->stem() . $suffix);
    }

    /**
     * Return a new path with the stem changed.
     */
    public function with_stem(string $stem): static
    {
        return $this->parent()->join($stem . $this->suffix());
    }

    /**
     * Is the path absolute?
     */
    public function is_absolute(): bool
    {
        return str_starts_with($this->path, '/') || (bool)preg_match('#^[a-zA-Z]:/#', $this->path);
    }

    /**
     * Resolve the path to absolute, normalizing '..' and '.'.
     */
    public function resolve(): static
    {
        $real = realpath($this->toNative());
        if ($real === false) {
            // Best-effort normalization
            return new static($this->toNative());
        }
        return new static($real);
    }

    /**
     * Make this path relative to another.
     */
    public function relative_to(string|self $other): static
    {
        $base = rtrim((string)$other, '/') . '/';
        $base = str_replace('\\', '/', $base);
        if (!str_starts_with($this->path, $base)) {
            throw new \ValueError("'{$this->path}' is not relative to '{$base}'");
        }
        return new static(substr($this->path, strlen($base)));
    }

    // ─── Filesystem queries ──────────────────────────────────

    public function exists(): bool
    {
        return file_exists($this->toNative());
    }

    public function is_file(): bool
    {
        return is_file($this->toNative());
    }

    public function is_dir(): bool
    {
        return is_dir($this->toNative());
    }

    public function is_link(): bool
    {
        return is_link($this->toNative());
    }

    /**
     * File size in bytes.
     */
    public function size(): int
    {
        $s = filesize($this->toNative());
        if ($s === false) throw new \RuntimeException("Cannot get size of '{$this->path}'");
        return $s;
    }

    /**
     * stat() — array of file metadata.
     */
    public function stat(): array
    {
        $s = stat($this->toNative());
        if ($s === false) throw new \RuntimeException("Cannot stat '{$this->path}'");
        return $s;
    }

    // ─── Read / Write ────────────────────────────────────────

    /**
     * Read entire file as text.
     */
    public function read_text(string $encoding = 'UTF-8'): string
    {
        $content = file_get_contents($this->toNative());
        if ($content === false) throw new \RuntimeException("Cannot read '{$this->path}'");
        return $content;
    }

    /**
     * Write text to file (creates or truncates).
     * @return int Bytes written.
     */
    public function write_text(string $data): int
    {
        $result = file_put_contents($this->toNative(), $data);
        if ($result === false) throw new \RuntimeException("Cannot write to '{$this->path}'");
        return $result;
    }

    /**
     * Read entire file as binary bytes.
     */
    public function read_bytes(): string
    {
        return $this->read_text();
    }

    /**
     * Write binary data.
     */
    public function write_bytes(string $data): int
    {
        return $this->write_text($data);
    }

    /**
     * Append text to file.
     */
    public function append_text(string $data): int
    {
        $result = file_put_contents($this->toNative(), $data, FILE_APPEND);
        if ($result === false) throw new \RuntimeException("Cannot append to '{$this->path}'");
        return $result;
    }

    /**
     * Read lines as a PyList.
     */
    public function read_lines(bool $stripNewlines = true): PyList
    {
        $content = $this->read_text();
        $lines = explode("\n", $content);
        if ($stripNewlines) {
            $lines = array_map(fn($l) => rtrim($l, "\r"), $lines);
        }
        // Remove trailing empty line from final newline
        if (end($lines) === '') array_pop($lines);
        return new PyList($lines);
    }

    // ─── Directory operations ────────────────────────────────

    /**
     * List directory entries.
     */
    public function iterdir(): PyList
    {
        $native = $this->toNative();
        if (!is_dir($native)) throw new \RuntimeException("'{$this->path}' is not a directory");
        $entries = [];
        foreach (scandir($native) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $entries[] = new static($this->path . '/' . $entry);
        }
        return new PyList($entries);
    }

    /**
     * Glob pattern matching within this directory.
     * @return PyList<PyPath>
     */
    public function glob(string $pattern): PyList
    {
        $native = rtrim($this->toNative(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pattern;
        $matches = glob($native) ?: [];
        return new PyList(array_map(fn($p) => new static($p), $matches));
    }

    /**
     * Recursive glob.
     * @return PyList<PyPath>
     */
    public function rglob(string $pattern): PyList
    {
        $results = [];
        $this->rglobRecursive($this->toNative(), $pattern, $results);
        return new PyList(array_map(fn($p) => new static($p), $results));
    }

    private function rglobRecursive(string $dir, string $pattern, array &$results): void
    {
        foreach (glob($dir . DIRECTORY_SEPARATOR . $pattern) ?: [] as $match) {
            $results[] = $match;
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $subdir) {
            $this->rglobRecursive($subdir, $pattern, $results);
        }
    }

    /**
     * Create directory.
     */
    public function mkdir(bool $recursive = false, int $permissions = 0777): static
    {
        if (!mkdir($this->toNative(), $permissions, $recursive) && !is_dir($this->toNative())) {
            throw new \RuntimeException("Cannot create directory '{$this->path}'");
        }
        return $this;
    }

    // ─── File operations ─────────────────────────────────────

    /**
     * Create the file if it doesn't exist, or update mtime.
     */
    public function touch(): static
    {
        // Ensure parent directory exists
        $parent = dirname($this->toNative());
        if (!is_dir($parent)) {
            mkdir($parent, 0777, true);
        }
        touch($this->toNative());
        return $this;
    }

    /**
     * Delete a file.
     */
    public function unlink(): void
    {
        if (!unlink($this->toNative())) {
            throw new \RuntimeException("Cannot unlink '{$this->path}'");
        }
    }

    /**
     * Remove an empty directory.
     */
    public function rmdir(): void
    {
        if (!rmdir($this->toNative())) {
            throw new \RuntimeException("Cannot rmdir '{$this->path}'");
        }
    }

    /**
     * Rename/move the file to target, returning the new path.
     */
    public function rename(string|self $target): static
    {
        $targetPath = (string)$target;
        $targetNative = str_replace('/', DIRECTORY_SEPARATOR, $targetPath);
        if (!rename($this->toNative(), $targetNative)) {
            throw new \RuntimeException("Cannot rename '{$this->path}' to '{$targetPath}'");
        }
        return new static($targetPath);
    }

    /**
     * Copy file to destination.
     */
    public function copy(string|self $destination): static
    {
        $destPath = (string)$destination;
        $destNative = str_replace('/', DIRECTORY_SEPARATOR, $destPath);
        if (!copy($this->toNative(), $destNative)) {
            throw new \RuntimeException("Cannot copy '{$this->path}' to '{$destPath}'");
        }
        return new static($destPath);
    }

    // ─── Comparison ──────────────────────────────────────────

    public function eq(string|self $other): bool
    {
        return $this->path === (string)(new static((string)$other));
    }

    // ─── Static constructors ─────────────────────────────────

    /**
     * Get current working directory as PyPath.
     */
    public static function cwd(): static
    {
        return new static(getcwd() ?: '.');
    }

    /**
     * Get user home directory as PyPath.
     */
    public static function home(): static
    {
        $home = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? getenv('HOME') ?: getenv('USERPROFILE') ?: '.';
        return new static($home);
    }

    /**
     * Create a temporary file path.
     */
    public static function tempfile(string $prefix = 'py', string $suffix = ''): static
    {
        $tmp = tempnam(sys_get_temp_dir(), $prefix);
        if ($tmp === false) throw new \RuntimeException("Cannot create temp file");
        if ($suffix !== '') {
            $newPath = $tmp . $suffix;
            rename($tmp, $newPath);
            return new static($newPath);
        }
        return new static($tmp);
    }

    /**
     * Get the temp directory as PyPath.
     */
    public static function tempdir(): static
    {
        return new static(sys_get_temp_dir());
    }

    // ─── Internal ────────────────────────────────────────────

    /**
     * Convert to native OS path.
     */
    public function toNative(): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $this->path);
    }

    /**
     * Get the raw path string.
     */
    public function toPhp(): string
    {
        return $this->path;
    }
}
