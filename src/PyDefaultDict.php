<?php

declare(strict_types=1);

namespace QXS\pythonic;

/**
 * Python collections.defaultdict for PHP.
 *
 * A dict subclass that calls a factory function to supply missing values.
 *
 * Usage:
 *   $dd = new PyDefaultDict(fn() => 0);
 *   $dd['missing']        // 0 (auto-created)
 *   $dd['count'] += 1;    // works without initialization
 *
 *   $dd = new PyDefaultDict(fn() => new PyList());
 *   $dd['fruits']->append('apple');
 *
 *   $dd = PyDefaultDict::ofList();   // shortcut: default = []
 *   $dd = PyDefaultDict::ofInt();    // shortcut: default = 0
 *   $dd = PyDefaultDict::ofString(); // shortcut: default = ''
 */
class PyDefaultDict extends PyDict
{
    /** @var callable Factory for generating default values */
    private $defaultFactory;

    /**
     * @param callable $defaultFactory Function returning the default value for missing keys
     * @param array    $data           Initial data
     */
    public function __construct(callable $defaultFactory, array $data = [])
    {
        $this->defaultFactory = $defaultFactory;
        parent::__construct($data);
    }

    // ─── Convenient factories ────────────────────────────────

    /** defaultdict(int) — default 0 */
    public static function ofInt(array $data = []): static
    {
        return new static(fn() => 0, $data);
    }

    /** defaultdict(list) — default [] as PyList */
    public static function ofList(array $data = []): static
    {
        return new static(fn() => new PyList(), $data);
    }

    /** defaultdict(str) — default '' */
    public static function ofString(array $data = []): static
    {
        return new static(fn() => '', $data);
    }

    /** defaultdict(set) — default set() as PySet */
    public static function ofSet(array $data = []): static
    {
        return new static(fn() => new PySet(), $data);
    }

    /** defaultdict(dict) — default {} as PyDict */
    public static function ofDict(array $data = []): static
    {
        return new static(fn() => new PyDict(), $data);
    }

    // ─── Override access to implement default_factory ────────

    /**
     * On missing key, call default_factory and store the result.
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!array_key_exists($offset, $this->data)) {
            $this->data[$offset] = ($this->defaultFactory)();
        }
        return $this->data[$offset];
    }

    /**
     * Magic __get also uses default_factory.
     */
    public function __get(string $name): mixed
    {
        if (!array_key_exists($name, $this->data)) {
            $this->data[$name] = ($this->defaultFactory)();
        }
        return $this->data[$name];
    }

    /**
     * Override get() — does NOT trigger default_factory (like Python).
     * Use [] access to trigger default_factory.
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    // ─── Repr ────────────────────────────────────────────────

    public function __repr(): string
    {
        return 'defaultdict(' . parent::__repr() . ')';
    }

    /**
     * Get the default factory.
     */
    public function getDefaultFactory(): callable
    {
        return $this->defaultFactory;
    }

    /**
     * Set the default factory.
     */
    public function setDefaultFactory(callable $factory): static
    {
        $this->defaultFactory = $factory;
        return $this;
    }
}
