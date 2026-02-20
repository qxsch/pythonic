<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python timedelta for PHP.
 *
 * Represents a duration — the difference between two dates or times.
 *
 * Usage:
 *   $delta = new PyTimeDelta(days: 5, hours: 3);
 *   echo $delta->total_seconds(); // 442800.0
 *
 *   $dt = PyDateTime::now();
 *   $future = $dt->add($delta);
 */
class PyTimeDelta implements \JsonSerializable, \Stringable
{
    use PyObject;

    /** @var float Total seconds stored internally. */
    private float $totalSeconds;

    /** Decomposed components (for display). */
    private int $days;
    private int $seconds;
    private int $microseconds;

    /**
     * @param int $days
     * @param int $seconds
     * @param int $microseconds
     * @param int $minutes       (converted to seconds)
     * @param int $hours         (converted to seconds)
     * @param int $weeks         (converted to days)
     */
    public function __construct(
        int $days = 0,
        int $seconds = 0,
        int $microseconds = 0,
        int $minutes = 0,
        int $hours = 0,
        int $weeks = 0,
    ) {
        // Normalize everything to total microseconds, then decompose
        $totalUs = $microseconds
            + ($seconds * 1_000_000)
            + ($minutes * 60 * 1_000_000)
            + ($hours * 3600 * 1_000_000)
            + ($days * 86400 * 1_000_000)
            + ($weeks * 7 * 86400 * 1_000_000);

        $this->microseconds = (int)($totalUs % 1_000_000);
        $totalSec = intdiv((int)$totalUs, 1_000_000);
        $this->days = intdiv($totalSec, 86400);
        $this->seconds = $totalSec % 86400;

        // Handle negative normalization (Python-like: days can be negative, seconds 0..86399)
        if ($this->seconds < 0) {
            $this->days -= 1;
            $this->seconds += 86400;
        }
        if ($this->microseconds < 0) {
            $this->seconds -= 1;
            $this->microseconds += 1_000_000;
            if ($this->seconds < 0) {
                $this->days -= 1;
                $this->seconds += 86400;
            }
        }

        $this->totalSeconds = $this->days * 86400.0 + $this->seconds + $this->microseconds / 1_000_000.0;
    }

    /** Total duration in seconds (float). */
    public function total_seconds(): float
    {
        return $this->totalSeconds;
    }

    /** Days component. */
    public function getDays(): int
    {
        return $this->days;
    }

    /** Seconds component (0 ≤ s < 86400). */
    public function getSeconds(): int
    {
        return $this->seconds;
    }

    /** Microseconds component (0 ≤ µs < 1000000). */
    public function getMicroseconds(): int
    {
        return $this->microseconds;
    }

    /**
     * Add two timedeltas → a new PyTimeDelta.
     */
    public function add(PyTimeDelta $other): self
    {
        return new self(
            days: $this->days + $other->days,
            seconds: $this->seconds + $other->seconds,
            microseconds: $this->microseconds + $other->microseconds,
        );
    }

    /**
     * Subtract another timedelta → a new PyTimeDelta.
     */
    public function sub(PyTimeDelta $other): self
    {
        return new self(
            days: $this->days - $other->days,
            seconds: $this->seconds - $other->seconds,
            microseconds: $this->microseconds - $other->microseconds,
        );
    }

    /**
     * Multiply by an integer.
     */
    public function mul(int $factor): self
    {
        return new self(
            days: $this->days * $factor,
            seconds: $this->seconds * $factor,
            microseconds: $this->microseconds * $factor,
        );
    }

    /**
     * Negate.
     */
    public function neg(): self
    {
        return $this->mul(-1);
    }

    /**
     * Absolute value.
     */
    public function abs(): self
    {
        if ($this->days < 0) {
            return $this->neg();
        }
        return clone $this;
    }

    // ─── Comparison ─────────────────────────────────────────

    /** Equal. */
    public function __eq(PyTimeDelta $other): bool
    {
        return $this->totalSeconds === $other->totalSeconds;
    }

    /** Less than. */
    public function __lt(PyTimeDelta $other): bool
    {
        return $this->totalSeconds < $other->totalSeconds;
    }

    /** Less than or equal. */
    public function __le(PyTimeDelta $other): bool
    {
        return $this->totalSeconds <= $other->totalSeconds;
    }

    /** Greater than. */
    public function __gt(PyTimeDelta $other): bool
    {
        return $this->totalSeconds > $other->totalSeconds;
    }

    /** Greater than or equal. */
    public function __ge(PyTimeDelta $other): bool
    {
        return $this->totalSeconds >= $other->totalSeconds;
    }

    // ─── Python-style attribute access ──────────────────────

    /**
     * Magic getter for Python-style attribute access:
     *   $delta->days, $delta->seconds, $delta->microseconds, $delta->total_seconds
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'days'          => $this->days,
            'seconds'       => $this->seconds,
            'microseconds'  => $this->microseconds,
            'total_seconds' => $this->total_seconds(),
            default => throw new AttributeError('PyTimeDelta', $name),
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, ['days', 'seconds', 'microseconds', 'total_seconds'], true);
    }

    // ─── PyObject / interfaces ───────────────────────────────

    public function __repr(): string
    {
        $parts = [];
        if ($this->days !== 0) {
            $parts[] = "days={$this->days}";
        }
        if ($this->seconds !== 0) {
            $parts[] = "seconds={$this->seconds}";
        }
        if ($this->microseconds !== 0) {
            $parts[] = "microseconds={$this->microseconds}";
        }
        if (empty($parts)) {
            $parts[] = "0";
        }
        return 'timedelta(' . implode(', ', $parts) . ')';
    }

    public function __bool(): bool
    {
        return $this->totalSeconds !== 0.0;
    }

    public function __len(): int
    {
        return 0; // timedelta has no length concept
    }

    public function __toString(): string
    {
        // Python format: D day(s), H:MM:SS or H:MM:SS
        $h = intdiv($this->seconds, 3600);
        $m = intdiv($this->seconds % 3600, 60);
        $s = $this->seconds % 60;
        $time = sprintf('%d:%02d:%02d', $h, $m, $s);
        if ($this->days !== 0) {
            $dayWord = abs($this->days) === 1 ? 'day' : 'days';
            return "{$this->days} {$dayWord}, {$time}";
        }
        return $time;
    }

    public function jsonSerialize(): mixed
    {
        return $this->total_seconds();
    }

    public function toPhp(): float
    {
        return $this->totalSeconds;
    }
}
