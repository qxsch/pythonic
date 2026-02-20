# 🐍 qxsch/pythonic

**Python-like syntax for PHP objects.** Write PHP that *feels* like Python.

**Goal:** Give PHP developers the joy, power and expressiveness of Python's built-in data structures and functions.

```
composer require qxsch/pythonic
```

---

## The `py()` Magic Function

One function to rule them all — auto-detects the type and wraps it:

```php
$list   = py([1, 2, 3, 4, 5]);           // → PyList
$dict   = py(["name" => "Alice"]);        // → PyDict
$string = py("hello world");              // → PyString
```

Everything is **fluently chainable** and uses Python method names.


## Explicit Constructors are available too

In addtion, you can also explicitly construct the objects if you prefer:

```php
$list   = py_list([1, 2, 3]);               // short hand for lists
$list   = new PyList([1, 2, 3]);            // same thing (short hand calls this under the hood)
$dict   = py_dict(["name" => "Alice"]);     // short hand for dicts
$dict   = new PyDict(["name" => "Alice"]);  // same thing (short hand calls this under the hood)
$string = py_string("hello world");         // short hand for strings
$string = new PyString("hello world");      // same thing (short hand calls this under the hood)
```

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

// Pythonic string slice notation (like x[1:3] in Python)
$nums["1:4"];                  // PyList [1, 4, 1]  — same as ->slice(1, 4)
$nums["::2"];                  // PyList [3, 4, 5, 2]  — every 2nd
$nums["::-1"];                 // PyList [6, 2, 9, 5, 1, 4, 1, 3]  — reversed

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

// Pythonic string slice notation
$s["0:5"];                     // PyString "Hello"
$s["::-1"];                    // PyString "!dlroW ,olleH"
$s["::2"];                     // every 2nd character

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
Itertools::filterfalse(fn($x) => $x % 2, [1,2,3,4,5,6]); // 2, 4, 6 (inverse of filter)
Itertools::tee([1, 2, 3], 2);               // [Gen1, Gen2] — two independent copies

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

// Most common
$c->most_common(2);            // PyList [['a', 3], ['b', 2]]

// Elements — expand counts back to items
$c->elements();                // PyList ['a', 'a', 'a', 'b', 'b', 'c']

// Total count
$c->total();                   // 6

// From a mapping also works
$c = PyCounter::fromMapping(['x' => 5, 'y' => 2]);

// From a string (counts characters)
$c = new PyCounter("hello");
$c['l'];                       // 2

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

## PyChainMap — `collections.ChainMap`

```php
use QXS\pythonic\PyChainMap;

// Layer multiple dicts — first map wins on lookup
$defaults = ['color' => 'red', 'size' => 'medium', 'theme' => 'light'];
$user     = ['color' => 'blue', 'font' => 'mono'];
$cm = new PyChainMap($user, $defaults);

$cm['color'];                  // 'blue'   (found in first map)
$cm['size'];                   // 'medium' (falls through to defaults)
$cm['font'];                   // 'mono'   (first map only)

// Writes only affect the first map
$cm['size'] = 'large';
$cm['size'];                   // 'large' (now in first map)

// new_child() — push a new layer on top
$session = $cm->new_child(['color' => 'green']);
$session['color'];             // 'green'
$session['size'];              // 'large'

// parents — skip the first map
$session->parents['color'];    // 'blue'

// Dict-like methods
$cm->get('missing', 'N/A');    // 'N/A'
$cm->contains('color');        // true
$cm->keys();                   // PyList of all unique keys
$cm->values();                 // PyList of merged values
$cm->items();                  // PyList of [key, value] pairs
$cm->pop('font');              // 'mono' (removes from first map)
$cm->clear();                  // clears only the first map

// Access the underlying maps directly
$cm->maps;                     // array of PyDict objects
$cm->maps[0];                  // the first (active) map
$cm->maps[1];                  // the second map

// Conversion
$cm->toPhp();                  // flat PHP array (merged)
$cm->toDict();                 // PyDict (merged)
json_encode($cm);              // JSON of merged view

// Helper
$cm = py_chainmap(['a' => 1], ['b' => 2]);
$cm = Py::chainmap(['a' => 1], ['b' => 2]);

echo $cm;
// ChainMap({'a': 1}, {'b': 2})
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

## PyTuple — Immutable Sequences

```php
use QXS\pythonic\PyTuple;

// Create tuples
$t = new PyTuple([1, 2, 3]);
$t = py_tuple(1, 2, 3);
$t = Py::tuple(1, 2, 3);

// Immutable — these throw RuntimeException:
// $t[0] = 99;       // TypeError: 'tuple' object does not support item assignment
// unset($t[0]);     // TypeError: 'tuple' object does not support item deletion

// Negative indexing
$t[-1];              // 3
$t[-2];              // 2
$t[0];               // 1

// Slicing (returns new PyTuple)
$t = py_tuple(0, 1, 2, 3, 4, 5);
$t->slice(1, 4);           // (1, 2, 3)
$t->slice(0, null, 2);     // (0, 2, 4)
$t->slice(null, null, -1); // (5, 4, 3, 2, 1, 0)

// String slice notation
$t["1:4"];                  // PyTuple(1, 2, 3)
$t["::2"];                  // PyTuple(0, 2, 4)
$t["::-1"];                 // reversed

// Python tuple methods
$t = py_tuple(1, 2, 3, 2, 1);
$t->index(2);               // 1 (first occurrence)
$t->countOf(2);             // 2
$t->contains(3);            // true

// Hashable — usable as dict key
$t->hash();                 // deterministic md5 hash

// Functional methods (return new PyTuple)
py_tuple(1, 2, 3, 4)->map(fn($x) => $x * 10);
// (10, 20, 30, 40)

py_tuple(1, 2, 3, 4)->filter(fn($x) => $x > 2);
// (3, 4)

py_tuple(1, 2, 3)->reduce(fn($a, $b) => $a + $b);
// 6

// Concatenation & repetition
py_tuple(1, 2)->concat(py_tuple(3, 4));   // (1, 2, 3, 4)
py_tuple(1, 2)->repeat(3);               // (1, 2, 1, 2, 1, 2)

// Sorting (returns new tuple)
py_tuple(3, 1, 2)->sorted();             // (1, 2, 3)
py_tuple(3, 1, 2)->reversed();           // (2, 1, 3)

// Conversions
$t->toList();              // PyList
$t->toSet();               // PySet
$t->toPhp();               // plain PHP array

// Python repr
echo py_tuple(1, 'hello', true);   // (1, 'hello', True)
echo py_tuple(42);                  // (42,)  — single-element tuple
echo new PyTuple();                // ()
```

---

## PyJson — `json` Module

```php
use QXS\pythonic\PyJson;

// ─── json.loads() — Decode JSON to Pythonic types (recursively) ───

$data = PyJson::loads('{"name": "Alice", "scores": [95, 87]}');
// $data is a PyDict with:
//   "name"   → PyString("Alice")
//   "scores" → PyList([95, 87])

$data['name'];                // PyString("Alice")
$data['scores'][0];           // 95
$data['name']->upper();       // PyString("ALICE")  — it's a real PyString!
$data['scores']->sum();       // 182 — it's a real PyList!

// Nested structures are fully wrapped:
$json = '{"users": [{"name": "Bob", "tags": ["admin", "user"]}]}';
$d = PyJson::loads($json);
$d['users'][0]['name'];       // PyString("Bob")
$d['users'][0]['tags']->contains(PyJson::loads('"admin"'));  // works!

// Disable wrapping (returns plain PHP arrays like json_decode):
$plain = PyJson::loads('{"a": 1}', wrap: false);
// $plain === ["a" => 1]  (plain PHP array)

// ─── json.dumps() — Encode to JSON string ────────────────────

$dict = py(["name" => "Alice", "age" => 30]);
PyJson::dumps($dict);
// '{"name":"Alice","age":30}'

// Pretty-print with custom indent
PyJson::dumps($dict, indent: 2);
// {
//   "name": "Alice",
//   "age": 30
// }

// Sort keys
PyJson::dumps($dict, sort_keys: true);
// '{"age":30,"name":"Alice"}'

// Works with all Pythonic types:
PyJson::dumps(py_tuple(1, 2, 3));      // '[1,2,3]'
PyJson::dumps(py_set([1, 2, 3]));      // '[1,2,3]'
PyJson::dumps(py("hello"));             // '"hello"'

// ─── json.load() / json.dump() — File I/O ────────────────────

// Write JSON to file
PyJson::dump(["name" => "Alice"], '/tmp/data.json', indent: 2);

// Read JSON from file → Pythonic types
$data = PyJson::load('/tmp/data.json');

// ─── Helper functions ─────────────────────────────────────────

$data = py_json_loads('{"x": 1}');        // PyDict
$json = py_json_dumps($data);              // '{"x":1}'

// Via Py class
$data = Py::json_loads('{"x": 1}');
$json = Py::json_dumps($data);
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

## PyOrderedDict — `collections.OrderedDict`

```php
use QXS\pythonic\PyOrderedDict;

// Create from associative array (preserves insertion order)
$od = new PyOrderedDict(['banana' => 3, 'apple' => 4, 'pear' => 1]);
// Helper
$od = py_ordereddict(['banana' => 3, 'apple' => 4, 'pear' => 1]);
// Via Py
$od = Py::ordereddict(['banana' => 3, 'apple' => 4, 'pear' => 1]);

// All PyDict methods work
$od['grape'] = 5;
$od->keys();                           // PyList(['banana', 'apple', 'pear', 'grape'])
$od->values();                         // PyList([3, 4, 1, 5])
$od->items();                          // PyList of PyTuple pairs
$od->get('apple');                     // 4
$od->pop('pear');                      // 1 (removed)
$od->contains('banana');               // true

// OrderedDict-specific: move_to_end
$od->move_to_end('banana');            // moves 'banana' to the end
$od->move_to_end('apple', last: false);// moves 'apple' to the front

// popitem — pop from end (default) or beginning
$od->popitem();                        // ['grape', 5] (last)
$od->popitem(last: false);             // ['apple', 4] (first)

// reversed — returns new OrderedDict in reverse order
$reversed = $od->reversed();

// ─── Positional access & manipulation ────────────────────

$od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]);

// index_of — get the 0-based position of a key
$od->index_of('b');                    // 1

// key_at / item_at — access by numeric position (negative = from end)
$od->key_at(0);                        // 'a'
$od->key_at(-1);                       // 'd'
$od->item_at(1);                       // ['b', 2]

// insert_at — insert at a specific position
$od->insert_at(1, 'x', 99);           // a=1, x=99, b=2, c=3, d=4

// insert_before / insert_after — insert relative to a key
$od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
$od->insert_before('b', 'x', 99);     // a=1, x=99, b=2, c=3
$od->insert_after('b', 'y', 88);      // a=1, x=99, b=2, y=88, c=3

// move_to — move an existing key to a numeric position
$od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
$od->move_to('c', 0);                 // c=3, a=1, b=2

// move_before / move_after — move relative to another key
$od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
$od->move_before('c', 'a');           // c=3, a=1, b=2

$od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
$od->move_after('a', 'c');            // b=2, c=3, a=1

// swap — swap positions of two keys
$od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
$od->swap('a', 'c');                   // c=3, b=2, a=1

// reorder — rearrange all entries to a given key sequence
$od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
$od->reorder(['c', 'a', 'b']);         // c=3, a=1, b=2

// Order-sensitive equality (unlike PyDict)
$a = new PyOrderedDict(['x' => 1, 'y' => 2]);
$b = new PyOrderedDict(['y' => 2, 'x' => 1]);
$a->__eq($b);                         // false (different order)

// fromkeys
$od = PyOrderedDict::fromkeys(['a', 'b', 'c'], 0);
// OrderedDict([('a', 0), ('b', 0), ('c', 0)])

echo $od;  // OrderedDict([('banana', 3), ('apple', 4), ...])
```

---

## Functools — `functools` Module

```php
use QXS\pythonic\Functools;

// partial() — freeze some arguments
$add = fn($a, $b) => $a + $b;
$add5 = Functools::partial($add, 5);
$add5(3);                              // 8

// reduce() — fold/accumulate
Functools::reduce(fn($a, $b) => $a + $b, [1, 2, 3, 4]); // 10
Functools::reduce(fn($a, $b) => $a * $b, [1, 2, 3], 10); // 60

// lru_cache() — memoize with LRU eviction
$fib = Functools::lru_cache(function (int $n) use (&$fib): int {
    return $n <= 1 ? $n : $fib($n - 1) + $fib($n - 2);
}, maxsize: 128);
$fib(50);

Functools::cache_info($fib);           // PyDict({hits, misses, maxsize, currsize})
Functools::cache_clear($fib);          // reset cache

// cache() — unbounded memoization (no eviction)
$expensive = Functools::cache(fn($x) => $x * $x);

// cmp_to_key() — convert comparator to key function for sorting
$cmp = fn($a, $b) => $b - $a;         // reverse
$key = Functools::cmp_to_key($cmp);
py_sorted([3, 1, 2], key: $key);      // [3, 2, 1]

// compose() — left-to-right function composition
$transform = Functools::compose(
    fn($x) => $x * 2,
    fn($x) => $x + 1,
);
$transform(5);                         // 11 = (5*2)+1

// wraps() — attach original callable metadata to a wrapper
$greet = fn(string $name) => "Hello, {$name}";
$wrapper = function (string $name) use ($greet) {
    return strtoupper($greet($name));
};
Functools::wraps($wrapper, $greet);    // attach metadata
Functools::wrapped($wrapper)->name;    // 'Closure@file.php:42'
Functools::wrapped($wrapper)->wrapped; // original $greet

// Helper functions
$add5 = py_partial($add, 5);
$sum  = py_reduce(fn($a, $b) => $a + $b, [1, 2, 3, 4]);

// Via Py class
$add5 = Py::partial($add, 5);
$sum  = Py::reduce(fn($a, $b) => $a + $b, [1, 2, 3, 4]);
```

---

## PyCsv — `csv` Module

All reader functions return **framework types** (PyList of PyList/PyDict with PyString values).

```php
use QXS\pythonic\PyCsv;

// csv.reader() — read file as PyList of PyList rows
$rows = PyCsv::reader('/path/to/data.csv');
// → PyList([PyList(['Alice', '30']), PyList(['Bob', '25'])])

// csv.DictReader() — read file as PyList of PyDict rows (first row = headers)
$rows = PyCsv::DictReader('/path/to/data.csv');
// → PyList([PyDict({'name': 'Alice', 'age': '30'}), ...])

// Read from string (no file needed)
$csv = "name,age\nAlice,30\nBob,25";
$rows = PyCsv::reader_from_string($csv);
$rows = PyCsv::DictReader_from_string($csv);

// Custom delimiter
$rows = PyCsv::reader('/path/to/data.tsv', delimiter: "\t");

// csv.writer() — write rows to file
PyCsv::writer('/path/to/out.csv', [
    ['name', 'age'],
    ['Alice', '30'],
    ['Bob', '25'],
]);

// csv.DictWriter() — write dicts to file with header
PyCsv::DictWriter('/path/to/out.csv', ['name', 'age'], [
    ['name' => 'Alice', 'age' => '30'],
    ['name' => 'Bob', 'age' => '25'],
]);

// Write to string (in-memory)
$csvStr = PyCsv::writer_to_string([['a', 'b'], ['1', '2']]);
// → PyString "a,b\n1,2\n"
$csvStr = PyCsv::DictWriter_to_string(['x', 'y'], [['x' => '1', 'y' => '2']]);

// Helper functions
$rows = py_csv_reader('/path/to/file.csv');
$rows = py_csv_dictreader('/path/to/file.csv');

// Via Py class
$rows = Py::csv_reader('/path/to/file.csv');
$rows = Py::csv_DictReader('/path/to/file.csv');
Py::csv_writer('/path/to/out.csv', $rows);
```

---

## Operator — `operator` Module

Callable wrappers for operators — perfect as `key` functions for sorting, mapping.

```php
use QXS\pythonic\Operator;

// itemgetter — fetch values by key (single or multiple)
$getName = Operator::itemgetter('name');
$getName(['name' => 'Alice', 'age' => 30]);  // 'Alice'

$getMulti = Operator::itemgetter('name', 'age');
$getMulti(['name' => 'Alice', 'age' => 30]); // PyTuple('Alice', 30)

// Use with sorting
$people = py([['name' => 'Bob', 'age' => 25], ['name' => 'Alice', 'age' => 30]]);
$people->sorted(key: Operator::itemgetter('name'));
// [['name' => 'Alice', ...], ['name' => 'Bob', ...]]

// attrgetter — fetch object attributes (supports dotted paths)
$getX = Operator::attrgetter('x');
$getX($point);  // $point->x

// methodcaller — call methods
$upper = Operator::methodcaller('upper');
$upper(py("hello"));  // PyString('HELLO')

// Arithmetic as callables
$add = Operator::add();
$add(2, 3);  // 5

$mul = Operator::mul();
$mul(4, 5);  // 20

// Comparison as callables
$lt = Operator::lt();
$lt(1, 2);   // true

// Available: add, sub, mul, truediv, floordiv, mod, pow, neg, pos, abs
//            lt, le, eq, ne, ge, gt
//            and_, or_, xor_, invert, not_, truth
//            contains, concat, length_hint, getitem, setitem, delitem

// Helper functions
$fn = py_itemgetter('name');
$fn = py_attrgetter('x');
```

---

## PyDateTime — `datetime` Module

```php
use QXS\pythonic\PyDateTime;
use QXS\pythonic\PyTimeDelta;

// Create datetime
$now  = PyDateTime::now();
$dt   = new PyDateTime('2024-06-15 10:30:00');
$dt   = PyDateTime::fromtimestamp(1718444400);
$dt   = PyDateTime::fromisoformat('2024-06-15T10:30:00');
$dt   = PyDateTime::strptime('2024-06-15', '%Y-%m-%d');
$dt   = PyDateTime::combine('2024-06-15', '10:30:00');

// Formatting (returns PyString)
$dt->strftime('%Y-%m-%d %H:%M:%S');    // PyString '2024-06-15 10:30:00'
$dt->isoformat();                       // PyString '2024-06-15T10:30:00'
$dt->date();                            // PyString '2024-06-15'
$dt->time();                            // PyString '10:30:00'

// Components (Python-style attribute access)
$dt->year;         // 2024
$dt->month;        // 6
$dt->day;          // 15
$dt->hour;         // 10
$dt->minute;       // 30
$dt->second;       // 0

// Calendar
$dt->weekday();        // 5 (0=Monday, 5=Saturday)
$dt->isoweekday();     // 6 (ISO: 1=Mon..7=Sun)
$dt->isocalendar();    // PyTuple(2024, 24, 6)
$dt->timestamp();      // Unix timestamp as float

// Timedelta — durations
$delta = new PyTimeDelta(days: 5, hours: 3);
$delta->total_seconds();               // 442800.0
$delta->getDays();                     // 5  (or $delta->days)
$delta->getSeconds();                  // 10800  (or $delta->seconds)
$delta->microseconds;                  // 0  (attribute access)

// Arithmetic
$future = $dt->add(new PyTimeDelta(days: 7));
$past   = $dt->sub(new PyTimeDelta(hours: 12));
$diff   = $dt->diff($other);          // PyTimeDelta

// Timedelta arithmetic
$d1 = new PyTimeDelta(days: 3);
$d2 = new PyTimeDelta(days: 5);
$d1->add($d2);                        // PyTimeDelta(days=8)
$d1->sub($d2);                        // PyTimeDelta(days=-2)
$d1->mul(3);                          // PyTimeDelta(days=9)
$d1->neg();                           // PyTimeDelta(days=-3)
$d1->abs();                           // PyTimeDelta(days=3)

// Replace fields
$dt->replace(year: 2025, month: 1);

// Comparison
$dt->__eq($other);
$dt->__lt($other);

// Timedelta comparison
$d1->__eq($d2);                       // false
$d1->__lt($d2);                       // true (3 < 5)
$d1->__le($d2);  $d1->__gt($d2);  $d1->__ge($d2);

// Helpers
$dt    = py_datetime('2024-06-15');
$delta = py_timedelta(days: 5);
// Via Py
$dt    = Py::datetime('2024-06-15');
$delta = Py::timedelta(days: 5);

echo $dt;     // 2024-06-15T10:30:00
echo $delta;  // 5 days, 3:00:00
```

---

## Heapq — `heapq` Module

Priority queue operations on PyList (min-heap — smallest element at index 0).

```php
use QXS\pythonic\Heapq;
use QXS\pythonic\PyList;

$heap = new PyList();

// Push items (maintains heap invariant)
Heapq::heappush($heap, 5);
Heapq::heappush($heap, 1);
Heapq::heappush($heap, 3);
// heap: [1, 5, 3]

// Pop smallest
Heapq::heappop($heap);    // 1
Heapq::heappop($heap);    // 3

// heapify — transform a list into a heap in-place
$data = new PyList([3, 1, 4, 1, 5, 9, 2, 6]);
Heapq::heapify($data);    // data is now a valid heap
Heapq::heappop($data);    // 1

// heapreplace — pop + push in one step
Heapq::heapreplace($data, 10);  // pops smallest, pushes 10

// heappushpop — push + pop in one step
Heapq::heappushpop($data, 0);   // pushes 0, returns smallest

// nlargest / nsmallest → PyList
Heapq::nlargest(3, [5, 1, 8, 3, 9, 2]);   // PyList([9, 8, 5])
Heapq::nsmallest(3, [5, 1, 8, 3, 9, 2]);  // PyList([1, 2, 3])

// With key function
$people = [['name' => 'Bob', 'age' => 25], ['name' => 'Alice', 'age' => 30]];
Heapq::nsmallest(1, $people, key: fn($p) => $p['age']);
// → PyList with youngest person

// merge — merge sorted iterables
Heapq::merge([1, 3, 5], [2, 4, 6]);  // PyList([1, 2, 3, 4, 5, 6])
```

---

## Bisect — `bisect` Module

O(log n) binary-search insertion into sorted sequences. Works with plain PHP arrays **and** `PyList`.

```php
use QXS\pythonic\Bisect;

$sorted = [1, 3, 5, 7, 9];

// bisect_left — insertion point BEFORE existing equal values
Bisect::bisect_left($sorted, 5);    // 2

// bisect_right — insertion point AFTER existing equal values (alias: bisect)
Bisect::bisect_right($sorted, 5);   // 3
Bisect::bisect($sorted, 5);         // 3  (alias)

// insort_left / insort_right / insort — insert keeping sorted order
$arr = [1, 3, 5, 7];
Bisect::insort($arr, 4);            // $arr → [1, 3, 4, 5, 7]
Bisect::insort_left($arr, 5);       // $arr → [1, 3, 4, 5, 5, 7]

// With PyList
$list = new PyList([10, 20, 30, 40]);
Bisect::insort($list, 25);          // $list → [10, 20, 25, 30, 40]

// With key function — search by a derived value
$people = [['age' => 20], ['age' => 30], ['age' => 40]];
Bisect::bisect_left($people, ['age' => 30], key: fn($x) => $x['age']);  // 1

// Convenience: index — O(log n) find in sorted sequence (-1 if missing)
Bisect::index($sorted, 5);         // 2
Bisect::index($sorted, 6);         // -1

// Convenience: count — O(log n) count of value in sorted sequence
$dupes = [1, 2, 2, 2, 3, 4];
Bisect::count($dupes, 2);           // 3

// Convenience: contains — O(log n) membership test
Bisect::contains($sorted, 5);       // true
Bisect::contains($sorted, 6);       // false

// lo / hi bounds — restrict search to a slice
Bisect::bisect_left($sorted, 5, lo: 1, hi: 4);  // 2

// Access via helper / Py
py_bisect_left($sorted, 5);
py_bisect_right($sorted, 5);
py_insort($arr, 6);
```

---

## Shutil — `shutil` Module

High-level file and directory operations that complement `PyPath`. Accepts both `string` and `PyPath` arguments.

```php
use QXS\pythonic\Shutil;

// copyfile — copy file content only
Shutil::copyfile('/src/file.txt', '/dst/file.txt');

// copy — copy file + preserve permissions
Shutil::copy('/src/file.txt', '/dst/');           // into directory
Shutil::copy('/src/file.txt', '/dst/other.txt');   // to specific path

// copy2 — copy file + preserve permissions AND timestamps
Shutil::copy2('/src/file.txt', '/dst/file.txt');

// copytree — recursively copy entire directory tree
Shutil::copytree('/src/project', '/backup/project');

// copytree with dirs_exist_ok (merge into existing dir)
Shutil::copytree('/src', '/dst', dirs_exist_ok: true);

// copytree with ignore — skip patterns
Shutil::copytree('/src', '/dst', ignore: Shutil::ignore_patterns('*.log', '__pycache__'));

// rmtree — recursively remove directory tree
Shutil::rmtree('/tmp/build');
Shutil::rmtree('/tmp/maybe', ignore_errors: true);

// move — move file or directory (cross-device safe)
Shutil::move('/old/file.txt', '/new/file.txt');
Shutil::move('/old/dir', '/new/dir');

// disk_usage — total/used/free bytes
$usage = Shutil::disk_usage('/');
echo $usage['total'];  // e.g. 500107862016
echo $usage['used'];
echo $usage['free'];

// which — find executable in PATH
Shutil::which('php');     // '/usr/bin/php' or null
Shutil::which('git');     // '/usr/bin/git' or null

// make_archive — create .zip or .tar.gz
Shutil::make_archive('/tmp/backup', 'zip', '/src/project');

// unpack_archive — extract .zip or .tar.gz
Shutil::unpack_archive('/tmp/backup.zip', '/dst/project');

// Access via helper / Py
py_rmtree('/tmp/build');
py_copytree('/src', '/dst');
py_which('php');
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
| `tuple(...)` | `py_tuple(...)` or `Py::tuple(...)` or `new PyTuple(...)` |
| `dict(...)` | `py({...})` or `py_dict([...])` |
| `str(...)` | `py("...")` or `py_str(...)` |
| `set(...)` | `py_set([...])` |
| `frozenset(...)` | `py_frozenset([...])` or `new PyFrozenSet(...)` |
| `range(n)` | `py_range(n)` |
| `collections.Counter(...)` | `py_counter(...)` or `new PyCounter(...)` |
| `collections.defaultdict(...)` | `py_defaultdict(fn)` or `new PyDefaultDict(fn)` |
| `collections.deque(...)` | `py_deque(...)` or `new PyDeque(...)` |
| `pathlib.Path(...)` | `py_path(...)` or `new PyPath(...)` |
| `json.loads(s)` | `PyJson::loads($s)` or `py_json_loads($s)` |
| `json.dumps(obj)` | `PyJson::dumps($obj)` or `py_json_dumps($obj)` |
| `json.load(fp)` | `PyJson::load($path)` |
| `json.dump(obj, fp)` | `PyJson::dump($obj, $path)` |
| `itertools.chain(...)` | `Itertools::chain(...)` |
| `itertools.product(...)` | `Itertools::product(...)` |
| `collections.OrderedDict(...)` | `py_ordereddict(...)` or `new PyOrderedDict(...)` |
| `functools.partial(fn, ...)` | `Functools::partial($fn, ...)` or `py_partial(...)` |
| `functools.reduce(fn, iter)` | `Functools::reduce($fn, $iter)` or `py_reduce(...)` |
| `functools.lru_cache(fn)` | `Functools::lru_cache($fn)` |
| `csv.reader(f)` | `PyCsv::reader($path)` or `py_csv_reader($path)` |
| `csv.DictReader(f)` | `PyCsv::DictReader($path)` or `py_csv_dictreader($path)` |
| `csv.writer(f)` | `PyCsv::writer($path, $rows)` |
| `csv.DictWriter(f, fields)` | `PyCsv::DictWriter($path, $fields, $rows)` |
| `operator.itemgetter(k)` | `Operator::itemgetter($k)` or `py_itemgetter($k)` |
| `operator.attrgetter(a)` | `Operator::attrgetter($a)` or `py_attrgetter($a)` |
| `operator.methodcaller(m)` | `Operator::methodcaller($m)` |
| `datetime.datetime.now()` | `PyDateTime::now()` or `py_datetime()` |
| `datetime.timedelta(days=5)` | `new PyTimeDelta(days: 5)` or `py_timedelta(days: 5)` |
| `heapq.heappush(h, x)` | `Heapq::heappush($h, $x)` |
| `heapq.heappop(h)` | `Heapq::heappop($h)` |
| `heapq.nlargest(n, iter)` | `Heapq::nlargest($n, $iter)` |
| `heapq.nsmallest(n, iter)` | `Heapq::nsmallest($n, $iter)` |
| `len(x)` | `py_len($x)` or `$x->__len()` |
| `x[i]` | `$x[$i]` (negative too!) |
| `x[1:3]` | `$x->slice(1, 3)` or `$x["1:3"]` |
| `x[::-1]` | `$x->slice(null, null, -1)` or `$x["::-1"]` |
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

