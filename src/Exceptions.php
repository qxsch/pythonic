<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python-like exception hierarchy for PHP.
 *
 * Python                       PHP (this library)
 * ──────────────────────       ──────────────────────────
 * BaseException (builtin)  →   \Exception (builtin)
 * Exception (builtin)      →   PyException extends \RuntimeException
 * ValueError               →   ValueError extends PyException
 * KeyError                 →   KeyError extends PyException
 * IndexError               →   IndexError extends PyException
 * TypeError                →   PyTypeError extends PyException
 * AttributeError           →   AttributeError extends PyException
 * StopIteration            →   StopIteration extends PyException
 * FileNotFoundError        →   FileNotFoundError extends PyException
 * ZeroDivisionError        →   ZeroDivisionError extends PyException
 * NotImplementedError      →   NotImplementedError extends PyException
 *
 * Usage:
 *   throw new \QXS\pythonic\ValueError("invalid literal for int() with base 10: 'abc'");
 *   throw new \QXS\pythonic\KeyError('name');
 *   throw new \QXS\pythonic\IndexError('list index out of range');
 */

/**
 * Base for all pythonic exceptions.
 */
class PyException extends \RuntimeException
{
    /** Python-style repr. */
    public function pyRepr(): string
    {
        return static::class . '(' . var_export($this->getMessage(), true) . ')';
    }

    /** Python-style str(exception). */
    public function pyStr(): string
    {
        return $this->getMessage();
    }
}

/**
 * Python ValueError — raised when an operation receives
 * an argument with the right type but inappropriate value.
 */
class ValueError extends PyException {}

/**
 * Python KeyError — raised when a mapping key is not found.
 */
class KeyError extends PyException
{
    public function __construct(string|int $key, int $code = 0, ?\Throwable $previous = null)
    {
        $msg = is_string($key) ? "KeyError: '{$key}'" : "KeyError: {$key}";
        parent::__construct($msg, $code, $previous);
    }
}

/**
 * Python IndexError — raised when a sequence index is out of range.
 */
class IndexError extends PyException
{
    public function __construct(string $message = 'list index out of range', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Python TypeError — raised when an operation is applied
 * to an object of inappropriate type.
 *
 * Named PyTypeError to avoid conflict with PHP's built-in TypeError.
 */
class PyTypeError extends PyException {}

/**
 * Python AttributeError — raised when attribute access fails.
 */
class AttributeError extends PyException
{
    public function __construct(string $class, string $attribute, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("'{$class}' object has no attribute '{$attribute}'", $code, $previous);
    }
}

/**
 * Python StopIteration — raised to signal the end of an iterator.
 */
class StopIteration extends PyException
{
    private mixed $value;

    public function __construct(mixed $value = null, int $code = 0, ?\Throwable $previous = null)
    {
        $this->value = $value;
        parent::__construct('StopIteration', $code, $previous);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}

/**
 * Python FileNotFoundError.
 */
class FileNotFoundError extends PyException
{
    public function __construct(string $path, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("[Errno 2] No such file or directory: '{$path}'", $code, $previous);
    }
}

/**
 * Python ZeroDivisionError.
 */
class ZeroDivisionError extends PyException
{
    public function __construct(string $message = 'division by zero', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Python NotImplementedError.
 */
class NotImplementedError extends PyException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
