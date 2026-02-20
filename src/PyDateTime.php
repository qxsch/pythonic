<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python datetime for PHP.
 *
 * Wraps PHP's DateTimeImmutable with a Pythonic interface:
 *   - PyDateTime::now()
 *   - PyDateTime::fromtimestamp($ts)
 *   - PyDateTime::strptime($dateString, $format)
 *   - $dt->strftime($format)
 *   - $dt->isoformat()
 *   - $dt->timestamp()
 *   - $dt->date()        → PyString 'YYYY-MM-DD'
 *   - $dt->time()        → PyString 'HH:MM:SS'
 *   - $dt->add($delta)   → PyDateTime
 *   - $dt->sub($delta)   → PyTimeDelta|PyDateTime
 *   - $dt->diff($other)  → PyTimeDelta
 *   - Attribute access: ->year, ->month, ->day, ->hour, ->minute, ->second
 *
 * All string return values are PyString instances.
 */
class PyDateTime implements \JsonSerializable, \Stringable
{
    use PyObject;

    private \DateTimeImmutable $dt;

    /**
     * Create a PyDateTime from a DateTimeImmutable, string, or timestamp.
     *
     * @param \DateTimeImmutable|string|null $datetime
     *   - null → current datetime
     *   - string → parsed via DateTimeImmutable constructor
     *   - DateTimeImmutable → used directly
     * @param \DateTimeZone|string|null $timezone Optional timezone.
     */
    public function __construct(
        \DateTimeImmutable|string|null $datetime = null,
        \DateTimeZone|string|null $timezone = null,
    ) {
        $tz = null;
        if ($timezone !== null) {
            $tz = ($timezone instanceof \DateTimeZone)
                ? $timezone
                : new \DateTimeZone($timezone);
        }

        if ($datetime instanceof \DateTimeImmutable) {
            $this->dt = $tz ? $datetime->setTimezone($tz) : $datetime;
        } elseif (is_string($datetime)) {
            $this->dt = new \DateTimeImmutable($datetime, $tz);
        } else {
            $this->dt = new \DateTimeImmutable('now', $tz);
        }
    }

    // ─── Factory methods ─────────────────────────────────────

    /**
     * Return current date and time.
     *
     * Python equivalent: datetime.datetime.now(tz)
     */
    public static function now(\DateTimeZone|string|null $timezone = null): self
    {
        return new self(null, $timezone);
    }

    /**
     * Create from a Unix timestamp.
     *
     * Python equivalent: datetime.datetime.fromtimestamp(ts, tz)
     */
    public static function fromtimestamp(int|float $ts, \DateTimeZone|string|null $timezone = null): self
    {
        $dt = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', $ts));
        if ($dt === false) {
            $dt = (new \DateTimeImmutable())->setTimestamp((int)$ts);
        }
        return new self($dt, $timezone);
    }

    /**
     * Create from ISO format string.
     *
     * Python equivalent: datetime.datetime.fromisoformat(date_string)
     */
    public static function fromisoformat(string $dateString): self
    {
        return new self($dateString);
    }

    /**
     * Parse a date string with a Python-style strptime format.
     *
     * Python equivalent: datetime.datetime.strptime(date_string, format)
     *
     * Converts common Python format codes (%Y, %m, %d, %H, %M, %S, %f, %z, %Z)
     * to PHP's date format codes before parsing.
     *
     * @param string $dateString The date string to parse.
     * @param string $format     Python-compatible format string (e.g. '%Y-%m-%d %H:%M:%S').
     * @return self
     */
    public static function strptime(string $dateString, string $format): self
    {
        $phpFormat = self::pythonToPhpFormat($format);
        $dt = \DateTimeImmutable::createFromFormat($phpFormat, $dateString);
        if ($dt === false) {
            throw new \ValueError("strptime: time data '{$dateString}' does not match format '{$format}'");
        }
        return new self($dt);
    }

    /**
     * Combine a date string and time string into a datetime.
     *
     * Python equivalent: datetime.datetime.combine(date, time)
     *
     * @param string $date Date string (e.g. '2024-01-15').
     * @param string $time Time string (e.g. '10:30:00').
     * @return self
     */
    public static function combine(string $date, string $time): self
    {
        return new self("{$date} {$time}");
    }

    // ─── Formatting ──────────────────────────────────────────

    /**
     * Format using Python-style format codes.
     *
     * Python equivalent: dt.strftime(format)
     *
     * @param string $format Python-compatible format string.
     * @return PyString
     */
    public function strftime(string $format): PyString
    {
        $phpFormat = self::pythonToPhpFormat($format);
        return new PyString($this->dt->format($phpFormat));
    }

    /**
     * Return ISO 8601 formatted string.
     *
     * Python equivalent: dt.isoformat(sep)
     *
     * @param string $sep Separator between date and time (default 'T').
     * @return PyString
     */
    public function isoformat(string $sep = 'T'): PyString
    {
        $d = $this->dt->format('Y-m-d');
        $t = $this->dt->format('H:i:s');
        $us = (int)$this->dt->format('u');
        $result = $d . $sep . $t;
        if ($us > 0) {
            $result .= '.' . str_pad((string)$us, 6, '0', STR_PAD_LEFT);
        }
        return new PyString($result);
    }

    /**
     * Return the date part as PyString 'YYYY-MM-DD'.
     */
    public function date(): PyString
    {
        return new PyString($this->dt->format('Y-m-d'));
    }

    /**
     * Return the time part as PyString 'HH:MM:SS'.
     */
    public function time(): PyString
    {
        return new PyString($this->dt->format('H:i:s'));
    }

    // ─── Components ──────────────────────────────────────────

    /** @var int Year */
    public function getYear(): int { return (int)$this->dt->format('Y'); }
    /** @var int Month (1-12) */
    public function getMonth(): int { return (int)$this->dt->format('n'); }
    /** @var int Day of month (1-31) */
    public function getDay(): int { return (int)$this->dt->format('j'); }
    /** @var int Hour (0-23) */
    public function getHour(): int { return (int)$this->dt->format('G'); }
    /** @var int Minute (0-59) */
    public function getMinute(): int { return (int)$this->dt->format('i'); }
    /** @var int Second (0-59) */
    public function getSecond(): int { return (int)$this->dt->format('s'); }
    /** @var int Microsecond (0-999999) */
    public function getMicrosecond(): int { return (int)$this->dt->format('u'); }

    /** Day of week (0=Monday ... 6=Sunday), like Python's weekday(). */
    public function weekday(): int
    {
        // PHP: 1=Mon..7=Sun; Python: 0=Mon..6=Sun
        return ((int)$this->dt->format('N')) - 1;
    }

    /** ISO day of week (1=Mon..7=Sun). */
    public function isoweekday(): int
    {
        return (int)$this->dt->format('N');
    }

    /** ISO calendar: PyTuple(year, week, weekday). */
    public function isocalendar(): PyTuple
    {
        return new PyTuple([
            (int)$this->dt->format('o'),
            (int)$this->dt->format('W'),
            (int)$this->dt->format('N'),
        ]);
    }

    /** Day of year (1-366). */
    public function timetuple_tm_yday(): int
    {
        return (int)$this->dt->format('z') + 1;
    }

    /** Unix timestamp. */
    public function timestamp(): float
    {
        return (float)$this->dt->format('U.u');
    }

    // ─── Attribute access (Python-compatible) ────────────────

    public function __get(string $name): mixed
    {
        return match ($name) {
            'year'        => $this->getYear(),
            'month'       => $this->getMonth(),
            'day'         => $this->getDay(),
            'hour'        => $this->getHour(),
            'minute'      => $this->getMinute(),
            'second'      => $this->getSecond(),
            'microsecond' => $this->getMicrosecond(),
            default       => throw new \RuntimeException("AttributeError: 'datetime' has no attribute '{$name}'"),
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['year', 'month', 'day', 'hour', 'minute', 'second', 'microsecond'], true);
    }

    // ─── Arithmetic ──────────────────────────────────────────

    /**
     * Add a PyTimeDelta → new PyDateTime.
     *
     * Python equivalent: dt + timedelta(...)
     */
    public function add(PyTimeDelta $delta): self
    {
        $seconds = (int)$delta->total_seconds();
        $microseconds = (int)(($delta->total_seconds() - $seconds) * 1_000_000);
        $interval = new \DateInterval('PT' . abs($seconds) . 'S');
        if ($seconds < 0) {
            $interval->invert = 1;
        }
        $newDt = $this->dt->add($interval);
        // Handle microseconds
        if ($microseconds !== 0) {
            $currentUs = (int)$newDt->format('u');
            $newUs = $currentUs + $microseconds;
            // Normalize
            $extraSec = intdiv($newUs, 1_000_000);
            $newUs = $newUs % 1_000_000;
            if ($newUs < 0) {
                $extraSec -= 1;
                $newUs += 1_000_000;
            }
            if ($extraSec !== 0) {
                $newDt = $newDt->modify("{$extraSec} seconds");
            }
            $newDt = $newDt->modify($newDt->format('Y-m-d H:i:s') . '.' . str_pad((string)$newUs, 6, '0', STR_PAD_LEFT));
        }
        return new self($newDt);
    }

    /**
     * Subtract: if PyTimeDelta → new PyDateTime; if PyDateTime → PyTimeDelta difference.
     *
     * Python equivalents: dt - timedelta(...) or dt1 - dt2
     */
    public function sub(PyTimeDelta|self $other): self|PyTimeDelta
    {
        if ($other instanceof PyTimeDelta) {
            return $this->add($other->neg());
        }
        // $other is PyDateTime → return difference as PyTimeDelta
        return $this->diff($other);
    }

    /**
     * Compute difference between two datetimes → PyTimeDelta.
     *
     * Python equivalent: dt1 - dt2
     */
    public function diff(self $other): PyTimeDelta
    {
        $diffSeconds = $this->timestamp() - $other->timestamp();
        $totalUs = (int)round($diffSeconds * 1_000_000);
        $us = $totalUs % 1_000_000;
        $sec = intdiv($totalUs, 1_000_000);
        return new PyTimeDelta(seconds: $sec, microseconds: $us);
    }

    // ─── Comparison ──────────────────────────────────────────

    public function __eq(self $other): bool
    {
        return $this->dt == $other->dt;
    }

    public function __lt(self $other): bool
    {
        return $this->dt < $other->dt;
    }

    public function __le(self $other): bool
    {
        return $this->dt <= $other->dt;
    }

    public function __gt(self $other): bool
    {
        return $this->dt > $other->dt;
    }

    public function __ge(self $other): bool
    {
        return $this->dt >= $other->dt;
    }

    // ─── Replace ─────────────────────────────────────────────

    /**
     * Return a new datetime with some fields replaced.
     *
     * Python equivalent: dt.replace(year=..., month=..., ...)
     */
    public function replace(
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
        ?int $hour = null,
        ?int $minute = null,
        ?int $second = null,
        ?int $microsecond = null,
        \DateTimeZone|string|null $tzinfo = null,
    ): self {
        $y = $year ?? $this->getYear();
        $mo = $month ?? $this->getMonth();
        $d = $day ?? $this->getDay();
        $h = $hour ?? $this->getHour();
        $mi = $minute ?? $this->getMinute();
        $s = $second ?? $this->getSecond();
        $us = $microsecond ?? $this->getMicrosecond();

        $tz = $tzinfo ?? $this->dt->getTimezone();
        if (is_string($tz)) {
            $tz = new \DateTimeZone($tz);
        }

        $dateStr = sprintf('%04d-%02d-%02d %02d:%02d:%02d.%06d', $y, $mo, $d, $h, $mi, $s, $us);
        return new self($dateStr, $tz);
    }

    // ─── Timezone ────────────────────────────────────────────

    /**
     * Get the timezone name.
     */
    public function tzname(): PyString
    {
        return new PyString($this->dt->getTimezone()->getName());
    }

    /**
     * UTC offset in seconds.
     */
    public function utcoffset(): int
    {
        return $this->dt->getOffset();
    }

    // ─── Underlying PHP object ───────────────────────────────

    /**
     * Get the underlying DateTimeImmutable.
     */
    public function toDateTimeImmutable(): \DateTimeImmutable
    {
        return $this->dt;
    }

    // ─── PyObject / interfaces ───────────────────────────────

    public function __repr(): string
    {
        $us = $this->getMicrosecond();
        $base = sprintf(
            'datetime.datetime(%d, %d, %d, %d, %d, %d',
            $this->getYear(),
            $this->getMonth(),
            $this->getDay(),
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
        );
        if ($us > 0) {
            $base .= ", {$us}";
        }
        return $base . ')';
    }

    public function __bool(): bool
    {
        return true; // datetime is always truthy
    }

    public function __len(): int
    {
        return 0; // datetime has no length concept
    }

    public function __toString(): string
    {
        return (string)$this->isoformat();
    }

    public function jsonSerialize(): mixed
    {
        return (string)$this->isoformat();
    }

    public function toPhp(): \DateTimeImmutable
    {
        return $this->dt;
    }

    // ─── Format conversion helper ────────────────────────────

    /**
     * Convert Python strftime/strptime format codes to PHP date format codes.
     */
    private static function pythonToPhpFormat(string $pythonFormat): string
    {
        $map = [
            '%Y' => 'Y',    // 4-digit year
            '%y' => 'y',    // 2-digit year
            '%m' => 'm',    // Zero-padded month
            '%d' => 'd',    // Zero-padded day
            '%H' => 'H',    // 24-hour zero-padded
            '%I' => 'h',    // 12-hour zero-padded
            '%M' => 'i',    // Minute zero-padded
            '%S' => 's',    // Second zero-padded
            '%f' => 'u',    // Microsecond
            '%p' => 'A',    // AM/PM
            '%j' => 'z',    // Day of year (note: PHP is 0-based, Python is 1-based)
            '%A' => 'l',    // Full weekday name
            '%a' => 'D',    // Abbreviated weekday name
            '%B' => 'F',    // Full month name
            '%b' => 'M',    // Abbreviated month name
            '%w' => 'w',    // Day of week (0=Sunday)
            '%Z' => 'T',    // Timezone abbreviation
            '%z' => 'O',    // UTC offset
            '%%' => '%',    // Literal %
        ];

        $result = $pythonFormat;
        foreach ($map as $py => $php) {
            $result = str_replace($py, $php, $result);
        }
        return $result;
    }
}
