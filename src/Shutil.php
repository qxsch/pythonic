<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python shutil module for PHP.
 *
 * High-level file/directory operations that complement PyPath.
 * While PyPath mirrors pathlib (single-file ops), Shutil provides
 * the bulk / recursive operations that pathlib alone doesn't cover.
 *
 * Functions:
 *   - rmtree($path)                        — recursively remove directory tree
 *   - copytree($src, $dst, dirs_exist_ok)  — recursively copy directory tree
 *   - move($src, $dst)                     — move file or directory
 *   - copy($src, $dst)                     — copy file (preserving permissions)
 *   - copy2($src, $dst)                    — copy file (preserving metadata)
 *   - copyfile($src, $dst)                 — copy file content only
 *   - disk_usage($path)                    — return disk space (total, used, free)
 *   - which($name)                         — locate executable in PATH
 *   - make_archive($base, $format, $root)  — create archive (.zip / .tar.gz)
 *   - unpack_archive($file, $extractDir)   — extract archive
 *
 * Usage:
 *   use QXS\pythonic\Shutil;
 *
 *   Shutil::copytree('/src/project', '/backup/project');
 *   Shutil::rmtree('/tmp/build');
 *   Shutil::move('/old/file.txt', '/new/file.txt');
 *
 *   $usage = Shutil::disk_usage('/');
 *   echo $usage['total'];  // bytes
 *
 *   $php = Shutil::which('php');  // '/usr/bin/php' or null
 */
class Shutil
{
    // ─── Copy operations ─────────────────────────────────────

    /**
     * Copy a file's content only (no metadata).
     *
     * Python: shutil.copyfile(src, dst)
     *
     * @param string|PyPath $src Source file path.
     * @param string|PyPath $dst Destination file path.
     * @return PyPath The destination path.
     */
    public static function copyfile(string|PyPath $src, string|PyPath $dst): PyPath
    {
        $srcPath = self::toNative($src);
        $dstPath = self::toNative($dst);

        if (!is_file($srcPath)) {
            throw new FileNotFoundError((string)$src);
        }
        if (is_dir($dstPath)) {
            throw new \QXS\pythonic\ValueError("Destination '{$dst}' is a directory, use copy() instead");
        }

        if (!copy($srcPath, $dstPath)) {
            throw new \RuntimeException("Cannot copy '{$src}' to '{$dst}'");
        }

        return $dst instanceof PyPath ? $dst : new PyPath((string)$dst);
    }

    /**
     * Copy file and preserve permissions.
     *
     * Python: shutil.copy(src, dst)
     *
     * If $dst is a directory, the file is copied INTO that directory
     * with the same basename.
     *
     * @param string|PyPath $src Source file path.
     * @param string|PyPath $dst Destination file or directory path.
     * @return PyPath The destination file path.
     */
    public static function copy(string|PyPath $src, string|PyPath $dst): PyPath
    {
        $srcPath = self::toNative($src);
        $dstPath = self::toNative($dst);

        if (!is_file($srcPath)) {
            throw new FileNotFoundError((string)$src);
        }

        // If dst is a directory, copy into it with the same name
        if (is_dir($dstPath)) {
            $dstPath = rtrim($dstPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($srcPath);
            $dst     = new PyPath(rtrim((string)$dst, '/') . '/' . basename((string)$src));
        }

        if (!copy($srcPath, $dstPath)) {
            throw new \RuntimeException("Cannot copy '{$src}' to '{$dst}'");
        }

        // Preserve permissions
        $perms = fileperms($srcPath);
        if ($perms !== false) {
            chmod($dstPath, $perms);
        }

        return $dst instanceof PyPath ? $dst : new PyPath((string)$dst);
    }

    /**
     * Copy file and preserve metadata (permissions + timestamps).
     *
     * Python: shutil.copy2(src, dst)
     *
     * @param string|PyPath $src Source file path.
     * @param string|PyPath $dst Destination file or directory path.
     * @return PyPath The destination file path.
     */
    public static function copy2(string|PyPath $src, string|PyPath $dst): PyPath
    {
        $result = self::copy($src, $dst);

        // Also preserve access/modification times
        $srcPath = self::toNative($src);
        $dstPath = self::toNative($result);

        $atime = fileatime($srcPath) ?: time();
        $mtime = filemtime($srcPath) ?: time();
        touch($dstPath, $mtime, $atime);

        return $result;
    }

    // ─── Directory operations ────────────────────────────────

    /**
     * Recursively copy an entire directory tree.
     *
     * Python: shutil.copytree(src, dst, dirs_exist_ok=False)
     *
     * @param string|PyPath $src            Source directory.
     * @param string|PyPath $dst            Destination directory.
     * @param bool          $dirs_exist_ok  If false, raises error when $dst exists.
     * @param callable|null $ignore         Callable(dir, entries) → array of names to ignore.
     * @return PyPath The destination path.
     */
    public static function copytree(
        string|PyPath $src,
        string|PyPath $dst,
        bool $dirs_exist_ok = false,
        ?callable $ignore = null,
    ): PyPath {
        $srcPath = self::toNative($src);
        $dstPath = self::toNative($dst);

        if (!is_dir($srcPath)) {
            throw new FileNotFoundError((string)$src);
        }

        if (is_dir($dstPath) && !$dirs_exist_ok) {
            throw new \RuntimeException("Destination directory '{$dst}' already exists");
        }

        if (!is_dir($dstPath)) {
            mkdir($dstPath, 0777, true);
        }

        $entries = scandir($srcPath);
        if ($entries === false) {
            throw new \RuntimeException("Cannot read directory '{$src}'");
        }

        // Determine which entries to ignore
        $ignoredNames = [];
        if ($ignore !== null) {
            $names = array_values(array_filter($entries, fn($e) => $e !== '.' && $e !== '..'));
            $ignoredNames = $ignore((string)$src, $names);
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (in_array($entry, $ignoredNames, true)) continue;

            $srcEntry = $srcPath . DIRECTORY_SEPARATOR . $entry;
            $dstEntry = $dstPath . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($srcEntry)) {
                self::copytree(
                    $srcEntry,
                    $dstEntry,
                    $dirs_exist_ok,
                    $ignore,
                );
            } else {
                copy($srcEntry, $dstEntry);
                // Preserve permissions
                $perms = fileperms($srcEntry);
                if ($perms !== false) {
                    chmod($dstEntry, $perms);
                }
            }
        }

        return $dst instanceof PyPath ? $dst : new PyPath((string)$dst);
    }

    /**
     * Recursively remove an entire directory tree.
     *
     * Python: shutil.rmtree(path)
     *
     * @param string|PyPath $path           Directory to remove.
     * @param bool          $ignore_errors  If true, errors are silently ignored.
     */
    public static function rmtree(string|PyPath $path, bool $ignore_errors = false): void
    {
        $native = self::toNative($path);

        if (!is_dir($native)) {
            if ($ignore_errors) return;
            throw new FileNotFoundError((string)$path);
        }

        self::rmtreeRecursive($native, $ignore_errors);
    }

    /**
     * Move a file or directory to another location.
     *
     * Python: shutil.move(src, dst)
     *
     * If $dst is an existing directory, $src is moved INSIDE it.
     *
     * @param string|PyPath $src Source file or directory.
     * @param string|PyPath $dst Destination path.
     * @return PyPath The final destination path.
     */
    public static function move(string|PyPath $src, string|PyPath $dst): PyPath
    {
        $srcPath = self::toNative($src);
        $dstPath = self::toNative($dst);

        if (!file_exists($srcPath) && !is_dir($srcPath)) {
            throw new FileNotFoundError((string)$src);
        }

        // If dst is an existing directory, move inside it
        if (is_dir($dstPath)) {
            $dstPath = rtrim($dstPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($srcPath);
            $dst     = new PyPath(rtrim((string)$dst, '/') . '/' . basename((string)$src));
        }

        if (!rename($srcPath, $dstPath)) {
            // Cross-device move: copy + remove
            if (is_dir($srcPath)) {
                self::copytree($src, $dst, dirs_exist_ok: true);
                self::rmtree($src);
            } else {
                copy($srcPath, $dstPath);
                unlink($srcPath);
            }
        }

        return $dst instanceof PyPath ? $dst : new PyPath((string)$dst);
    }

    // ─── Disk & system info ──────────────────────────────────

    /**
     * Return disk usage statistics for the given path.
     *
     * Python: shutil.disk_usage(path) → (total, used, free)
     *
     * @param string|PyPath $path A path on the target filesystem.
     * @return array{total: float, used: float, free: float} Bytes.
     */
    public static function disk_usage(string|PyPath $path): array
    {
        $native = self::toNative($path);

        $total = disk_total_space($native);
        $free  = disk_free_space($native);

        if ($total === false || $free === false) {
            throw new \RuntimeException("Cannot determine disk usage for '{$path}'");
        }

        return [
            'total' => $total,
            'used'  => $total - $free,
            'free'  => $free,
        ];
    }

    /**
     * Locate an executable in the system PATH.
     *
     * Python: shutil.which(name)
     *
     * @param string $name Command name (e.g. 'php', 'git').
     * @return PyPath|null Full path to the executable, or null if not found.
     */
    public static function which(string $name): ?PyPath
    {
        // On Windows, check common extensions
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $extensions = $isWindows
            ? explode(';', strtolower(getenv('PATHEXT') ?: '.COM;.EXE;.BAT;.CMD'))
            : [''];

        $pathDirs = explode(PATH_SEPARATOR, getenv('PATH') ?: '');

        foreach ($pathDirs as $dir) {
            $dir = rtrim($dir, DIRECTORY_SEPARATOR);
            if ($dir === '') continue;

            foreach ($extensions as $ext) {
                $candidate = $dir . DIRECTORY_SEPARATOR . $name . $ext;
                if (is_file($candidate) && is_executable($candidate)) {
                    return new PyPath(str_replace('\\', '/', $candidate));
                }
                // On Windows, also check without extension if name already has one
                if ($isWindows && $ext === '' && is_file($dir . DIRECTORY_SEPARATOR . $name)) {
                    return new PyPath(str_replace('\\', '/', $dir . DIRECTORY_SEPARATOR . $name));
                }
            }
        }

        return null;
    }

    // ─── Archive operations ──────────────────────────────────

    /**
     * Create an archive file (zip or tar.gz).
     *
     * Python: shutil.make_archive(base_name, format, root_dir)
     *
     * @param string        $baseName   Base name of the archive (without extension).
     * @param string        $format     'zip' or 'tar' (creates .tar.gz).
     * @param string|PyPath $rootDir    Directory to archive.
     * @return PyPath Path to the created archive.
     */
    public static function make_archive(string $baseName, string $format, string|PyPath $rootDir): PyPath
    {
        $rootNative = self::toNative($rootDir);

        if (!is_dir($rootNative)) {
            throw new FileNotFoundError((string)$rootDir);
        }

        $archivePath = match ($format) {
            'zip'  => self::makeZipArchive($baseName, $rootNative),
            'tar'  => self::makeTarArchive($baseName, $rootNative),
            default => throw new \ValueError("Unsupported archive format: '{$format}'. Use 'zip' or 'tar'."),
        };

        return new PyPath($archivePath);
    }

    /**
     * Extract an archive to a directory.
     *
     * Python: shutil.unpack_archive(filename, extract_dir)
     *
     * @param string|PyPath $filename    Path to the archive.
     * @param string|PyPath $extractDir  Directory to extract into.
     */
    public static function unpack_archive(string|PyPath $filename, string|PyPath $extractDir): void
    {
        $archivePath = self::toNative($filename);
        $extractPath = self::toNative($extractDir);

        if (!is_file($archivePath)) {
            throw new FileNotFoundError((string)$filename);
        }

        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0777, true);
        }

        $ext = strtolower(pathinfo($archivePath, PATHINFO_EXTENSION));

        if ($ext === 'zip') {
            self::extractZip($archivePath, $extractPath);
        } elseif ($ext === 'gz' || $ext === 'tgz') {
            self::extractTarGz($archivePath, $extractPath);
        } else {
            throw new \ValueError("Cannot determine archive format for '{$filename}'");
        }
    }

    // ─── Convenience: ignore_patterns ────────────────────────

    /**
     * Factory function for copytree ignore argument.
     *
     * Python: shutil.ignore_patterns(*patterns)
     *
     * Returns a callable suitable for copytree(ignore: ...) that skips
     * entries matching any of the given glob patterns.
     *
     * @param string ...$patterns Glob patterns (e.g. '*.pyc', '__pycache__').
     * @return callable
     */
    public static function ignore_patterns(string ...$patterns): callable
    {
        return function (string $directory, array $entries) use ($patterns): array {
            $ignored = [];
            foreach ($entries as $entry) {
                foreach ($patterns as $pattern) {
                    if (fnmatch($pattern, $entry)) {
                        $ignored[] = $entry;
                        break;
                    }
                }
            }
            return $ignored;
        };
    }

    // ─── Internal helpers ────────────────────────────────────

    /**
     * Convert string|PyPath to native OS path.
     */
    private static function toNative(string|PyPath $path): string
    {
        $str = $path instanceof PyPath ? $path->toPhp() : $path;
        return str_replace('/', DIRECTORY_SEPARATOR, $str);
    }

    /**
     * Recursively delete directory contents and the directory itself.
     */
    private static function rmtreeRecursive(string $dir, bool $ignoreErrors): void
    {
        $entries = scandir($dir);
        if ($entries === false) {
            if (!$ignoreErrors) {
                throw new \RuntimeException("Cannot read directory '{$dir}'");
            }
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full = $dir . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($full)) {
                self::rmtreeRecursive($full, $ignoreErrors);
            } else {
                if (!unlink($full) && !$ignoreErrors) {
                    throw new \RuntimeException("Cannot remove file '{$full}'");
                }
            }
        }

        if (!rmdir($dir) && !$ignoreErrors) {
            throw new \RuntimeException("Cannot remove directory '{$dir}'");
        }
    }

    /**
     * Create a .zip archive.
     */
    private static function makeZipArchive(string $baseName, string $rootDir): string
    {
        $archivePath = $baseName . '.zip';

        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is not available');
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create zip archive '{$archivePath}'");
        }

        self::addDirToZip($zip, $rootDir, '');
        $zip->close();

        return $archivePath;
    }

    /**
     * Recursively add directory contents to zip.
     */
    private static function addDirToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        $entries = scandir($dir);
        if ($entries === false) return;

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $full     = $dir . DIRECTORY_SEPARATOR . $entry;
            $relative = $prefix === '' ? $entry : $prefix . '/' . $entry;

            if (is_dir($full)) {
                $zip->addEmptyDir($relative);
                self::addDirToZip($zip, $full, $relative);
            } else {
                $zip->addFile($full, $relative);
            }
        }
    }

    /**
     * Create a .tar.gz archive using PharData.
     */
    private static function makeTarArchive(string $baseName, string $rootDir): string
    {
        $tarPath = $baseName . '.tar';
        $gzPath  = $tarPath . '.gz';

        if (!class_exists(\PharData::class)) {
            throw new \RuntimeException('Phar extension is not available');
        }

        // Remove existing files to avoid conflicts
        if (file_exists($gzPath)) unlink($gzPath);
        if (file_exists($tarPath)) unlink($tarPath);

        $phar = new \PharData($tarPath);
        $phar->buildFromDirectory($rootDir);
        $phar->compress(\Phar::GZ);

        // Clean up the intermediate .tar
        if (file_exists($tarPath)) unlink($tarPath);

        return $gzPath;
    }

    /**
     * Extract a .zip archive.
     */
    private static function extractZip(string $archivePath, string $extractPath): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is not available');
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException("Cannot open zip archive '{$archivePath}'");
        }
        $zip->extractTo($extractPath);
        $zip->close();
    }

    /**
     * Extract a .tar.gz archive.
     */
    private static function extractTarGz(string $archivePath, string $extractPath): void
    {
        if (!class_exists(\PharData::class)) {
            throw new \RuntimeException('Phar extension is not available');
        }

        $phar = new \PharData($archivePath);
        $phar->extractTo($extractPath, null, true);
    }
}
