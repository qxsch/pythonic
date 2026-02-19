# 🐍 qxsch/pythonic

**Python-like syntax for PHP objects.** Write PHP that *feels* like Python.

```
composer require qxsch/pythonic
```

Requires PHP 8.1+.

---

## The `py()` Magic Function

One function to rule them all — auto-detects the type and wraps it:

```php
$list   = py([1, 2, 3, 4, 5]);           // → PyList
$dict   = py(["name" => "Alice"]);        // → PyDict
$string = py("hello world");              // → PyString
```

Everything is **fluently chainable** and uses Python method names.

---

## PyList — Python Lists

```php
$nums = py([3, 1, 4, 1, 5, 9, 2, 6]);

// Negative indexing
$nums[-1];          // 6
$nums[-2];          // 2

// Slicing
$nums->slice(1, 4);           // [1, 4, 1]
$nums->slice(0, null, 2);     // [3, 4, 5, 2]  (every 2nd)
$nums->slice(null, null, -1); // [6, 2, 9, 5, 1, 4, 1, 3]  (reversed)

// List comprehension
py([1, 2, 3, 4, 5])->comp(
    fn($x) => $x ** 2,       // transform
    fn($x) => $x > 2          // filter
);
// → [9, 16, 25]

// Fluent chaining
py([5, 3, 8, 1, 9])
    ->filter(fn($x) => $x > 3)
    ->map(fn($x) => $x * 10)
    ->sorted()
    ->toPhp();
// → [50, 80, 90]

// Python list methods
$list = py([1, 2, 3]);
$list->append(4);              // [1, 2, 3, 4]
$list->extend([5, 6]);         // [1, 2, 3, 4, 5, 6]
$list->insert(0, 0);           // [0, 1, 2, 3, 4, 5, 6]
$list->pop();                  // returns 6
$list->remove(3);              // removes first 3
$list->index(2);               // 2
$list->contains(4);            // true

// Aggregation
py([1, 2, 3])->sum();          // 6
py([1, 2, 3])->min();          // 1
py([1, 2, 3])->max();          // 3
py([0, 1, 0])->any();          // true
py([1, 1, 1])->all();          // true

// Enumerate & Zip
py(["a", "b", "c"])->enumerate();
// [[0, "a"], [1, "b"], [2, "c"]]

py([1, 2, 3])->zip(["a", "b", "c"]);
// [[1, "a"], [2, "b"], [3, "c"]]

// More
$list->unique();               // deduplicated
$list->flatten();              // flatten nested
$list->chunk(3);               // chunk into sublists
$list->groupby(fn($x) => $x % 2 === 0 ? 'even' : 'odd');
$list->join(", ");             // → PyString "1, 2, 3"
$list->first();                // first element
$list->last();                 // last element
$list->take(3);                // first 3
$list->drop(2);                // skip first 2
$list->takewhile(fn($x) => $x < 5);
$list->dropwhile(fn($x) => $x < 5);
$list->repeat(3);              // [1,2,3,1,2,3,1,2,3]
$list->concat([4, 5]);         // [1,2,3,4,5]
$list->reduce(fn($a, $b) => $a + $b);

// Python repr
echo py([1, "hello", true, null]);
// [1, 'hello', True, None]
```

---

## PyDict — Python Dicts

```php
$user = py(["name" => "Alice", "age" => 30, "city" => "NYC"]);

// Attribute-style access
$user->name;                   // "Alice"
$user->age;                    // 30

// Dict methods
$user->get("email", "N/A");   // "N/A" (no KeyError!)
$user->keys();                 // PyList ['name', 'age', 'city']
$user->values();               // PyList ['Alice', 30, 'NYC']
$user->items();                // PyList [['name','Alice'], ['age',30], ...]
$user->contains("name");       // true ('in' operator)
$user->pop("city");            // "NYC" (removes it)
$user->setdefault("email", "alice@example.com");

// Merge (like {**d1, **d2})
$merged = py(["a" => 1])->merge(["b" => 2], ["c" => 3]);
// {'a': 1, 'b': 2, 'c': 3}

// Dict comprehension
$prices = py(["apple" => 1.5, "banana" => 0.5, "cherry" => 3.0]);
$expensive = $prices->comp(
    fn($k, $v) => [$k, $v * 1.1],     // transform: 10% markup
    fn($k, $v) => $v > 1.0             // filter: only expensive
);
// {'apple': 1.65, 'cherry': 3.3}

// Functional
$user->mapValues(fn($v) => strtoupper((string)$v));
$user->mapKeys(fn($k) => "user_{$k}");
$user->filter(fn($k, $v) => is_string($v));
$user->sortedByKeys();
$user->sortedByValues();

// Static constructor
PyDict::fromkeys(["a", "b", "c"], 0);
// {'a': 0, 'b': 0, 'c': 0}

echo $user;
// {'name': 'Alice', 'age': 30, 'city': 'NYC'}
```

---

## PyString — Python Strings

```php
$s = py("Hello, World!");

// Negative indexing
$s[0];                         // "H"
$s[-1];                        // "!"

// Slicing
$s->slice(0, 5);               // "Hello"
$s->slice(7);                  // "World!"
$s->slice(null, null, -1);     // "!dlroW ,olleH"

// Case methods
$s->upper();                   // "HELLO, WORLD!"
$s->lower();                   // "hello, world!"
$s->title();                   // "Hello, World!"
$s->capitalize();              // "Hello, world!"
$s->swapcase();                // "hELLO, wORLD!"

// Strip / Split / Join
py("  hello  ")->strip();                    // "hello"
py("hello world")->split();                  // PyList ["hello", "world"]
py("a,b,c")->split(",");                     // PyList ["a", "b", "c"]
py(", ")->join(["a", "b", "c"]);             // "a, b, c"

// f-string interpolation!
py("Hello {name}, you are {age}!")->f(["name" => "Alice", "age" => 30]);
// "Hello Alice, you are 30!"

// format()
py("{0} + {1} = {2}")->format(1, 2, 3);
// "1 + 2 = 3"

// Search
$s->find("World");             // 7
$s->contains("Hello");         // true
$s->startswith("Hello");       // true
$s->endswith("!");             // true
$s->replace("World", "PHP");   // "Hello, PHP!"
$s->countOf("l");              // 3

// Character tests
py("123")->isdigit();          // true
py("abc")->isalpha();          // true
py("abc123")->isalnum();       // true

// Padding
py("hi")->center(10);          // "    hi    "
py("hi")->ljust(10, '-');      // "hi--------"
py("42")->zfill(5);            // "00042"

// Regex
py("hello 123 world 456")->re_findall('/\d+/');  // PyList ["123", "456"]
py("hello world")->re_sub('/\bworld\b/', 'PHP'); // "hello PHP"

// Repeat
py("abc")->repeat(3);          // "abcabcabc"

// Partition
py("hello=world")->partition("=");  // PyList ["hello", "=", "world"]

// Immutable (like Python!)
$s[0] = "X";                  // throws LogicException
```

---

## PySet — Python Sets

```php
$a = py_set([1, 2, 3, 4]);
$b = py_set([3, 4, 5, 6]);

// Set operations
$a->union($b);                 // {1, 2, 3, 4, 5, 6}
$a->intersection($b);          // {3, 4}
$a->difference($b);            // {1, 2}
$a->symmetric_difference($b);  // {1, 2, 5, 6}

// Membership
$a->contains(3);               // true
$a->in(99);                    // false

// Comparisons
$a->issubset(py_set([1,2,3,4,5]));  // true
$a->issuperset(py_set([1,2]));      // true
$a->isdisjoint($b);                 // false

// Mutation
$a->add(5);
$a->remove(1);                 // throws if not present
$a->discard(99);               // silent if not present
$a->pop();                     // remove arbitrary element

// Comprehension
py_set([1, 2, 3, 4])->comp(fn($x) => $x ** 2, fn($x) => $x > 2);
// {9, 16}
```

---

## PyRange — Python Range

```php
// Basic ranges
foreach (py_range(5) as $i) { ... }           // 0, 1, 2, 3, 4
foreach (py_range(2, 8) as $i) { ... }        // 2, 3, 4, 5, 6, 7
foreach (py_range(0, 10, 2) as $i) { ... }    // 0, 2, 4, 6, 8
foreach (py_range(10, 0, -1) as $i) { ... }   // 10, 9, 8, ..., 1

// Range comprehension
py_range(10)->comp(fn($x) => $x ** 2, fn($x) => $x % 2 === 0);
// PyList [0, 4, 16, 36, 64]

// Efficient sum (uses arithmetic formula)
py_range(1, 101)->sum();       // 5050

// Membership (O(1))
py_range(0, 1000000)->contains(999999);  // true, instant!

// Convert
py_range(5)->toList();         // PyList [0, 1, 2, 3, 4]
```

---

## PyDataClass — Python Dataclasses

```php
class User extends PyDataClass {
    public function __construct(
        public string $name,
        public int $age,
        public string $email = '',
    ) {
        parent::__construct();
    }
}

$alice = new User('Alice', 30, 'alice@example.com');

// Auto repr
echo $alice;
// User(name='Alice', age=30, email='alice@example.com')

// Structural equality
$alice2 = new User('Alice', 30, 'alice@example.com');
$alice->eq($alice2);           // true

// Convert
$alice->asdict();              // PyDict {'name': 'Alice', 'age': 30, ...}
$alice->astuple();             // PyList ['Alice', 30, 'alice@example.com']

// Copy with overrides
$bob = $alice->copy(name: 'Bob', age: 25);
echo $bob;                     // User(name='Bob', age=25, email='alice@example.com')

// Introspection
$alice->fieldNames();          // PyList ['name', 'age', 'email']
$alice->getFields();           // ['name' => 'Alice', 'age' => 30, ...]

// JSON
json_encode($alice);           // {"name":"Alice","age":30,"email":"alice@example.com"}
```

---

## Python Built-in Functions

All available as global `py_*()` functions or `Py::*()` static methods:

```php
// Itertools-style
py_enumerate(["a", "b", "c"]);            // [[0,"a"], [1,"b"], [2,"c"]]
py_zip([1,2,3], ["a","b","c"]);           // [[1,"a"], [2,"b"], [3,"c"]]
py_sorted([3,1,2]);                        // [1, 2, 3]
py_reversed([1,2,3]);                      // [3, 2, 1]
py_map(fn($x) => $x * 2, [1,2,3]);        // [2, 4, 6]
py_filter(fn($x) => $x > 2, [1,2,3,4]);   // [3, 4]

// Math
py_sum([1, 2, 3]);                         // 6
py_min([3, 1, 2]);                         // 1
py_max([3, 1, 2]);                         // 3
py_abs(-5);                                // 5
py_divmod(17, 5);                          // [3, 2]

// Logic
py_any([0, 0, 1]);                         // true
py_all([1, 1, 1]);                         // true

// Inspection
py_len(py([1, 2, 3]));                     // 3
py_type(py("hello"));                      // "PyString"
py_isinstance(py([]), PyList::class);      // true
```

---

## Context Manager (`with`)

```php
// File handling — auto-closes when done
py_with(fopen('data.txt', 'r'), function($f) {
    while ($line = fgets($f)) {
        echo $line;
    }
});

// Works with any object that has close()/disconnect()/release()
py_with($dbConnection, function($db) {
    $db->query("SELECT * FROM users");
});
```

---

## Itertools — Lazy Generators

All methods return lazy `Generator`s. Materialise with `Itertools::toList()`.

```php
use QXS\pythonic\Itertools;

// ─── Infinite iterators ─────────────────────────────────────
Itertools::count(5, 3);                     // 5, 8, 11, 14, ...
Itertools::cycle([1, 2, 3]);                // 1, 2, 3, 1, 2, 3, ...
Itertools::repeat('x', 4);                  // 'x', 'x', 'x', 'x'

// ─── Finite iterators ───────────────────────────────────────
Itertools::chain([1, 2], [3, 4], [5]);      // 1, 2, 3, 4, 5
Itertools::compress(['a','b','c','d'], [1,0,1,0]);  // 'a', 'c'
Itertools::accumulate([1, 2, 3, 4, 5]);     // 1, 3, 6, 10, 15
Itertools::accumulate([1,2,3,4], fn($a,$b) => $a * $b);  // 1, 2, 6, 24
Itertools::takewhile(fn($x) => $x < 4, [1,2,3,4,5]);    // 1, 2, 3
Itertools::dropwhile(fn($x) => $x < 4, [1,2,3,4,5]);    // 4, 5
Itertools::islice(range(0,9), 2, 8, 2);     // 2, 4, 6
Itertools::pairwise([1, 2, 3, 4]);          // [1,2], [2,3], [3,4]
Itertools::zip_longest('-', [1,2,3], ['a','b']);  // [1,'a'], [2,'b'], [3,'-']
Itertools::starmap(fn($a,$b) => $a+$b, [[1,2],[3,4]]);   // 3, 7
Itertools::groupby(['aaa','aab','bba'], fn($s) => $s[0]); // grouped by first char

// ─── Combinatoric iterators ─────────────────────────────────
Itertools::product([1,2], ['a','b']);        // [1,'a'], [1,'b'], [2,'a'], [2,'b']
Itertools::permutations([1,2,3], 2);        // [1,2], [1,3], [2,1], ...
Itertools::combinations([1,2,3,4], 2);      // [1,2], [1,3], ..., [3,4]
Itertools::combinations_with_replacement([1,2], 3);  // [1,1,1], [1,1,2], ...

// ─── Materialise ────────────────────────────────────────────
$result = Itertools::toList(Itertools::chain([1,2], [3,4]));
// → PyList [1, 2, 3, 4]
$result->toPhp();  // plain array [1, 2, 3, 4]

// Or use the helper / Py constructor
$it = py_itertools();   // returns the Itertools class name (for static calls)
$it = Py::itertools();  // same
```

---

## PyCounter — `collections.Counter`

```php
use QXS\pythonic\PyCounter;

$c = new PyCounter(['a', 'b', 'a', 'c', 'a', 'b']);
$c['a'];                       // 3
$c['b'];                       // 2
$c['missing'];                 // 0 (never throws)

// From a mapping
$c = PyCounter::fromMapping(['x' => 5, 'y' => 2]);

// From a string (counts characters)
$c = new PyCounter("hello");
$c['l'];                       // 2

// Most common
$c->most_common(2);            // PyList [['a', 3], ['b', 2]]

// Elements — expand counts back to items
$c->elements();                // PyList ['a', 'a', 'a', 'b', 'b', 'c']

// Total count
$c->total();                   // 6

// Arithmetic (returns new counters)
$c1 = PyCounter::fromMapping(['a' => 3, 'b' => 1]);
$c2 = PyCounter::fromMapping(['a' => 1, 'b' => 5]);

$c1->add($c2);                // Counter({'a': 4, 'b': 6})
$c1->sub($c2);                // Counter({'a': 2})           — negatives removed
$c1->intersect($c2);          // Counter({'a': 1, 'b': 1})  — min of counts
$c1->union($c2);              // Counter({'a': 3, 'b': 5})  — max of counts

// Operator aliases
$c1->__add($c2);               // same as add()
$c1->__sub($c2);               // same as sub()
$c1->__and($c2);               // same as intersect()
$c1->__or($c2);                // same as union()
$c1->__eq($c2);                // structural equality
$c1->__contains('a');          // true

// Helper
$c = py_counter(['a', 'b', 'a']);
$c = Py::counter(['a', 'b', 'a']);

echo $c;                       // Counter({'a': 2, 'b': 1})
```

---

## PyDefaultDict — `collections.defaultdict`

```php
use QXS\pythonic\PyDefaultDict;

// Manual factory
$dd = new PyDefaultDict(fn() => 0);
$dd['missing'];                // 0 (auto-created via factory)
$dd['count'] += 1;             // works without initialization

// Convenient factories
$dd = PyDefaultDict::ofInt();      // default → 0
$dd = PyDefaultDict::ofList();     // default → PyList
$dd = PyDefaultDict::ofString();   // default → ''
$dd = PyDefaultDict::ofSet();      // default → PySet
$dd = PyDefaultDict::ofDict();     // default → PyDict

// Counting pattern
$dd = PyDefaultDict::ofInt();
foreach (['a','b','a','c','a'] as $ch) {
    $dd[$ch] += 1;
}
// {'a': 3, 'b': 1, 'c': 1}

// Grouping pattern
$dd = PyDefaultDict::ofList();
$colors = $dd['colors'];       // PyList (auto-created)
$colors->append('red');

// get() does NOT trigger the factory (like Python)
$dd->get('unknown', 42);       // 42 — key NOT created

// Magic property access also triggers factory
$dd = PyDefaultDict::ofString();
$dd->name;                     // '' (auto-created)

// Helper
$dd = py_defaultdict(fn() => []);
$dd = Py::defaultdict(fn() => []);

echo $dd;                      // defaultdict({'colors': ['red']})
```

---

## PyDeque — `collections.deque`

```php
use QXS\pythonic\PyDeque;

$dq = new PyDeque([1, 2, 3]);

// O(1) append/pop on both ends
$dq->append(4);               // [1, 2, 3, 4]
$dq->appendleft(0);           // [0, 1, 2, 3, 4]
$dq->pop();                   // 4  → [0, 1, 2, 3]
$dq->popleft();               // 0  → [1, 2, 3]

// Rotate
$dq->rotate(1);               // [3, 1, 2]  — rotate right
$dq->rotate(-1);              // [1, 2, 3]  — rotate left

// Extend
$dq->extend([4, 5]);          // [1, 2, 3, 4, 5]
$dq->extendleft([0, -1]);     // [-1, 0, 1, 2, 3, 4, 5]

// Bounded deque (maxlen)
$dq = new PyDeque([1, 2, 3], maxlen: 3);
$dq->append(4);               // [2, 3, 4]   — 1 dropped from left
$dq->appendleft(0);           // [0, 2, 3]   — 4 dropped from right

// Negative indexing
$dq[-1];                       // last element
$dq[-2];                       // second to last

// Search
$dq->index(2);                 // position of first 2
$dq->countOf(3);               // count occurrences of 3
$dq->remove(2);                // remove first occurrence

// Other
$dq->reverse();                // reverse in place
$dq->clear();                  // remove all
$dq->copy();                   // shallow copy
$dq->peekright();              // last item without removing
$dq->peekleft();               // first item without removing

// Helper
$dq = py_deque([1, 2, 3], maxlen: 5);
$dq = Py::deque([1, 2, 3], maxlen: 5);

echo $dq;                      // deque([1, 2, 3], maxlen=5)
```

---

## PyFrozenSet — Immutable Sets

```php
use QXS\pythonic\PyFrozenSet;

$fs = new PyFrozenSet([1, 2, 3, 4]);

// Membership
$fs->contains(3);              // true
$fs->contains(99);             // false

// Set algebra (all return new PyFrozenSet)
$other = new PyFrozenSet([3, 4, 5, 6]);
$fs->union($other);            // frozenset({1, 2, 3, 4, 5, 6})
$fs->intersection($other);     // frozenset({3, 4})
$fs->difference($other);       // frozenset({1, 2})
$fs->symmetric_difference($other);  // frozenset({1, 2, 5, 6})

// Comparisons
$fs->issubset(new PyFrozenSet([1,2,3,4,5]));  // true
$fs->issuperset(new PyFrozenSet([1,2]));       // true
$fs->isdisjoint(new PyFrozenSet([5,6]));       // true

// Hashable — safe to use as dict keys
$fs->hash();                   // deterministic integer hash

// Equality
$fs->equals(new PyFrozenSet([4, 3, 2, 1]));   // true (order-independent)

// Convert
$fs->toList();                 // PyList
$fs->toSet();                  // PySet (mutable)
$fs->copy();                   // new PyFrozenSet

// Helper
$fs = py_frozenset([1, 2, 3]);
$fs = Py::frozenset([1, 2, 3]);

echo $fs;                      // frozenset({1, 2, 3})
echo new PyFrozenSet();        // frozenset()
```

---

## PyPath — `pathlib.Path`

```php
use QXS\pythonic\PyPath;

$p = new PyPath('/home/user/docs/file.txt');

// Properties (accessible via ->)
$p->name;                      // 'file.txt'
$p->stem;                      // 'file'
$p->suffix;                    // '.txt'
$p->suffixes;                  // ['.txt']
$p->parent;                    // PyPath('/home/user/docs')
$p->parts;                     // ['/', 'home', 'user', 'docs', 'file.txt']
$p->anchor;                    // '/'

// Build new paths (immutable — returns new PyPath)
$p->join('sub', 'other.txt');  // PyPath('/home/user/docs/sub/other.txt')
$p->with_name('other.md');     // PyPath('/home/user/docs/other.md')
$p->with_stem('backup');       // PyPath('/home/user/docs/backup.txt')
$p->with_suffix('.md');        // PyPath('/home/user/docs/file.md')

// Division operator
$p->__div('sub');              // PyPath('/home/user/docs/file.txt/sub')

// Filesystem operations
$p->exists();                  // bool
$p->is_file();                 // bool
$p->is_dir();                  // bool
$p->stat();                    // file stats array

// Read / Write
$p->write_text('hello');       // int (bytes written)
$p->read_text();               // 'hello'
$p->write_bytes($data);       // int
$p->read_bytes();              // string

// Directory operations
$p->mkdir(recursive: true);    // create directory (+ parents)
$p->rmdir();                   // remove empty directory
$p->unlink();                  // delete file
$p->touch();                   // create file / update mtime
$p->rename($newPath);          // rename or move
$p->glob('*.txt');             // PyList of matching PyPaths
$p->iterdir();                 // PyList of PyPath entries

// Static constructors
PyPath::cwd();                 // current working directory
PyPath::home();                // home directory

// Helper
$p = py_path('/tmp/myfile.txt');
$p = Py::path('/tmp/myfile.txt');

echo $p;                       // /home/user/docs/file.txt
```

---

## Operator Overloading

Python-style dunder methods on core types:

```php
// ─── PyList ─────────────────────────────────────────────────
$a = py([1, 2, 3]);
$a->__add([4, 5]);            // PyList [1, 2, 3, 4, 5]   (like + in Python)
$a->__mul(3);                  // PyList [1,2,3,1,2,3,1,2,3] (like * in Python)
$a->__contains(2);             // true                     (like `in`)
$a->__eq([1, 2, 3]);          // true                     (like ==)

// ─── PyDict ─────────────────────────────────────────────────
$d = py(['a' => 1]);
$d->__or(['b' => 2]);         // PyDict {'a': 1, 'b': 2}  (like | in Python 3.9+)
$d->__ior(['b' => 2]);        // in-place merge            (like |=)
$d->__contains('a');           // true
$d->__eq(['a' => 1]);         // true

// ─── PySet ──────────────────────────────────────────────────
$s = py_set([1, 2, 3]);
$s->__or(py_set([3, 4]));     // union       {1, 2, 3, 4}
$s->__and(py_set([2, 3]));    // intersection {2, 3}
$s->__sub(py_set([3]));       // difference   {1, 2}
$s->__xor(py_set([3, 4]));    // symmetric    {1, 2, 4}
$s->__contains(2);             // true
$s->__eq(py_set([3, 2, 1]));  // true

// ─── PyCounter ──────────────────────────────────────────────
$c1 = PyCounter::fromMapping(['a' => 3]);
$c2 = PyCounter::fromMapping(['a' => 1]);
$c1->__add($c2);              // Counter({'a': 4})
$c1->__sub($c2);              // Counter({'a': 2})
```

---

## Python Exceptions

A full exception hierarchy mirroring Python:

```php
use QXS\pythonic\{PyException, ValueError, KeyError, IndexError,
    PyTypeError, AttributeError, StopIteration, FileNotFoundError,
    ZeroDivisionError, NotImplementedError};

// All extend PyException (which extends \RuntimeException)
throw new ValueError("invalid literal for int()");
throw new KeyError('name');           // "KeyError: 'name'"
throw new IndexError();               // "list index out of range"
throw new PyTypeError("unsupported operand type");
throw new AttributeError('PyList', 'foo');
// "'PyList' object has no attribute 'foo'"
throw new FileNotFoundError('/path'); // "[Errno 2] No such file or directory: '/path'"
throw new ZeroDivisionError();        // "division by zero"
throw new NotImplementedError();

// StopIteration carries a value
$e = new StopIteration(42);
$e->getValue();                // 42

// Hierarchy
new ValueError("x") instanceof PyException;       // true
new ValueError("x") instanceof \RuntimeException;  // true

// Python repr
$e = new ValueError("bad value");
$e->pyRepr();                  // "QXS\pythonic\ValueError('bad value')"
$e->pyStr();                   // "bad value"
```

---

## Pattern Matching

```php
// Simple value matching
$result = py_match($statusCode, [
    200 => fn() => 'OK',
    404 => fn() => 'Not Found',
    500 => fn() => 'Server Error',
    '_' => fn() => 'Unknown',
]);

// Predicate-based matching
$category = py_match_when($age, [
    [fn($x) => $x < 13,  fn() => 'child'],
    [fn($x) => $x < 20,  fn() => 'teenager'],
    [fn($x) => $x < 65,  fn() => 'adult'],
    [null,                fn() => 'senior'],
]);
```

---

## Pipe & Tap

Chain arbitrary transformations:

```php
// Pipe — transform the entire object
$result = py([5, 3, 8, 1])
    ->sorted()
    ->pipe(fn($list) => $list->sum());
// 17

// Tap — side effects without breaking the chain
py([3, 1, 2])
    ->tap(fn($l) => error_log("Before: {$l}"))
    ->sorted()
    ->tap(fn($l) => error_log("After: {$l}"))
    ->toPhp();
```

---

## Python repr() Output

Every object prints in Python style:

```php
echo py([1, "hello", true, null]);
// [1, 'hello', True, None]

echo py(["name" => "Alice", "active" => true]);
// {'name': 'Alice', 'active': True}

echo py_set([1, 2, 3]);
// {1, 2, 3}

echo py_range(0, 10, 2);
// range(0, 10, 2)
```

---

## Quick Reference

| Python | qxsch/pythonic |
|---|---|
| `list(...)` | `py([...])` or `py_list(...)` |
| `dict(...)` | `py({...})` or `py_dict([...])` |
| `str(...)` | `py("...")` or `py_str(...)` |
| `set(...)` | `py_set([...])` |
| `frozenset(...)` | `py_frozenset([...])` or `new PyFrozenSet(...)` |
| `range(n)` | `py_range(n)` |
| `collections.Counter(...)` | `py_counter(...)` or `new PyCounter(...)` |
| `collections.defaultdict(...)` | `py_defaultdict(fn)` or `new PyDefaultDict(fn)` |
| `collections.deque(...)` | `py_deque(...)` or `new PyDeque(...)` |
| `pathlib.Path(...)` | `py_path(...)` or `new PyPath(...)` |
| `itertools.chain(...)` | `Itertools::chain(...)` |
| `itertools.product(...)` | `Itertools::product(...)` |
| `len(x)` | `py_len($x)` or `$x->__len()` |
| `x[i]` | `$x[$i]` (negative too!) |
| `x[1:3]` | `$x->slice(1, 3)` |
| `x[::-1]` | `$x->slice(null, null, -1)` |
| `[f(x) for x in lst if g(x)]` | `$lst->comp(fn($x) => f($x), fn($x) => g($x))` |
| `x in lst` | `$lst->contains($x)` or `$lst->__contains($x)` |
| `lst1 + lst2` | `$lst1->__add($lst2)` or `$lst1->concat($lst2)` |
| `lst * 3` | `$lst->__mul(3)` or `$lst->repeat(3)` |
| `d1 \| d2` | `$d1->__or($d2)` or `$d1->merge($d2)` |
| `s1 & s2` | `$s1->__and($s2)` or `$s1->intersection($s2)` |
| `sorted(lst, key=fn)` | `py_sorted($lst, key: $fn)` |
| `enumerate(lst)` | `py_enumerate($lst)` |
| `zip(a, b)` | `py_zip($a, $b)` |
| `", ".join(lst)` | `py(", ")->join($lst)` |
| `f"Hello {name}"` | `py("Hello {name}")->f(["name" => $name])` |
| `with open(...) as f:` | `py_with(fopen(...), fn($f) => ...)` |
| `match value:` | `py_match($value, [...])` |
| `raise ValueError(...)` | `throw new ValueError(...)` |
| `raise KeyError(...)` | `throw new KeyError(...)` |

