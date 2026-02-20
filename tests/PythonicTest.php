<?php

declare(strict_types=1);

namespace QXS\pythonic\Tests;

use PHPUnit\Framework\TestCase;
use QXS\pythonic\Py;
use QXS\pythonic\PyList;
use QXS\pythonic\PyDict;
use QXS\pythonic\PyString;
use QXS\pythonic\PySet;
use QXS\pythonic\PyRange;
use QXS\pythonic\PyDataClass;
use QXS\pythonic\Itertools;
use QXS\pythonic\PyCounter;
use QXS\pythonic\PyDefaultDict;
use QXS\pythonic\PyPath;
use QXS\pythonic\PyDeque;
use QXS\pythonic\PyFrozenSet;
use QXS\pythonic\PyChainMap;
use QXS\pythonic\PyException;
use QXS\pythonic\ValueError;
use QXS\pythonic\KeyError;
use QXS\pythonic\IndexError;
use QXS\pythonic\AttributeError;
use QXS\pythonic\StopIteration;
use QXS\pythonic\PyTuple;
use QXS\pythonic\PyJson;
use QXS\pythonic\PyOrderedDict;
use QXS\pythonic\Functools;
use QXS\pythonic\PyCsv;
use QXS\pythonic\Operator;
use QXS\pythonic\PyDateTime;
use QXS\pythonic\PyTimeDelta;
use QXS\pythonic\Heapq;
use QXS\pythonic\Bisect;
use QXS\pythonic\Shutil;

class PythonicTest extends TestCase
{
    // ═══════════════════════════════════════════════════════════
    //  PyList
    // ═══════════════════════════════════════════════════════════

    public function test_py_auto_detects_list(): void
    {
        $list = py([1, 2, 3]);
        $this->assertInstanceOf(PyList::class, $list);
    }

    public function test_list_negative_indexing(): void
    {
        $list = py([10, 20, 30, 40]);
        $this->assertSame(40, $list[-1]);
        $this->assertSame(30, $list[-2]);
        $this->assertSame(10, $list[0]);
    }

    public function test_list_slicing(): void
    {
        $list = py([0, 1, 2, 3, 4, 5]);
        $this->assertSame([1, 2, 3], $list->slice(1, 4)->toPhp());
        $this->assertSame([0, 2, 4], $list->slice(0, null, 2)->toPhp());
        $this->assertSame([5, 4, 3, 2, 1, 0], $list->slice(null, null, -1)->toPhp());
    }

    public function test_list_string_slice_notation(): void
    {
        $list = py([0, 1, 2, 3, 4, 5]);

        // basic slice
        $this->assertSame([1, 2, 3], $list["1:4"]->toPhp());
        // step
        $this->assertSame([0, 2, 4], $list["0::2"]->toPhp());
        // reverse
        $this->assertSame([5, 4, 3, 2, 1, 0], $list["::-1"]->toPhp());
        // every 3rd
        $this->assertSame([0, 3], $list["::3"]->toPhp());
        // start only
        $this->assertSame([3, 4, 5], $list["3:"]->toPhp());
        // stop only
        $this->assertSame([0, 1, 2], $list[":3"]->toPhp());
        // negative index via string still works as single element
        $this->assertSame(5, $list["-1"]);
        // negative in slice
        $this->assertSame([3, 4], $list["-3:-1"]->toPhp());
    }

    public function test_list_comprehension(): void
    {
        $result = py([1, 2, 3, 4, 5])->comp(
            fn($x) => $x ** 2,
            fn($x) => $x > 2
        );
        $this->assertSame([9, 16, 25], $result->toPhp());
    }

    public function test_list_fluent_chain(): void
    {
        $result = py([5, 3, 8, 1, 9])
            ->filter(fn($x) => $x > 3)
            ->map(fn($x) => $x * 10)
            ->sorted()
            ->toPhp();
        $this->assertSame([50, 80, 90], $result);
    }

    public function test_list_append_extend_pop(): void
    {
        $list = py([1, 2]);
        $list->append(3)->extend([4, 5]);
        $this->assertSame([1, 2, 3, 4, 5], $list->toPhp());

        $popped = $list->pop();
        $this->assertSame(5, $popped);
        $this->assertSame([1, 2, 3, 4], $list->toPhp());

        $popped = $list->pop(0);
        $this->assertSame(1, $popped);
    }

    public function test_list_contains(): void
    {
        $list = py([1, 2, 3]);
        $this->assertTrue($list->contains(2));
        $this->assertFalse($list->contains(99));
    }

    public function test_list_aggregation(): void
    {
        $list = py([1, 2, 3, 4, 5]);
        $this->assertSame(15, $list->sum());
        $this->assertSame(1, $list->min());
        $this->assertSame(5, $list->max());
        $this->assertTrue($list->any());
        $this->assertTrue($list->all());

        $this->assertTrue(py([0, 1])->any());
        $this->assertFalse(py([0, 0])->any());
        $this->assertFalse(py([1, 0])->all());
    }

    public function test_list_enumerate(): void
    {
        $result = py(["a", "b", "c"])->enumerate()->toPhp();
        $this->assertSame([[0, "a"], [1, "b"], [2, "c"]], $result);
    }

    public function test_list_zip(): void
    {
        $result = py([1, 2, 3])->zip(["a", "b", "c"])->toPhp();
        $this->assertSame([[1, "a"], [2, "b"], [3, "c"]], $result);
    }

    public function test_list_unique(): void
    {
        $this->assertSame([1, 2, 3], py([1, 2, 2, 3, 3, 3])->unique()->toPhp());
    }

    public function test_list_flatten(): void
    {
        $this->assertSame([1, 2, 3, 4], py([[1, 2], [3, 4]])->flatten()->toPhp());
    }

    public function test_list_take_drop(): void
    {
        $list = py([1, 2, 3, 4, 5]);
        $this->assertSame([1, 2, 3], $list->take(3)->toPhp());
        $this->assertSame([3, 4, 5], $list->drop(2)->toPhp());
    }

    public function test_list_groupby(): void
    {
        $groups = py([1, 2, 3, 4, 5, 6])->groupby(fn($x) => $x % 2 === 0 ? 'even' : 'odd');
        $this->assertSame([1, 3, 5], $groups->get('odd')->toPhp());
        $this->assertSame([2, 4, 6], $groups->get('even')->toPhp());
    }

    public function test_list_join(): void
    {
        $result = py(["a", "b", "c"])->join(", ");
        $this->assertSame("a, b, c", $result->toPhp());
    }

    public function test_list_repr(): void
    {
        $this->assertSame("[1, 2, 'hello', True, None]", (string)py([1, 2, "hello", true, null]));
    }

    public function test_list_repeat(): void
    {
        $this->assertSame([1, 2, 1, 2, 1, 2], py([1, 2])->repeat(3)->toPhp());
    }

    public function test_list_sorted_with_key(): void
    {
        $result = py(["banana", "apple", "cherry"])->sorted(fn($s) => strlen($s));
        $this->assertSame(["apple", "banana", "cherry"], $result->toPhp());
    }

    public function test_list_reduce(): void
    {
        $sum = py([1, 2, 3, 4])->reduce(fn($a, $b) => $a + $b);
        $this->assertSame(10, $sum);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyDict
    // ═══════════════════════════════════════════════════════════

    public function test_py_auto_detects_dict(): void
    {
        $dict = py(["name" => "Alice", "age" => 30]);
        $this->assertInstanceOf(PyDict::class, $dict);
    }

    public function test_dict_attribute_access(): void
    {
        $dict = py(["name" => "Alice", "age" => 30]);
        $this->assertSame("Alice", $dict->name);
        $this->assertSame(30, $dict->age);
    }

    public function test_dict_array_access(): void
    {
        $dict = py(["a" => 1, "b" => 2]);
        $this->assertSame(1, $dict["a"]);
        $dict["c"] = 3;
        $this->assertSame(3, $dict["c"]);
    }

    public function test_dict_get_with_default(): void
    {
        $dict = py(["a" => 1]);
        $this->assertSame(1, $dict->get("a", 0));
        $this->assertSame(0, $dict->get("missing", 0));
        $this->assertNull($dict->get("missing"));
    }

    public function test_dict_keys_values_items(): void
    {
        $dict = py(["a" => 1, "b" => 2]);
        $this->assertSame(["a", "b"], $dict->keys()->toPhp());
        $this->assertSame([1, 2], $dict->values()->toPhp());
        $this->assertSame([["a", 1], ["b", 2]], $dict->items()->toPhp());
    }

    public function test_dict_merge(): void
    {
        $merged = py(["a" => 1])->merge(["b" => 2], ["c" => 3]);
        $this->assertSame(["a" => 1, "b" => 2, "c" => 3], $merged->toPhp());
    }

    public function test_dict_comprehension(): void
    {
        $prices = py(["apple" => 1.0, "banana" => 0.5, "cherry" => 3.0]);
        $expensive = $prices->comp(
            fn($k, $v) => [$k, $v * 2],
            fn($k, $v) => $v > 0.7
        );
        $this->assertSame(["apple" => 2.0, "cherry" => 6.0], $expensive->toPhp());
    }

    public function test_dict_pop_setdefault(): void
    {
        $dict = py(["a" => 1, "b" => 2]);
        $this->assertSame(2, $dict->pop("b"));
        $this->assertFalse($dict->contains("b"));

        $this->assertSame(99, $dict->setdefault("c", 99));
        $this->assertSame(99, $dict["c"]);
    }

    public function test_dict_contains(): void
    {
        $dict = py(["x" => 1]);
        $this->assertTrue($dict->contains("x"));
        $this->assertFalse($dict->contains("y"));
    }

    public function test_dict_repr(): void
    {
        $this->assertSame("{'name': 'Alice', 'age': 30}", (string)py(["name" => "Alice", "age" => 30]));
    }

    public function test_dict_fromkeys(): void
    {
        $dict = PyDict::fromkeys(["a", "b", "c"], 0);
        $this->assertSame(["a" => 0, "b" => 0, "c" => 0], $dict->toPhp());
    }

    // ═══════════════════════════════════════════════════════════
    //  PyString
    // ═══════════════════════════════════════════════════════════

    public function test_py_auto_detects_string(): void
    {
        $str = py("hello");
        $this->assertInstanceOf(PyString::class, $str);
    }

    public function test_string_negative_indexing(): void
    {
        $s = py("hello");
        $this->assertSame("h", $s[0]);
        $this->assertSame("o", $s[-1]);
        $this->assertSame("l", $s[-2]);
    }

    public function test_string_slicing(): void
    {
        $s = py("hello world");
        $this->assertSame("hello", $s->slice(0, 5)->toPhp());
        $this->assertSame("dlrow olleh", $s->slice(null, null, -1)->toPhp());
    }

    public function test_string_string_slice_notation(): void
    {
        $s = py("hello world");

        // basic slice
        $this->assertSame("hello", $s["0:5"]->toPhp());
        // reverse
        $this->assertSame("dlrow olleh", $s["::-1"]->toPhp());
        // every 2nd char
        $this->assertSame("hlowrd", $s["::2"]->toPhp());
        // start only
        $this->assertSame("world", $s["6:"]->toPhp());
        // stop only
        $this->assertSame("hel", $s[":3"]->toPhp());
        // negative index via string still works as single char
        $this->assertSame("d", $s["-1"]);
        // negative in slice
        $this->assertSame("worl", $s["-5:-1"]->toPhp());
    }

    public function test_string_case_methods(): void
    {
        $s = py("hello World");
        $this->assertSame("HELLO WORLD", $s->upper()->toPhp());
        $this->assertSame("hello world", $s->lower()->toPhp());
        $this->assertSame("Hello World", $s->title()->toPhp());
        $this->assertSame("Hello world", $s->capitalize()->toPhp());
        $this->assertSame("HELLO wORLD", $s->swapcase()->toPhp());
    }

    public function test_string_strip(): void
    {
        $this->assertSame("hello", py("  hello  ")->strip()->toPhp());
        $this->assertSame("hello  ", py("  hello  ")->lstrip()->toPhp());
        $this->assertSame("  hello", py("  hello  ")->rstrip()->toPhp());
    }

    public function test_string_split_join(): void
    {
        $this->assertSame(["hello", "world"], py("hello world")->split()->toPhp());
        $this->assertSame(["a", "b", "c"], py("a,b,c")->split(",")->toPhp());
        $this->assertSame("a, b, c", py(", ")->join(["a", "b", "c"])->toPhp());
    }

    public function test_string_fstring(): void
    {
        $result = py("Hello {name}, age {age}")->f(["name" => "Alice", "age" => 30]);
        $this->assertSame("Hello Alice, age 30", $result->toPhp());
    }

    public function test_string_format(): void
    {
        $result = py("{0} + {1} = {2}")->format(1, 2, 3);
        $this->assertSame("1 + 2 = 3", $result->toPhp());
    }

    public function test_string_find_contains(): void
    {
        $s = py("hello world");
        $this->assertSame(6, $s->find("world"));
        $this->assertSame(-1, $s->find("xyz"));
        $this->assertTrue($s->contains("hello"));
        $this->assertFalse($s->contains("xyz"));
    }

    public function test_string_startswith_endswith(): void
    {
        $s = py("hello world");
        $this->assertTrue($s->startswith("hello"));
        $this->assertTrue($s->endswith("world"));
        $this->assertFalse($s->startswith("world"));
    }

    public function test_string_replace(): void
    {
        $this->assertSame("hello PHP", py("hello world")->replace("world", "PHP")->toPhp());
    }

    public function test_string_is_methods(): void
    {
        $this->assertTrue(py("12345")->isdigit());
        $this->assertTrue(py("abcde")->isalpha());
        $this->assertTrue(py("abc123")->isalnum());
        $this->assertTrue(py("   ")->isspace());
        $this->assertFalse(py("abc 123")->isalnum());
    }

    public function test_string_padding(): void
    {
        $this->assertSame("00042", py("42")->zfill(5)->toPhp());
        $this->assertSame("hi--------", py("hi")->ljust(10, '-')->toPhp());
    }

    public function test_string_repeat(): void
    {
        $this->assertSame("abcabcabc", py("abc")->repeat(3)->toPhp());
    }

    public function test_string_partition(): void
    {
        $this->assertSame(["hello", "=", "world"], py("hello=world")->partition("=")->toPhp());
    }

    public function test_string_immutable(): void
    {
        $this->expectException(\LogicException::class);
        $s = py("hello");
        $s[0] = "X";
    }

    public function test_string_regex(): void
    {
        $matches = py("hello 123 world 456")->re_findall('/\d+/');
        $this->assertSame(["123", "456"], $matches->toPhp());

        $result = py("hello world")->re_sub('/world/', 'PHP');
        $this->assertSame("hello PHP", $result->toPhp());
    }

    public function test_string_repr(): void
    {
        $this->assertSame("'hello'", (string)py("hello"));
    }

    // ═══════════════════════════════════════════════════════════
    //  PySet
    // ═══════════════════════════════════════════════════════════

    public function test_set_operations(): void
    {
        $a = py_set([1, 2, 3, 4]);
        $b = py_set([3, 4, 5, 6]);

        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6], $a->union($b)->toPhp());
        $this->assertEqualsCanonicalizing([3, 4], $a->intersection($b)->toPhp());
        $this->assertEqualsCanonicalizing([1, 2], $a->difference($b)->toPhp());
        $this->assertEqualsCanonicalizing([1, 2, 5, 6], $a->symmetric_difference($b)->toPhp());
    }

    public function test_set_membership(): void
    {
        $set = py_set([1, 2, 3]);
        $this->assertTrue($set->contains(2));
        $this->assertFalse($set->contains(99));
    }

    public function test_set_subset_superset(): void
    {
        $a = py_set([1, 2, 3]);
        $b = py_set([1, 2, 3, 4, 5]);

        $this->assertTrue($a->issubset($b));
        $this->assertFalse($b->issubset($a));
        $this->assertTrue($b->issuperset($a));
    }

    public function test_set_add_remove(): void
    {
        $set = py_set([1, 2]);
        $set->add(3);
        $this->assertTrue($set->contains(3));

        $set->discard(99); // no error
        $set->remove(1);
        $this->assertFalse($set->contains(1));
    }

    public function test_set_comprehension(): void
    {
        $result = py_set([1, 2, 3, 4])->comp(fn($x) => $x ** 2, fn($x) => $x > 2);
        $this->assertEqualsCanonicalizing([9, 16], $result->toPhp());
    }

    // ═══════════════════════════════════════════════════════════
    //  PyRange
    // ═══════════════════════════════════════════════════════════

    public function test_range_basic(): void
    {
        $this->assertSame([0, 1, 2, 3, 4], py_range(5)->toPhp());
        $this->assertSame([2, 3, 4], py_range(2, 5)->toPhp());
        $this->assertSame([0, 2, 4], py_range(0, 5, 2)->toPhp());
    }

    public function test_range_negative_step(): void
    {
        $this->assertSame([5, 4, 3, 2, 1], py_range(5, 0, -1)->toPhp());
    }

    public function test_range_contains(): void
    {
        $r = py_range(0, 1000000, 3);
        $this->assertTrue($r->contains(999));
        $this->assertFalse($r->contains(998));
    }

    public function test_range_sum(): void
    {
        $this->assertSame(5050, py_range(1, 101)->sum());
    }

    public function test_range_comprehension(): void
    {
        $result = py_range(10)->comp(fn($x) => $x ** 2, fn($x) => $x % 2 === 0);
        $this->assertSame([0, 4, 16, 36, 64], $result->toPhp());
    }

    public function test_range_repr(): void
    {
        $this->assertSame("range(0, 10)", (string)py_range(10));
        $this->assertSame("range(0, 10, 2)", (string)py_range(0, 10, 2));
    }

    // ═══════════════════════════════════════════════════════════
    //  PyDataClass
    // ═══════════════════════════════════════════════════════════

    public function test_dataclass_repr(): void
    {
        $user = new TestUser("Alice", 30);
        $this->assertSame("TestUser(name='Alice', age=30, email='')", (string)$user);
    }

    public function test_dataclass_equality(): void
    {
        $a = new TestUser("Alice", 30);
        $b = new TestUser("Alice", 30);
        $c = new TestUser("Bob", 25);
        $this->assertTrue($a->eq($b));
        $this->assertFalse($a->eq($c));
    }

    public function test_dataclass_asdict(): void
    {
        $user = new TestUser("Alice", 30, "alice@test.com");
        $dict = $user->asdict();
        $this->assertInstanceOf(PyDict::class, $dict);
        $this->assertSame("Alice", $dict->get("name"));
        $this->assertSame(30, $dict->get("age"));
    }

    public function test_dataclass_copy(): void
    {
        $alice = new TestUser("Alice", 30);
        $bob = $alice->copy(name: "Bob", age: 25);
        $this->assertSame("Bob", $bob->name);
        $this->assertSame(25, $bob->age);
    }

    public function test_dataclass_json(): void
    {
        $user = new TestUser("Alice", 30);
        $json = json_encode($user);
        $this->assertSame('{"name":"Alice","age":30,"email":""}', $json);
    }

    // ═══════════════════════════════════════════════════════════
    //  Global Helpers
    // ═══════════════════════════════════════════════════════════

    public function test_py_enumerate(): void
    {
        $result = py_enumerate(["a", "b", "c"])->toPhp();
        $this->assertSame([[0, "a"], [1, "b"], [2, "c"]], $result);
    }

    public function test_py_zip(): void
    {
        $result = py_zip([1, 2, 3], ["a", "b", "c"])->toPhp();
        $this->assertSame([[1, "a"], [2, "b"], [3, "c"]], $result);
    }

    public function test_py_sorted(): void
    {
        $this->assertSame([1, 2, 3], py_sorted([3, 1, 2])->toPhp());
    }

    public function test_py_reversed(): void
    {
        $this->assertSame([3, 2, 1], py_reversed([1, 2, 3])->toPhp());
    }

    public function test_py_map_filter(): void
    {
        $this->assertSame([2, 4, 6], py_map(fn($x) => $x * 2, [1, 2, 3])->toPhp());
        $this->assertSame([3, 4, 5], py_filter(fn($x) => $x > 2, [1, 2, 3, 4, 5])->toPhp());
    }

    public function test_py_sum_min_max(): void
    {
        $this->assertSame(6, py_sum([1, 2, 3]));
        $this->assertSame(1, py_min([3, 1, 2]));
        $this->assertSame(3, py_max([3, 1, 2]));
    }

    public function test_py_any_all(): void
    {
        $this->assertTrue(py_any([0, 0, 1]));
        $this->assertFalse(py_any([0, 0, 0]));
        $this->assertTrue(py_all([1, 1, 1]));
        $this->assertFalse(py_all([1, 0, 1]));
    }

    public function test_py_len(): void
    {
        $this->assertSame(3, py_len(py([1, 2, 3])));
        $this->assertSame(5, py_len(py("hello")));
    }

    public function test_py_type(): void
    {
        $this->assertSame("PyList", py_type(py([1, 2])));
        $this->assertSame("PyString", py_type(py("hello")));
        $this->assertSame("PyDict", py_type(py(["a" => 1])));
    }

    public function test_py_isinstance(): void
    {
        $this->assertTrue(py_isinstance(py([]), PyList::class));
        $this->assertFalse(py_isinstance(py([]), PyDict::class));
    }

    // ═══════════════════════════════════════════════════════════
    //  Pattern Matching
    // ═══════════════════════════════════════════════════════════

    public function test_py_match(): void
    {
        $result = py_match(200, [
            200 => fn() => 'OK',
            404 => fn() => 'Not Found',
            '_' => fn() => 'Unknown',
        ]);
        $this->assertSame('OK', $result);

        $result = py_match(999, [
            200 => fn() => 'OK',
            '_' => fn() => 'Unknown',
        ]);
        $this->assertSame('Unknown', $result);
    }

    public function test_py_match_when(): void
    {
        $result = py_match_when(15, [
            [fn($x) => $x < 13, fn() => 'child'],
            [fn($x) => $x < 20, fn() => 'teenager'],
            [null, fn() => 'adult'],
        ]);
        $this->assertSame('teenager', $result);
    }

    // ═══════════════════════════════════════════════════════════
    //  Pipe & Tap
    // ═══════════════════════════════════════════════════════════

    public function test_pipe(): void
    {
        $result = py([1, 2, 3, 4])->pipe(fn($l) => $l->sum());
        $this->assertSame(10, $result);
    }

    public function test_tap(): void
    {
        $sideEffect = null;
        $result = py([3, 1, 2])
            ->tap(function ($l) use (&$sideEffect) { $sideEffect = $l->toPhp(); })
            ->sorted();
        $this->assertSame([3, 1, 2], $sideEffect);
        $this->assertSame([1, 2, 3], $result->toPhp());
    }

    // ═══════════════════════════════════════════════════════════
    //  Context Manager
    // ═══════════════════════════════════════════════════════════

    public function test_with_auto_closes(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'pytest');
        file_put_contents($tmpFile, "hello world");

        $content = py_with(fopen($tmpFile, 'r'), function ($f) {
            return fgets($f);
        });

        $this->assertSame("hello world", $content);
        unlink($tmpFile);
    }

    // ═══════════════════════════════════════════════════════════
    //  JSON Serialization
    // ═══════════════════════════════════════════════════════════

    public function test_json_serialize(): void
    {
        $this->assertSame('[1,2,3]', json_encode(py([1, 2, 3])));
        $this->assertSame('{"a":1,"b":2}', json_encode(py(["a" => 1, "b" => 2])));
        $this->assertSame('"hello"', json_encode(py("hello")));
    }

    // ═══════════════════════════════════════════════════════════
    //  Iteration / Foreach
    // ═══════════════════════════════════════════════════════════

    public function test_foreach_list(): void
    {
        $result = [];
        foreach (py([10, 20, 30]) as $v) {
            $result[] = $v;
        }
        $this->assertSame([10, 20, 30], $result);
    }

    public function test_foreach_dict(): void
    {
        $result = [];
        foreach (py(["a" => 1, "b" => 2]) as $k => $v) {
            $result[$k] = $v;
        }
        $this->assertSame(["a" => 1, "b" => 2], $result);
    }

    public function test_foreach_range(): void
    {
        $result = [];
        foreach (py_range(3) as $i) {
            $result[] = $i;
        }
        $this->assertSame([0, 1, 2], $result);
    }

    public function test_foreach_string_chars(): void
    {
        $result = [];
        foreach (py("abc") as $ch) {
            $result[] = $ch;
        }
        $this->assertSame(["a", "b", "c"], $result);
    }

    public function test_spread_list(): void
    {
        $arr = [...py([1, 2, 3])];
        $this->assertSame([1, 2, 3], $arr);
    }

    // ═══════════════════════════════════════════════════════════
    //  Itertools
    // ═══════════════════════════════════════════════════════════

    public function test_itertools_chain(): void
    {
        $result = Itertools::toList(Itertools::chain([1, 2], [3, 4], [5]));
        $this->assertSame([1, 2, 3, 4, 5], $result->toPhp());
    }

    public function test_itertools_cycle(): void
    {
        $result = Itertools::toList(Itertools::islice(Itertools::cycle([1, 2, 3]), 7));
        $this->assertSame([1, 2, 3, 1, 2, 3, 1], $result->toPhp());
    }

    public function test_itertools_repeat(): void
    {
        $result = Itertools::toList(Itertools::repeat('x', 4));
        $this->assertSame(['x', 'x', 'x', 'x'], $result->toPhp());
    }

    public function test_itertools_compress(): void
    {
        $result = Itertools::toList(Itertools::compress(['a', 'b', 'c', 'd'], [1, 0, 1, 0]));
        $this->assertSame(['a', 'c'], $result->toPhp());
    }

    public function test_itertools_accumulate(): void
    {
        $result = Itertools::toList(Itertools::accumulate([1, 2, 3, 4, 5]));
        $this->assertSame([1, 3, 6, 10, 15], $result->toPhp());
    }

    public function test_itertools_accumulate_with_fn(): void
    {
        $result = Itertools::toList(Itertools::accumulate([1, 2, 3, 4], fn($a, $b) => $a * $b));
        $this->assertSame([1, 2, 6, 24], $result->toPhp());
    }

    public function test_itertools_takewhile(): void
    {
        $result = Itertools::toList(Itertools::takewhile(fn($x) => $x < 4, [1, 2, 3, 4, 5, 1]));
        $this->assertSame([1, 2, 3], $result->toPhp());
    }

    public function test_itertools_dropwhile(): void
    {
        $result = Itertools::toList(Itertools::dropwhile(fn($x) => $x < 4, [1, 2, 3, 4, 5, 1]));
        $this->assertSame([4, 5, 1], $result->toPhp());
    }

    public function test_itertools_islice(): void
    {
        $result = Itertools::toList(Itertools::islice([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 2, 8, 2));
        $this->assertSame([2, 4, 6], $result->toPhp());
    }

    public function test_itertools_pairwise(): void
    {
        $result = Itertools::toList(Itertools::pairwise([1, 2, 3, 4]));
        $this->assertSame([[1, 2], [2, 3], [3, 4]], $result->toPhp());
    }

    public function test_itertools_zip_longest(): void
    {
        $result = Itertools::toList(Itertools::zip_longest('-', [1, 2, 3], ['a', 'b']));
        $this->assertSame([[1, 'a'], [2, 'b'], [3, '-']], $result->toPhp());
    }

    public function test_itertools_product(): void
    {
        $result = Itertools::toList(Itertools::product([1, 2], ['a', 'b']));
        $this->assertSame([[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']], $result->toPhp());
    }

    public function test_itertools_permutations(): void
    {
        $result = Itertools::toList(Itertools::permutations([1, 2, 3], 2));
        $this->assertCount(6, $result);
        $this->assertSame([1, 2], $result[0]);
    }

    public function test_itertools_combinations(): void
    {
        $result = Itertools::toList(Itertools::combinations([1, 2, 3, 4], 2));
        $this->assertCount(6, $result);
        $this->assertSame([1, 2], $result[0]);
        $this->assertSame([3, 4], $result[5]);
    }

    public function test_itertools_combinations_with_replacement(): void
    {
        $result = Itertools::toList(Itertools::combinations_with_replacement([1, 2], 3));
        $this->assertCount(4, $result);
        $this->assertSame([1, 1, 1], $result[0]);
        $this->assertSame([2, 2, 2], $result[3]);
    }

    public function test_itertools_count(): void
    {
        $result = Itertools::toList(Itertools::islice(Itertools::count(5, 3), 4));
        $this->assertSame([5, 8, 11, 14], $result->toPhp());
    }

    public function test_itertools_groupby(): void
    {
        $data = ['aaa', 'aab', 'bba', 'bbb', 'ccc'];
        $groups = Itertools::toList(Itertools::groupby($data, fn($s) => $s[0]));
        $this->assertSame('a', $groups[0][0]);
        $this->assertSame(['aaa', 'aab'], $groups[0][1]->toPhp());
        $this->assertSame('b', $groups[1][0]);
        $this->assertSame('c', $groups[2][0]);
    }

    public function test_itertools_starmap(): void
    {
        $result = Itertools::toList(Itertools::starmap(fn($a, $b) => $a + $b, [[1, 2], [3, 4], [5, 6]]));
        $this->assertSame([3, 7, 11], $result->toPhp());
    }

    // ─── filterfalse ─────────────────────────────────────────

    public function test_itertools_filterfalse_with_predicate(): void
    {
        $result = Itertools::toList(Itertools::filterfalse(fn($x) => $x % 2, [1, 2, 3, 4, 5, 6]));
        $this->assertSame([2, 4, 6], $result->toPhp());
    }

    public function test_itertools_filterfalse_null_predicate(): void
    {
        $result = Itertools::toList(Itertools::filterfalse(null, [0, 1, '', 'hello', false, true, null, 42]));
        $this->assertSame([0, '', false, null], $result->toPhp());
    }

    public function test_itertools_filterfalse_empty(): void
    {
        $result = Itertools::toList(Itertools::filterfalse(fn($x) => $x > 0, []));
        $this->assertSame([], $result->toPhp());
    }

    public function test_itertools_filterfalse_all_pass(): void
    {
        $result = Itertools::toList(Itertools::filterfalse(fn($x) => $x > 0, [1, 2, 3]));
        $this->assertSame([], $result->toPhp());
    }

    public function test_itertools_filterfalse_none_pass(): void
    {
        $result = Itertools::toList(Itertools::filterfalse(fn($x) => $x > 10, [1, 2, 3]));
        $this->assertSame([1, 2, 3], $result->toPhp());
    }

    // ─── tee ─────────────────────────────────────────────────

    public function test_itertools_tee_default_two(): void
    {
        [$a, $b] = Itertools::tee([1, 2, 3]);
        $this->assertSame([1, 2, 3], Itertools::toList($a)->toPhp());
        $this->assertSame([1, 2, 3], Itertools::toList($b)->toPhp());
    }

    public function test_itertools_tee_three_copies(): void
    {
        [$a, $b, $c] = Itertools::tee([10, 20, 30], 3);
        $this->assertSame([10, 20, 30], Itertools::toList($a)->toPhp());
        $this->assertSame([10, 20, 30], Itertools::toList($b)->toPhp());
        $this->assertSame([10, 20, 30], Itertools::toList($c)->toPhp());
    }

    public function test_itertools_tee_zero(): void
    {
        $result = Itertools::tee([1, 2, 3], 0);
        $this->assertSame([], $result);
    }

    public function test_itertools_tee_one(): void
    {
        [$only] = Itertools::tee([1, 2, 3], 1);
        $this->assertSame([1, 2, 3], Itertools::toList($only)->toPhp());
    }

    public function test_itertools_tee_empty_iterable(): void
    {
        [$a, $b] = Itertools::tee([]);
        $this->assertSame([], Itertools::toList($a)->toPhp());
        $this->assertSame([], Itertools::toList($b)->toPhp());
    }

    public function test_itertools_tee_independent_consumption(): void
    {
        [$a, $b] = Itertools::tee([1, 2, 3, 4, 5]);
        // Consume only part of $a
        $aItems = [];
        foreach ($a as $item) {
            $aItems[] = $item;
            if (count($aItems) === 2) break;
        }
        // $b should still yield all items
        $this->assertSame([1, 2, 3, 4, 5], Itertools::toList($b)->toPhp());
    }

    public function test_itertools_tee_with_generator_input(): void
    {
        $gen = Itertools::count(1, 1);
        $sliced = Itertools::islice($gen, 5);
        [$a, $b] = Itertools::tee($sliced);
        $this->assertSame([1, 2, 3, 4, 5], Itertools::toList($a)->toPhp());
        $this->assertSame([1, 2, 3, 4, 5], Itertools::toList($b)->toPhp());
    }

    public function test_itertools_helper_chain(): void
    {
        $result = Itertools::toList(py_chain([1, 2], [3, 4]));
        $this->assertSame([1, 2, 3, 4], $result->toPhp());
    }

    public function test_itertools_helper_islice(): void
    {
        $result = Itertools::toList(py_islice([10, 20, 30, 40, 50], 1, 4));
        $this->assertSame([20, 30, 40], $result->toPhp());
    }

    public function test_itertools_helper_accumulate(): void
    {
        $result = Itertools::toList(py_accumulate([1, 2, 3, 4]));
        $this->assertSame([1, 3, 6, 10], $result->toPhp());
    }

    public function test_itertools_helper_groupby(): void
    {
        $data = ['aab', 'aac', 'bba', 'bbc'];
        $groups = [];
        foreach (py_groupby($data, fn($s) => $s[0]) as [$key, $items]) {
            $groups[$key] = Itertools::toList($items)->toPhp();
        }
        $this->assertSame(['a' => ['aab', 'aac'], 'b' => ['bba', 'bbc']], $groups);
    }

    public function test_itertools_helper_product(): void
    {
        $result = Itertools::toList(py_product([1, 2], ['a', 'b']));
        $this->assertSame([[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']], $result->toPhp());
    }

    public function test_itertools_helper_permutations(): void
    {
        $result = Itertools::toList(py_permutations([1, 2, 3], 2));
        $this->assertCount(6, $result);
    }

    public function test_itertools_helper_combinations(): void
    {
        $result = Itertools::toList(py_combinations([1, 2, 3], 2));
        $this->assertSame([[1, 2], [1, 3], [2, 3]], $result->toPhp());
    }

    public function test_itertools_helper_zip_longest(): void
    {
        $result = Itertools::toList(py_zip_longest('-', [1, 2, 3], ['a', 'b']));
        $this->assertSame([[1, 'a'], [2, 'b'], [3, '-']], $result->toPhp());
    }

    public function test_itertools_helper_takewhile(): void
    {
        $result = Itertools::toList(py_takewhile(fn($x) => $x < 4, [1, 2, 3, 5, 1]));
        $this->assertSame([1, 2, 3], $result->toPhp());
    }

    public function test_itertools_helper_dropwhile(): void
    {
        $result = Itertools::toList(py_dropwhile(fn($x) => $x < 4, [1, 2, 3, 5, 1]));
        $this->assertSame([5, 1], $result->toPhp());
    }

    public function test_itertools_helper_starmap(): void
    {
        $result = Itertools::toList(py_starmap(fn($a, $b) => $a * $b, [[2, 3], [4, 5]]));
        $this->assertSame([6, 20], $result->toPhp());
    }

    public function test_itertools_helper_filterfalse(): void
    {
        $result = Itertools::toList(py_filterfalse(fn($x) => $x % 2 === 0, [1, 2, 3, 4, 5]));
        $this->assertSame([1, 3, 5], $result->toPhp());
    }

    public function test_itertools_helper_pairwise(): void
    {
        $result = Itertools::toList(py_pairwise([1, 2, 3, 4]));
        $this->assertSame([[1, 2], [2, 3], [3, 4]], $result->toPhp());
    }

    public function test_itertools_helper_compress(): void
    {
        $result = Itertools::toList(py_compress(['a', 'b', 'c', 'd'], [1, 0, 1, 0]));
        $this->assertSame(['a', 'c'], $result->toPhp());
    }

    // ═══════════════════════════════════════════════════════════
    //  PyCounter
    // ═══════════════════════════════════════════════════════════

    public function test_counter_count_items(): void
    {
        $c = new PyCounter(['a', 'b', 'a', 'c', 'a', 'b']);
        $this->assertSame(3, $c['a']);
        $this->assertSame(2, $c['b']);
        $this->assertSame(1, $c['c']);
    }

    public function test_counter_missing_key_returns_zero(): void
    {
        $c = new PyCounter(['a']);
        $this->assertSame(0, $c['missing']);
    }

    public function test_counter_most_common(): void
    {
        $c = new PyCounter(['a', 'b', 'a', 'c', 'a', 'b']);
        $top2 = $c->most_common(2);
        $this->assertSame([['a', 3], ['b', 2]], $top2->toPhp());
    }

    public function test_counter_elements(): void
    {
        $c = PyCounter::fromMapping(['x' => 3, 'y' => 1]);
        $elements = $c->elements();
        $this->assertCount(4, $elements);
    }

    public function test_counter_arithmetic(): void
    {
        $c1 = PyCounter::fromMapping(['a' => 3, 'b' => 1]);
        $c2 = PyCounter::fromMapping(['a' => 1, 'b' => 5]);

        $added = $c1->add($c2);
        $this->assertSame(4, $added['a']);
        $this->assertSame(6, $added['b']);

        $subbed = $c1->sub($c2);
        $this->assertSame(2, $subbed['a']);
        $this->assertSame(0, $subbed['b']); // negative removed

        $inter = $c1->intersect($c2);
        $this->assertSame(1, $inter['a']);
        $this->assertSame(1, $inter['b']);

        $union = $c1->union($c2);
        $this->assertSame(3, $union['a']);
        $this->assertSame(5, $union['b']);
    }

    public function test_counter_total(): void
    {
        $c = PyCounter::fromMapping(['a' => 3, 'b' => 2]);
        $this->assertSame(5, $c->total());
    }

    public function test_counter_from_string(): void
    {
        $c = new PyCounter("hello");
        $this->assertSame(2, $c['l']);
        $this->assertSame(1, $c['h']);
    }

    public function test_counter_repr(): void
    {
        $c = PyCounter::fromMapping(['a' => 2, 'b' => 1]);
        $this->assertStringContainsString('Counter', $c->__repr());
    }

    public function test_counter_operator_aliases(): void
    {
        $c1 = PyCounter::fromMapping(['a' => 3, 'b' => 1]);
        $c2 = PyCounter::fromMapping(['a' => 1, 'b' => 5]);
        $this->assertSame(4, $c1->__add($c2)['a']);
        $this->assertSame(2, $c1->__sub($c2)['a']);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyDefaultDict
    // ═══════════════════════════════════════════════════════════

    public function test_defaultdict_missing_key_uses_factory(): void
    {
        $dd = PyDefaultDict::ofInt();
        $dd['a'] = 5;
        $this->assertSame(5, $dd['a']);
        $this->assertSame(0, $dd['missing']); // triggers factory
        $this->assertTrue($dd->contains('missing')); // now key exists
    }

    public function test_defaultdict_of_list(): void
    {
        $dd = PyDefaultDict::ofList();
        $val = $dd['colors'];  // triggers factory → PyList
        $val[] = 'red';
        // After access, key should be created
        $this->assertInstanceOf(PyList::class, $dd['colors']);
    }

    public function test_defaultdict_get_does_not_trigger_factory(): void
    {
        $dd = PyDefaultDict::ofInt();
        $this->assertSame(42, $dd->get('x', 42)); // default, not factory
        $this->assertFalse($dd->contains('x')); // key NOT created
    }

    public function test_defaultdict_magic_property_access(): void
    {
        $dd = PyDefaultDict::ofString();
        $val = $dd->name; // triggers factory → ''
        $this->assertSame('', $val);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyChainMap
    // ═══════════════════════════════════════════════════════════

    public function test_chainmap_lookup_first_map_wins(): void
    {
        $cm = new PyChainMap(['color' => 'blue'], ['color' => 'red', 'size' => 'medium']);
        $this->assertSame('blue', $cm['color']);
        $this->assertSame('medium', $cm['size']);
    }

    public function test_chainmap_write_only_affects_first_map(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $cm['b'] = 99;
        // first map now has 'b' => 99
        $this->assertSame(99, $cm['b']);
        // second map is untouched
        $this->assertSame(2, $cm->maps[1]['b']);
    }

    public function test_chainmap_delete_only_from_first_map(): void
    {
        $cm = new PyChainMap(['a' => 1, 'b' => 2], ['b' => 99]);
        unset($cm['b']);
        // 'b' falls through to second map now
        $this->assertSame(99, $cm['b']);
    }

    public function test_chainmap_delete_missing_from_first_throws(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $this->expectException(KeyError::class);
        unset($cm['b']); // 'b' is not in first map
    }

    public function test_chainmap_missing_key_throws(): void
    {
        $cm = new PyChainMap(['a' => 1]);
        $this->expectException(KeyError::class);
        $cm['missing'];
    }

    public function test_chainmap_contains(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $this->assertTrue($cm->contains('a'));
        $this->assertTrue($cm->contains('b'));
        $this->assertFalse($cm->contains('c'));
    }

    public function test_chainmap_get_with_default(): void
    {
        $cm = new PyChainMap(['a' => 1]);
        $this->assertSame(1, $cm->get('a'));
        $this->assertSame('N/A', $cm->get('missing', 'N/A'));
        $this->assertNull($cm->get('missing'));
    }

    public function test_chainmap_keys_values_items(): void
    {
        $cm = new PyChainMap(['a' => 1, 'b' => 2], ['b' => 99, 'c' => 3]);
        $keys = $cm->keys()->toPhp();
        sort($keys);
        $this->assertSame(['a', 'b', 'c'], $keys);

        $merged = $cm->toPhp();
        $this->assertSame(1, $merged['a']);
        $this->assertSame(2, $merged['b']); // first map wins
        $this->assertSame(3, $merged['c']);

        $this->assertCount(3, $cm->items());
    }

    public function test_chainmap_new_child(): void
    {
        $cm = new PyChainMap(['color' => 'blue'], ['color' => 'red']);
        $child = $cm->new_child(['color' => 'green', 'font' => 'mono']);

        $this->assertSame('green', $child['color']);
        $this->assertSame('mono', $child['font']);
        $this->assertCount(3, $child->maps);
    }

    public function test_chainmap_new_child_empty(): void
    {
        $cm = new PyChainMap(['a' => 1]);
        $child = $cm->new_child();
        $this->assertSame(1, $child['a']);
        $this->assertCount(2, $child->maps);
        $this->assertCount(0, $child->maps[0]);
    }

    public function test_chainmap_parents(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2], ['c' => 3]);
        $parents = $cm->parents;
        $this->assertCount(2, $parents->maps);
        $this->assertFalse($parents->contains('a'));
        $this->assertTrue($parents->contains('b'));
        $this->assertTrue($parents->contains('c'));
    }

    public function test_chainmap_parents_single_map(): void
    {
        $cm = new PyChainMap(['a' => 1]);
        $parents = $cm->parents;
        $this->assertCount(1, $parents->maps);
        $this->assertCount(0, $parents);
    }

    public function test_chainmap_pop(): void
    {
        $cm = new PyChainMap(['a' => 1, 'b' => 2], ['c' => 3]);
        $this->assertSame(1, $cm->pop('a'));
        $this->assertFalse($cm->maps[0]->contains('a'));
    }

    public function test_chainmap_pop_missing_with_default(): void
    {
        $cm = new PyChainMap(['a' => 1]);
        $this->assertSame('nope', $cm->pop('z', 'nope'));
    }

    public function test_chainmap_pop_missing_throws(): void
    {
        $cm = new PyChainMap(['a' => 1]);
        $this->expectException(KeyError::class);
        $cm->pop('z');
    }

    public function test_chainmap_clear(): void
    {
        $cm = new PyChainMap(['a' => 1, 'b' => 2], ['c' => 3]);
        $cm->clear();
        $this->assertCount(0, $cm->maps[0]);
        // second map untouched
        $this->assertSame(3, $cm['c']);
    }

    public function test_chainmap_count(): void
    {
        $cm = new PyChainMap(['a' => 1, 'b' => 2], ['b' => 99, 'c' => 3]);
        $this->assertCount(3, $cm); // 3 unique keys
    }

    public function test_chainmap_iteration(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $items = [];
        foreach ($cm as $k => $v) {
            $items[$k] = $v;
        }
        $this->assertSame(1, $items['a']);
        $this->assertSame(2, $items['b']);
    }

    public function test_chainmap_repr(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $this->assertSame("ChainMap({'a': 1}, {'b': 2})", (string)$cm);
    }

    public function test_chainmap_empty_constructo(): void
    {
        $cm = new PyChainMap();
        $this->assertCount(1, $cm->maps);
        $this->assertCount(0, $cm);
    }

    public function test_chainmap_to_dict(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $dict = $cm->toDict();
        $this->assertInstanceOf(PyDict::class, $dict);
        $this->assertSame(['b' => 2, 'a' => 1], $dict->toPhp());
    }

    public function test_chainmap_json(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $json = json_encode($cm);
        $this->assertSame('{"b":2,"a":1}', $json);
    }

    public function test_chainmap_copy(): void
    {
        $cm = new PyChainMap(['a' => 1], ['b' => 2]);
        $copy = $cm->copy();
        $copy['a'] = 99;
        // original is unaffected
        $this->assertSame(1, $cm['a']);
    }

    public function test_chainmap_eq(): void
    {
        $cm1 = new PyChainMap(['a' => 1], ['b' => 2]);
        $cm2 = new PyChainMap(['a' => 1, 'b' => 2]);
        $this->assertTrue($cm1->__eq($cm2));
    }

    public function test_chainmap_helper_and_static(): void
    {
        $cm1 = py_chainmap(['x' => 10], ['y' => 20]);
        $this->assertInstanceOf(PyChainMap::class, $cm1);
        $this->assertSame(10, $cm1['x']);

        $cm2 = Py::chainmap(['x' => 10], ['y' => 20]);
        $this->assertInstanceOf(PyChainMap::class, $cm2);
        $this->assertSame(20, $cm2['y']);
    }

    public function test_chainmap_scoped_config_pattern(): void
    {
        // Classic use case: layered config
        $defaults  = ['debug' => false, 'log_level' => 'warn', 'retries' => 3];
        $env       = ['log_level' => 'info'];
        $cli       = ['debug' => true];
        $config    = new PyChainMap($cli, $env, $defaults);

        $this->assertTrue($config['debug']);
        $this->assertSame('info', $config['log_level']);
        $this->assertSame(3, $config['retries']);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyPath
    // ═══════════════════════════════════════════════════════════

    public function test_path_properties(): void
    {
        $p = new PyPath('/home/user/docs/file.txt');
        $this->assertSame('file.txt', $p->name);
        $this->assertSame('file', $p->stem);
        $this->assertSame('.txt', $p->suffix);
        $this->assertSame(['.txt'], $p->suffixes);
    }

    public function test_path_parent(): void
    {
        $p = new PyPath('/home/user/docs/file.txt');
        $this->assertSame('/home/user/docs', (string)$p->parent);
    }

    public function test_path_join(): void
    {
        $p = new PyPath('/home/user');
        $joined = $p->join('docs', 'file.txt');
        $this->assertSame('/home/user/docs/file.txt', (string)$joined);
    }

    public function test_path_with_suffix(): void
    {
        $p = new PyPath('/home/user/file.txt');
        $this->assertSame('/home/user/file.md', (string)$p->with_suffix('.md'));
    }

    public function test_path_with_name(): void
    {
        $p = new PyPath('/home/user/file.txt');
        $this->assertSame('/home/user/other.md', (string)$p->with_name('other.md'));
    }

    public function test_path_with_stem(): void
    {
        $p = new PyPath('/home/user/file.txt');
        $this->assertSame('/home/user/other.txt', (string)$p->with_stem('other'));
    }

    public function test_path_parts(): void
    {
        $p = new PyPath('/home/user/file.txt');
        $parts = $p->parts;
        $this->assertContains('home', $parts);
        $this->assertContains('file.txt', $parts);
    }

    public function test_path_div_operator(): void
    {
        $p = new PyPath('/home');
        $joined = $p->div('user')->div('file.txt');
        $this->assertSame('/home/user/file.txt', (string)$joined);
    }

    public function test_path_cwd(): void
    {
        $cwd = PyPath::cwd();
        $this->assertTrue($cwd->is_dir());
    }

    public function test_path_write_read(): void
    {
        $tmp = PyPath::tempfile('phpusing_test_', '.txt');
        $tmp->write_text('Hello, PyPath!');
        $this->assertSame('Hello, PyPath!', $tmp->read_text());
        $tmp->unlink();
    }

    public function test_path_mkdir_rmdir(): void
    {
        $tmp = PyPath::tempdir('phpusing_test_');
        $sub = $tmp->join('subdir');
        $sub->mkdir();
        $this->assertTrue($sub->is_dir());
        $sub->rmdir();
        $this->assertFalse($sub->is_dir());
        $tmp->rmdir();
    }

    // ═══════════════════════════════════════════════════════════
    //  Operator Overloading
    // ═══════════════════════════════════════════════════════════

    public function test_list_add_operator(): void
    {
        $a = py([1, 2]);
        $b = py([3, 4]);
        $this->assertSame([1, 2, 3, 4], $a->__add($b)->toPhp());
    }

    public function test_list_mul_operator(): void
    {
        $list = py([1, 2]);
        $this->assertSame([1, 2, 1, 2, 1, 2], $list->__mul(3)->toPhp());
    }

    public function test_list_contains_operator(): void
    {
        $list = py([1, 2, 3]);
        $this->assertTrue($list->__contains(2));
        $this->assertFalse($list->__contains(5));
    }

    public function test_list_eq_operator(): void
    {
        $this->assertTrue(py([1, 2, 3])->__eq(py([1, 2, 3])));
        $this->assertFalse(py([1, 2])->__eq(py([1, 3])));
    }

    public function test_dict_or_operator(): void
    {
        $a = py(["a" => 1, "b" => 2]);
        $b = py(["b" => 99, "c" => 3]);
        $merged = $a->__or($b);
        $this->assertSame(99, $merged['b']);
        $this->assertSame(3, $merged['c']);
        $this->assertSame(1, $merged['a']);
    }

    public function test_dict_ior_operator(): void
    {
        $a = py(["a" => 1]);
        $a->__ior(["b" => 2]);
        $this->assertSame(2, $a['b']);
    }

    public function test_dict_eq_operator(): void
    {
        $this->assertTrue(py(["a" => 1])->__eq(py(["a" => 1])));
        $this->assertFalse(py(["a" => 1])->__eq(py(["a" => 2])));
    }

    public function test_set_or_operator(): void
    {
        $a = new PySet([1, 2, 3]);
        $b = new PySet([3, 4, 5]);
        $result = $a->__or($b);
        $this->assertCount(5, $result);
    }

    public function test_set_and_operator(): void
    {
        $a = new PySet([1, 2, 3]);
        $b = new PySet([2, 3, 4]);
        $result = $a->__and($b);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains(2));
        $this->assertTrue($result->contains(3));
    }

    public function test_set_sub_operator(): void
    {
        $a = new PySet([1, 2, 3]);
        $b = new PySet([2, 3, 4]);
        $result = $a->__sub($b);
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains(1));
    }

    public function test_set_xor_operator(): void
    {
        $a = new PySet([1, 2, 3]);
        $b = new PySet([2, 3, 4]);
        $result = $a->__xor($b);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains(1));
        $this->assertTrue($result->contains(4));
    }

    public function test_set_eq_operator(): void
    {
        $this->assertTrue((new PySet([1, 2, 3]))->__eq(new PySet([3, 2, 1])));
        $this->assertFalse((new PySet([1, 2]))->__eq(new PySet([1, 3])));
    }

    // ═══════════════════════════════════════════════════════════
    //  PyDeque
    // ═══════════════════════════════════════════════════════════

    public function test_deque_append_pop(): void
    {
        $dq = new PyDeque([1, 2, 3]);
        $dq->append(4);
        $this->assertSame([1, 2, 3, 4], $dq->toPhp());
        $this->assertSame(4, $dq->pop());
        $this->assertSame([1, 2, 3], $dq->toPhp());
    }

    public function test_deque_appendleft_popleft(): void
    {
        $dq = new PyDeque([1, 2, 3]);
        $dq->appendleft(0);
        $this->assertSame([0, 1, 2, 3], $dq->toPhp());
        $this->assertSame(0, $dq->popleft());
    }

    public function test_deque_rotate(): void
    {
        $dq = new PyDeque([1, 2, 3, 4, 5]);
        $dq->rotate(2);
        $this->assertSame([4, 5, 1, 2, 3], $dq->toPhp());

        $dq->rotate(-2);
        $this->assertSame([1, 2, 3, 4, 5], $dq->toPhp());
    }

    public function test_deque_maxlen(): void
    {
        $dq = new PyDeque([1, 2, 3], maxlen: 3);
        $dq->append(4); // drops 1 from left
        $this->assertSame([2, 3, 4], $dq->toPhp());

        $dq->appendleft(0); // drops 4 from right
        $this->assertSame([0, 2, 3], $dq->toPhp());
    }

    public function test_deque_extend(): void
    {
        $dq = new PyDeque([1]);
        $dq->extend([2, 3, 4]);
        $this->assertSame([1, 2, 3, 4], $dq->toPhp());
    }

    public function test_deque_extendleft(): void
    {
        $dq = new PyDeque([1, 2, 3]);
        $dq->extendleft([0, -1]);
        // Each item prepended individually: first 0, then -1 before 0
        $this->assertSame([-1, 0, 1, 2, 3], $dq->toPhp());
    }

    public function test_deque_negative_indexing(): void
    {
        $dq = new PyDeque([10, 20, 30]);
        $this->assertSame(30, $dq[-1]);
        $this->assertSame(10, $dq[0]);
    }

    public function test_deque_remove(): void
    {
        $dq = new PyDeque([1, 2, 3, 2]);
        $dq->remove(2);
        $this->assertSame([1, 3, 2], $dq->toPhp());
    }

    public function test_deque_reverse(): void
    {
        $dq = new PyDeque([1, 2, 3]);
        $dq->reverse();
        $this->assertSame([3, 2, 1], $dq->toPhp());
    }

    public function test_deque_count_of(): void
    {
        $dq = new PyDeque([1, 2, 2, 3, 2]);
        $this->assertSame(3, $dq->countOf(2));
    }

    public function test_deque_index(): void
    {
        $dq = new PyDeque(['a', 'b', 'c']);
        $this->assertSame(1, $dq->index('b'));
    }

    public function test_deque_repr(): void
    {
        $dq = new PyDeque([1, 2, 3], maxlen: 5);
        $repr = $dq->__repr();
        $this->assertStringContainsString('deque', $repr);
        $this->assertStringContainsString('maxlen=5', $repr);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyFrozenSet
    // ═══════════════════════════════════════════════════════════

    public function test_frozenset_contains(): void
    {
        $fs = new PyFrozenSet([1, 2, 3]);
        $this->assertTrue($fs->contains(2));
        $this->assertFalse($fs->contains(5));
    }

    public function test_frozenset_union(): void
    {
        $a = new PyFrozenSet([1, 2]);
        $b = new PyFrozenSet([2, 3]);
        $result = $a->union($b);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(PyFrozenSet::class, $result);
    }

    public function test_frozenset_intersection(): void
    {
        $a = new PyFrozenSet([1, 2, 3]);
        $b = new PyFrozenSet([2, 3, 4]);
        $result = $a->intersection($b);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains(2));
    }

    public function test_frozenset_difference(): void
    {
        $a = new PyFrozenSet([1, 2, 3]);
        $b = new PyFrozenSet([2, 3, 4]);
        $result = $a->difference($b);
        $this->assertCount(1, $result);
        $this->assertTrue($result->contains(1));
    }

    public function test_frozenset_symmetric_difference(): void
    {
        $a = new PyFrozenSet([1, 2, 3]);
        $b = new PyFrozenSet([2, 3, 4]);
        $result = $a->symmetric_difference($b);
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains(1));
        $this->assertTrue($result->contains(4));
    }

    public function test_frozenset_issubset(): void
    {
        $a = new PyFrozenSet([1, 2]);
        $b = new PyFrozenSet([1, 2, 3]);
        $this->assertTrue($a->issubset($b));
        $this->assertFalse($b->issubset($a));
    }

    public function test_frozenset_hash(): void
    {
        $a = new PyFrozenSet([1, 2, 3]);
        $b = new PyFrozenSet([3, 2, 1]);
        $this->assertSame($a->hash(), $b->hash());
    }

    public function test_frozenset_to_set(): void
    {
        $fs = new PyFrozenSet([1, 2, 3]);
        $set = $fs->toSet();
        $this->assertInstanceOf(PySet::class, $set);
        $this->assertCount(3, $set);
    }

    public function test_frozenset_equals(): void
    {
        $a = new PyFrozenSet([1, 2, 3]);
        $b = new PyFrozenSet([3, 1, 2]);
        $this->assertTrue($a->equals($b));
    }

    public function test_frozenset_repr(): void
    {
        $fs = new PyFrozenSet([1, 2]);
        $this->assertStringContainsString('frozenset', $fs->__repr());
    }

    public function test_frozenset_empty_repr(): void
    {
        $fs = new PyFrozenSet();
        $this->assertSame('frozenset()', $fs->__repr());
    }

    // ═══════════════════════════════════════════════════════════
    //  Python Exceptions
    // ═══════════════════════════════════════════════════════════

    public function test_value_error(): void
    {
        $this->expectException(ValueError::class);
        throw new ValueError("invalid value");
    }

    public function test_key_error(): void
    {
        $e = new KeyError('name');
        $this->assertStringContainsString("KeyError: 'name'", $e->getMessage());
    }

    public function test_index_error(): void
    {
        $e = new IndexError();
        $this->assertSame('list index out of range', $e->getMessage());
    }

    public function test_attribute_error(): void
    {
        $e = new AttributeError('PyList', 'foo');
        $this->assertStringContainsString("'PyList' object has no attribute 'foo'", $e->getMessage());
    }

    public function test_stop_iteration(): void
    {
        $e = new StopIteration(42);
        $this->assertSame(42, $e->getValue());
    }

    public function test_exception_hierarchy(): void
    {
        $this->assertInstanceOf(PyException::class, new ValueError("test"));
        $this->assertInstanceOf(\RuntimeException::class, new ValueError("test"));
    }

    public function test_exception_py_repr(): void
    {
        $e = new ValueError("bad value");
        $this->assertStringContainsString('ValueError', $e->pyRepr());
    }

    // ═══════════════════════════════════════════════════════════
    //  Helper functions for new types
    // ═══════════════════════════════════════════════════════════

    public function test_py_counter_helper(): void
    {
        $c = py_counter(['a', 'b', 'a']);
        $this->assertInstanceOf(PyCounter::class, $c);
        $this->assertSame(2, $c['a']);
    }

    public function test_py_defaultdict_helper(): void
    {
        $dd = py_defaultdict(fn() => 0);
        $this->assertInstanceOf(PyDefaultDict::class, $dd);
    }

    public function test_py_deque_helper(): void
    {
        $dq = py_deque([1, 2, 3], maxlen: 5);
        $this->assertInstanceOf(PyDeque::class, $dq);
        $this->assertSame(5, $dq->getMaxlen());
    }

    public function test_py_frozenset_helper(): void
    {
        $fs = py_frozenset([1, 2, 3]);
        $this->assertInstanceOf(PyFrozenSet::class, $fs);
    }

    public function test_py_path_helper(): void
    {
        $p = py_path('/tmp/test.txt');
        $this->assertInstanceOf(PyPath::class, $p);
        $this->assertSame('test.txt', $p->name);
    }

    public function test_py_class_constructors(): void
    {
        $this->assertInstanceOf(PyCounter::class, Py::counter(['a']));
        $this->assertInstanceOf(PyDefaultDict::class, Py::defaultdict(fn() => 0));
        $this->assertInstanceOf(PyDeque::class, Py::deque([1]));
        $this->assertInstanceOf(PyFrozenSet::class, Py::frozenset([1]));
        $this->assertInstanceOf(PyPath::class, Py::path('/tmp'));
        $this->assertInstanceOf(PyTuple::class, Py::tuple(1, 2, 3));
    }

    // ═══════════════════════════════════════════════════════════
    //  PyTuple
    // ═══════════════════════════════════════════════════════════

    public function test_tuple_creation(): void
    {
        $t = new PyTuple([1, 2, 3]);
        $this->assertInstanceOf(PyTuple::class, $t);
        $this->assertSame(3, count($t));
    }

    public function test_tuple_helper_function(): void
    {
        $t = py_tuple(1, 2, 3);
        $this->assertInstanceOf(PyTuple::class, $t);
        $this->assertSame([1, 2, 3], $t->toPhp());
    }

    public function test_tuple_py_class_constructor(): void
    {
        $t = Py::tuple(1, 2, 3);
        $this->assertInstanceOf(PyTuple::class, $t);
        $this->assertSame([1, 2, 3], $t->toPhp());
    }

    public function test_tuple_indexing(): void
    {
        $t = py_tuple(10, 20, 30, 40);
        $this->assertSame(10, $t[0]);
        $this->assertSame(40, $t[-1]);
        $this->assertSame(30, $t[-2]);
    }

    public function test_tuple_index_out_of_range(): void
    {
        $t = py_tuple(1, 2, 3);
        $this->expectException(\OutOfRangeException::class);
        $t[5];
    }

    public function test_tuple_immutable_set_throws(): void
    {
        $t = py_tuple(1, 2, 3);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not support item assignment');
        $t[0] = 99;
    }

    public function test_tuple_immutable_unset_throws(): void
    {
        $t = py_tuple(1, 2, 3);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not support item deletion');
        unset($t[0]);
    }

    public function test_tuple_slicing(): void
    {
        $t = py_tuple(0, 1, 2, 3, 4, 5);
        $this->assertSame([1, 2, 3], $t->slice(1, 4)->toPhp());
        $this->assertSame([0, 2, 4], $t->slice(0, null, 2)->toPhp());
        $this->assertSame([5, 4, 3, 2, 1, 0], $t->slice(null, null, -1)->toPhp());
    }

    public function test_tuple_string_slice_notation(): void
    {
        $t = py_tuple(0, 1, 2, 3, 4, 5);
        $this->assertSame([1, 2, 3], $t["1:4"]->toPhp());
        $this->assertSame([0, 2, 4], $t["::2"]->toPhp());
        $this->assertSame([5, 4, 3, 2, 1, 0], $t["::-1"]->toPhp());
    }

    public function test_tuple_slice_returns_tuple(): void
    {
        $t = py_tuple(1, 2, 3, 4);
        $sliced = $t->slice(1, 3);
        $this->assertInstanceOf(PyTuple::class, $sliced);
    }

    public function test_tuple_index_method(): void
    {
        $t = py_tuple(10, 20, 30, 20, 10);
        $this->assertSame(1, $t->index(20));
        $this->assertSame(3, $t->index(20, 2));
    }

    public function test_tuple_index_not_found(): void
    {
        $t = py_tuple(1, 2, 3);
        $this->expectException(\ValueError::class);
        $t->index(99);
    }

    public function test_tuple_countOf(): void
    {
        $t = py_tuple(1, 2, 3, 2, 1, 2);
        $this->assertSame(3, $t->countOf(2));
        $this->assertSame(0, $t->countOf(99));
    }

    public function test_tuple_contains(): void
    {
        $t = py_tuple(1, 2, 3);
        $this->assertTrue($t->contains(2));
        $this->assertFalse($t->contains(99));
        $this->assertTrue($t->in(3));
    }

    public function test_tuple_hash(): void
    {
        $t1 = py_tuple(1, 2, 3);
        $t2 = py_tuple(1, 2, 3);
        $t3 = py_tuple(3, 2, 1);
        $this->assertSame($t1->hash(), $t2->hash());
        $this->assertNotSame($t1->hash(), $t3->hash());
    }

    public function test_tuple_map(): void
    {
        $result = py_tuple(1, 2, 3)->map(fn($x) => $x * 10);
        $this->assertInstanceOf(PyTuple::class, $result);
        $this->assertSame([10, 20, 30], $result->toPhp());
    }

    public function test_tuple_filter(): void
    {
        $result = py_tuple(1, 2, 3, 4, 5)->filter(fn($x) => $x > 3);
        $this->assertInstanceOf(PyTuple::class, $result);
        $this->assertSame([4, 5], $result->toPhp());
    }

    public function test_tuple_reduce(): void
    {
        $sum = py_tuple(1, 2, 3, 4)->reduce(fn($a, $b) => $a + $b);
        $this->assertSame(10, $sum);
    }

    public function test_tuple_concat(): void
    {
        $t = py_tuple(1, 2)->concat(py_tuple(3, 4));
        $this->assertInstanceOf(PyTuple::class, $t);
        $this->assertSame([1, 2, 3, 4], $t->toPhp());
    }

    public function test_tuple_repeat(): void
    {
        $t = py_tuple(1, 2)->repeat(3);
        $this->assertSame([1, 2, 1, 2, 1, 2], $t->toPhp());
    }

    public function test_tuple_sorted(): void
    {
        $t = py_tuple(3, 1, 2)->sorted();
        $this->assertInstanceOf(PyTuple::class, $t);
        $this->assertSame([1, 2, 3], $t->toPhp());
    }

    public function test_tuple_sorted_reverse(): void
    {
        $t = py_tuple(3, 1, 2)->sorted(reverse: true);
        $this->assertSame([3, 2, 1], $t->toPhp());
    }

    public function test_tuple_sorted_key(): void
    {
        $t = py_tuple('bb', 'a', 'ccc')->sorted(key: fn($s) => strlen($s));
        $this->assertSame(['a', 'bb', 'ccc'], $t->toPhp());
    }

    public function test_tuple_reversed(): void
    {
        $t = py_tuple(1, 2, 3)->reversed();
        $this->assertInstanceOf(PyTuple::class, $t);
        $this->assertSame([3, 2, 1], $t->toPhp());
    }

    public function test_tuple_first_last(): void
    {
        $t = py_tuple(10, 20, 30);
        $this->assertSame(10, $t->first());
        $this->assertSame(30, $t->last());
        $this->assertNull((new PyTuple())->first());
        $this->assertSame('default', (new PyTuple())->last('default'));
    }

    public function test_tuple_to_list(): void
    {
        $list = py_tuple(1, 2, 3)->toList();
        $this->assertInstanceOf(PyList::class, $list);
        $this->assertSame([1, 2, 3], $list->toPhp());
    }

    public function test_tuple_to_set(): void
    {
        $set = py_tuple(1, 2, 2, 3)->toSet();
        $this->assertInstanceOf(PySet::class, $set);
        $this->assertSame(3, count($set));
    }

    public function test_tuple_repr(): void
    {
        $this->assertSame('(1, 2, 3)', (string)py_tuple(1, 2, 3));
        $this->assertSame("(42,)", (string)py_tuple(42));
        $this->assertSame("('hello', True, None)", (string)py_tuple('hello', true, null));
    }

    public function test_tuple_bool(): void
    {
        $this->assertTrue(py_tuple(1)->__bool());
        $this->assertFalse((new PyTuple())->__bool());
    }

    public function test_tuple_len(): void
    {
        $this->assertSame(3, py_tuple(1, 2, 3)->__len());
        $this->assertSame(0, (new PyTuple())->__len());
    }

    public function test_tuple_iteration(): void
    {
        $items = [];
        foreach (py_tuple(1, 2, 3) as $item) {
            $items[] = $item;
        }
        $this->assertSame([1, 2, 3], $items);
    }

    public function test_tuple_json_serialize(): void
    {
        $this->assertSame('[1,2,3]', json_encode(py_tuple(1, 2, 3)));
    }

    public function test_tuple_eq(): void
    {
        $t1 = py_tuple(1, 2, 3);
        $t2 = py_tuple(1, 2, 3);
        $t3 = py_tuple(3, 2, 1);
        $this->assertTrue($t1->__eq($t2));
        $this->assertFalse($t1->__eq($t3));
    }

    public function test_tuple_add(): void
    {
        $t = py_tuple(1, 2)->__add(py_tuple(3, 4));
        $this->assertSame([1, 2, 3, 4], $t->toPhp());
    }

    public function test_tuple_mul(): void
    {
        $t = py_tuple(1, 2)->__mul(2);
        $this->assertSame([1, 2, 1, 2], $t->toPhp());
    }

    public function test_tuple_join(): void
    {
        $result = py_tuple('a', 'b', 'c')->join(', ');
        $this->assertSame('a, b, c', $result->toPhp());
    }

    public function test_tuple_enumerate(): void
    {
        $result = py_tuple('a', 'b', 'c')->enumerate();
        $this->assertInstanceOf(PyList::class, $result);
        $this->assertSame([0, 'a'], $result[0]->toPhp());
        $this->assertSame([1, 'b'], $result[1]->toPhp());
    }

    public function test_tuple_copy(): void
    {
        $t1 = py_tuple(1, 2, 3);
        $t2 = $t1->copy();
        $this->assertSame($t1->toPhp(), $t2->toPhp());
        $this->assertNotSame($t1, $t2);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyJson
    // ═══════════════════════════════════════════════════════════

    public function test_json_loads_object(): void
    {
        $result = PyJson::loads('{"name": "Alice", "age": 30}');
        $this->assertInstanceOf(PyDict::class, $result);
        $this->assertInstanceOf(PyString::class, $result['name']);
        $this->assertSame('Alice', $result['name']->toPhp());
        $this->assertSame(30, $result['age']);
    }

    public function test_json_loads_array(): void
    {
        $result = PyJson::loads('[1, 2, 3]');
        $this->assertInstanceOf(PyList::class, $result);
        $this->assertSame([1, 2, 3], $result->toPhp());
    }

    public function test_json_loads_string(): void
    {
        $result = PyJson::loads('"hello"');
        $this->assertInstanceOf(PyString::class, $result);
        $this->assertSame('hello', $result->toPhp());
    }

    public function test_json_loads_number(): void
    {
        $this->assertSame(42, PyJson::loads('42'));
        $this->assertSame(3.14, PyJson::loads('3.14'));
    }

    public function test_json_loads_bool_null(): void
    {
        $this->assertTrue(PyJson::loads('true'));
        $this->assertFalse(PyJson::loads('false'));
        $this->assertNull(PyJson::loads('null'));
    }

    public function test_json_loads_nested(): void
    {
        $json = '{"users": [{"name": "Bob", "tags": ["admin", "user"]}]}';
        $data = PyJson::loads($json);
        $this->assertInstanceOf(PyDict::class, $data);
        $this->assertInstanceOf(PyList::class, $data['users']);
        $this->assertInstanceOf(PyDict::class, $data['users'][0]);
        $this->assertInstanceOf(PyString::class, $data['users'][0]['name']);
        $this->assertSame('Bob', $data['users'][0]['name']->toPhp());
        $this->assertInstanceOf(PyList::class, $data['users'][0]['tags']);
        $this->assertInstanceOf(PyString::class, $data['users'][0]['tags'][0]);
        $this->assertSame('admin', $data['users'][0]['tags'][0]->toPhp());
    }

    public function test_json_loads_deeply_nested(): void
    {
        $json = '{"a": {"b": {"c": [1, "deep", true]}}}';
        $data = PyJson::loads($json);
        $this->assertInstanceOf(PyDict::class, $data['a']);
        $this->assertInstanceOf(PyDict::class, $data['a']['b']);
        $this->assertInstanceOf(PyList::class, $data['a']['b']['c']);
        $this->assertSame(1, $data['a']['b']['c'][0]);
        $this->assertInstanceOf(PyString::class, $data['a']['b']['c'][1]);
        $this->assertTrue($data['a']['b']['c'][2]);
    }

    public function test_json_loads_no_wrap(): void
    {
        $result = PyJson::loads('{"a": 1}', wrap: false);
        $this->assertIsArray($result);
        $this->assertSame(['a' => 1], $result);
    }

    public function test_json_loads_invalid_throws(): void
    {
        $this->expectException(\JsonException::class);
        PyJson::loads('{invalid json}');
    }

    public function test_json_loads_empty_object(): void
    {
        // json_decode with associative:true returns [] for {}
        $result = PyJson::loads('{}');
        $this->assertInstanceOf(PyList::class, $result);
    }

    public function test_json_loads_empty_array(): void
    {
        $result = PyJson::loads('[]');
        $this->assertInstanceOf(PyList::class, $result);
        $this->assertSame(0, count($result));
    }

    public function test_json_loads_pystring_methods_work(): void
    {
        $data = PyJson::loads('{"greeting": "hello world"}');
        // The value is a PyString, so we can call string methods on it
        $this->assertSame('HELLO WORLD', $data['greeting']->upper()->toPhp());
        $this->assertTrue($data['greeting']->startswith('hello'));
    }

    public function test_json_loads_pylist_methods_work(): void
    {
        $data = PyJson::loads('{"scores": [10, 20, 30]}');
        $this->assertSame(60, $data['scores']->sum());
        $this->assertSame([30, 20, 10], $data['scores']->sorted(reverse: true)->toPhp());
    }

    public function test_json_dumps_pydict(): void
    {
        $dict = py(["name" => "Alice", "age" => 30]);
        $json = PyJson::dumps($dict);
        $this->assertSame('{"name":"Alice","age":30}', $json);
    }

    public function test_json_dumps_pylist(): void
    {
        $list = py([1, 2, 3]);
        $json = PyJson::dumps($list);
        $this->assertSame('[1,2,3]', $json);
    }

    public function test_json_dumps_pytuple(): void
    {
        $tuple = py_tuple(1, 2, 3);
        $json = PyJson::dumps($tuple);
        $this->assertSame('[1,2,3]', $json);
    }

    public function test_json_dumps_pystring(): void
    {
        $str = py("hello");
        $json = PyJson::dumps($str);
        $this->assertSame('"hello"', $json);
    }

    public function test_json_dumps_nested_pythonic(): void
    {
        $data = py(["users" => py([
            py(["name" => "Bob", "active" => true]),
        ])]);
        $json = PyJson::dumps($data);
        $decoded = json_decode($json, true);
        $this->assertSame('Bob', $decoded['users'][0]['name']);
        $this->assertTrue($decoded['users'][0]['active']);
    }

    public function test_json_dumps_indent(): void
    {
        $dict = py(["a" => 1]);
        $json = PyJson::dumps($dict, indent: 2);
        $this->assertStringContainsString("\n", $json);
        $this->assertStringContainsString('  "a"', $json);
    }

    public function test_json_dumps_sort_keys(): void
    {
        $dict = py(["b" => 2, "a" => 1]);
        $json = PyJson::dumps($dict, sort_keys: true);
        $this->assertSame('{"a":1,"b":2}', $json);
    }

    public function test_json_dumps_plain_php_array(): void
    {
        $json = PyJson::dumps(["x" => 1, "y" => 2]);
        $decoded = json_decode($json, true);
        $this->assertSame(['x' => 1, 'y' => 2], $decoded);
    }

    public function test_json_dumps_plain_list_array(): void
    {
        $json = PyJson::dumps([1, 2, 3]);
        $this->assertSame('[1,2,3]', $json);
    }

    public function test_json_dumps_scalar(): void
    {
        $this->assertSame('42', PyJson::dumps(42));
        $this->assertSame('"hello"', PyJson::dumps("hello"));
        $this->assertSame('true', PyJson::dumps(true));
        $this->assertSame('null', PyJson::dumps(null));
    }

    public function test_json_roundtrip(): void
    {
        $original = '{"name":"Alice","scores":[95,87,92],"active":true}';
        $data = PyJson::loads($original);
        $encoded = PyJson::dumps($data, sort_keys: true);
        $this->assertSame('{"active":true,"name":"Alice","scores":[95,87,92]}', $encoded);
    }

    public function test_json_roundtrip_nested(): void
    {
        $original = '{"users":[{"name":"Bob","age":25},{"name":"Carol","age":30}]}';
        $data = PyJson::loads($original);
        $encoded = PyJson::dumps($data);
        $this->assertSame($original, $encoded);
    }

    public function test_json_dumps_pyset(): void
    {
        $set = py_set([1, 2, 3]);
        $json = PyJson::dumps($set);
        $decoded = json_decode($json, true);
        sort($decoded);
        $this->assertSame([1, 2, 3], $decoded);
    }

    public function test_json_dumps_pyfrozenset(): void
    {
        $fs = py_frozenset([1, 2, 3]);
        $json = PyJson::dumps($fs);
        $decoded = json_decode($json, true);
        sort($decoded);
        $this->assertSame([1, 2, 3], $decoded);
    }

    public function test_json_helper_functions(): void
    {
        $data = py_json_loads('{"x": 1}');
        $this->assertInstanceOf(PyDict::class, $data);
        $this->assertSame(1, $data['x']);

        $json = py_json_dumps($data);
        $this->assertSame('{"x":1}', $json);
    }

    public function test_json_py_class_methods(): void
    {
        $data = Py::json_loads('{"x": 1}');
        $this->assertInstanceOf(PyDict::class, $data);

        $json = Py::json_dumps($data);
        $this->assertSame('{"x":1}', $json);
    }

    public function test_json_dump_and_load_file(): void
    {
        $tmpFile = __DIR__ . '/.pyjson_test_' . uniqid() . '.json';
        try {
            $data = py(["name" => "Alice", "scores" => py([95, 87])]);
            PyJson::dump($data, $tmpFile, indent: 2);

            $loaded = PyJson::load($tmpFile);
            $this->assertInstanceOf(PyDict::class, $loaded);
            $this->assertSame('Alice', $loaded['name']->toPhp());
            $this->assertInstanceOf(PyList::class, $loaded['scores']);
            $this->assertSame(95, $loaded['scores'][0]);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function test_json_load_file_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('FileNotFoundError');
        PyJson::load('/nonexistent/file.json');
    }

    public function test_json_loads_array_of_strings(): void
    {
        $result = PyJson::loads('["hello", "world"]');
        $this->assertInstanceOf(PyList::class, $result);
        $this->assertInstanceOf(PyString::class, $result[0]);
        $this->assertSame('hello', $result[0]->toPhp());
    }

    public function test_json_dumps_unicode(): void
    {
        $json = PyJson::dumps(py(["emoji" => "🐍"]));
        $this->assertStringContainsString('🐍', $json);
    }

    public function test_json_dumps_ensure_ascii(): void
    {
        $json = PyJson::dumps(py(["emoji" => "🐍"]), ensure_ascii: true);
        $this->assertStringNotContainsString('🐍', $json);
        // Should contain escaped unicode
        $decoded = json_decode($json, true);
        $this->assertSame('🐍', $decoded['emoji']);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyOrderedDict
    // ═══════════════════════════════════════════════════════════

    public function test_ordereddict_basic(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertSame(3, count($od));
        $this->assertSame(1, $od['a']);
        $this->assertSame(2, $od['b']);
        $this->assertSame(['a', 'b', 'c'], $od->keys()->toPhp());
        $this->assertSame([1, 2, 3], $od->values()->toPhp());
    }

    public function test_ordereddict_insertion_order(): void
    {
        $od = new PyOrderedDict();
        $od['banana'] = 3;
        $od['apple'] = 4;
        $od['cherry'] = 1;
        $this->assertSame(['banana', 'apple', 'cherry'], $od->keys()->toPhp());
    }

    public function test_ordereddict_move_to_end(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->move_to_end('a');
        $this->assertSame(['b', 'c', 'a'], $od->keys()->toPhp());

        $od->move_to_end('a', last: false);
        $this->assertSame(['a', 'b', 'c'], $od->keys()->toPhp());
    }

    public function test_ordereddict_popitem(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $last = $od->popitem();
        $this->assertSame(['c', 3], $last);
        $this->assertSame(2, count($od));

        $first = $od->popitem(last: false);
        $this->assertSame(['a', 1], $first);
    }

    public function test_ordereddict_reversed(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $rev = $od->reversed();
        $this->assertSame(['c', 'b', 'a'], $rev->keys()->toPhp());
        $this->assertSame([3, 2, 1], $rev->values()->toPhp());
    }

    public function test_ordereddict_order_sensitive_eq(): void
    {
        $a = new PyOrderedDict(['x' => 1, 'y' => 2]);
        $b = new PyOrderedDict(['y' => 2, 'x' => 1]);
        $c = new PyOrderedDict(['x' => 1, 'y' => 2]);
        $this->assertFalse($a->__eq($b));
        $this->assertTrue($a->__eq($c));
    }

    public function test_ordereddict_fromkeys(): void
    {
        $od = PyOrderedDict::fromkeys(['a', 'b', 'c'], 0);
        $this->assertSame(['a', 'b', 'c'], $od->keys()->toPhp());
        $this->assertSame([0, 0, 0], $od->values()->toPhp());
    }

    public function test_ordereddict_repr(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2]);
        $repr = (string)$od;
        $this->assertStringContainsString('OrderedDict', $repr);
        $this->assertStringContainsString("'a'", $repr);
    }

    public function test_ordereddict_update(): void
    {
        $od = new PyOrderedDict(['a' => 1]);
        $od->update(['b' => 2, 'c' => 3]);
        $this->assertSame(['a', 'b', 'c'], $od->keys()->toPhp());
    }

    public function test_ordereddict_pop(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $val = $od->pop('b');
        $this->assertSame(2, $val);
        $this->assertSame(['a', 'c'], $od->keys()->toPhp());
    }

    public function test_ordereddict_helper(): void
    {
        $od = py_ordereddict(['x' => 10]);
        $this->assertInstanceOf(PyOrderedDict::class, $od);
        $this->assertSame(10, $od['x']);
    }

    public function test_ordereddict_items(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2]);
        $items = $od->items();
        $this->assertInstanceOf(PyList::class, $items);
        $this->assertSame(2, count($items));
    }

    public function test_ordereddict_index_of(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertSame(0, $od->index_of('a'));
        $this->assertSame(1, $od->index_of('b'));
        $this->assertSame(2, $od->index_of('c'));
    }

    public function test_ordereddict_index_of_missing_throws(): void
    {
        $od = new PyOrderedDict(['a' => 1]);
        $this->expectException(\OutOfRangeException::class);
        $od->index_of('z');
    }

    public function test_ordereddict_key_at(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertSame('a', $od->key_at(0));
        $this->assertSame('c', $od->key_at(2));
        $this->assertSame('c', $od->key_at(-1));
        $this->assertSame('a', $od->key_at(-3));
    }

    public function test_ordereddict_key_at_out_of_range_throws(): void
    {
        $od = new PyOrderedDict(['a' => 1]);
        $this->expectException(\OutOfRangeException::class);
        $od->key_at(5);
    }

    public function test_ordereddict_item_at(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->assertSame(['a', 1], $od->item_at(0));
        $this->assertSame(['c', 3], $od->item_at(-1));
    }

    public function test_ordereddict_insert_at(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->insert_at(1, 'x', 99);
        $this->assertSame(['a', 'x', 'b', 'c'], $od->keys()->toPhp());
        $this->assertSame([1, 99, 2, 3], $od->values()->toPhp());
    }

    public function test_ordereddict_insert_at_beginning(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2]);
        $od->insert_at(0, 'z', 0);
        $this->assertSame(['z', 'a', 'b'], $od->keys()->toPhp());
    }

    public function test_ordereddict_insert_at_end(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2]);
        $od->insert_at(99, 'z', 0);
        $this->assertSame(['a', 'b', 'z'], $od->keys()->toPhp());
    }

    public function test_ordereddict_insert_at_negative(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->insert_at(-1, 'x', 99);
        $this->assertSame(['a', 'b', 'x', 'c'], $od->keys()->toPhp());
    }

    public function test_ordereddict_insert_at_existing_key_moves(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->insert_at(0, 'c', 99);
        $this->assertSame(['c', 'a', 'b'], $od->keys()->toPhp());
        $this->assertSame(99, $od['c']);
    }

    public function test_ordereddict_insert_before(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->insert_before('b', 'x', 99);
        $this->assertSame(['a', 'x', 'b', 'c'], $od->keys()->toPhp());
    }

    public function test_ordereddict_insert_after(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->insert_after('a', 'x', 99);
        $this->assertSame(['a', 'x', 'b', 'c'], $od->keys()->toPhp());
    }

    public function test_ordereddict_insert_after_last(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2]);
        $od->insert_after('b', 'z', 0);
        $this->assertSame(['a', 'b', 'z'], $od->keys()->toPhp());
    }

    public function test_ordereddict_move_to(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->move_to('c', 0);
        $this->assertSame(['c', 'a', 'b'], $od->keys()->toPhp());
        $this->assertSame([3, 1, 2], $od->values()->toPhp());
    }

    public function test_ordereddict_move_to_same_position(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->move_to('b', 1);
        $this->assertSame(['a', 'b', 'c'], $od->keys()->toPhp());
    }

    public function test_ordereddict_move_to_missing_throws(): void
    {
        $od = new PyOrderedDict(['a' => 1]);
        $this->expectException(\OutOfRangeException::class);
        $od->move_to('z', 0);
    }

    public function test_ordereddict_move_before(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->move_before('c', 'a');
        $this->assertSame(['c', 'a', 'b'], $od->keys()->toPhp());
    }

    public function test_ordereddict_move_after(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->move_after('a', 'c');
        $this->assertSame(['b', 'c', 'a'], $od->keys()->toPhp());
    }

    public function test_ordereddict_swap(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->swap('a', 'c');
        $this->assertSame(['c', 'b', 'a'], $od->keys()->toPhp());
        $this->assertSame([3, 2, 1], $od->values()->toPhp());
    }

    public function test_ordereddict_swap_same_key(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2]);
        $od->swap('a', 'a');
        $this->assertSame(['a', 'b'], $od->keys()->toPhp());
    }

    public function test_ordereddict_swap_missing_throws(): void
    {
        $od = new PyOrderedDict(['a' => 1]);
        $this->expectException(\OutOfRangeException::class);
        $od->swap('a', 'z');
    }

    public function test_ordereddict_reorder(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2, 'c' => 3]);
        $od->reorder(['c', 'a', 'b']);
        $this->assertSame(['c', 'a', 'b'], $od->keys()->toPhp());
        $this->assertSame([3, 1, 2], $od->values()->toPhp());
    }

    public function test_ordereddict_reorder_mismatch_throws(): void
    {
        $od = new PyOrderedDict(['a' => 1, 'b' => 2]);
        $this->expectException(\InvalidArgumentException::class);
        $od->reorder(['a', 'x']);
    }

    // ═══════════════════════════════════════════════════════════
    //  Functools
    // ═══════════════════════════════════════════════════════════

    public function test_functools_partial(): void
    {
        $add = fn($a, $b) => $a + $b;
        $add5 = Functools::partial($add, 5);
        $this->assertSame(8, $add5(3));
        $this->assertSame(15, $add5(10));
    }

    public function test_functools_partial_multiple_args(): void
    {
        $fn = fn($a, $b, $c) => "{$a}-{$b}-{$c}";
        $partial = Functools::partial($fn, 'x', 'y');
        $this->assertSame('x-y-z', $partial('z'));
    }

    public function test_functools_reduce(): void
    {
        $sum = Functools::reduce(fn($a, $b) => $a + $b, [1, 2, 3, 4]);
        $this->assertSame(10, $sum);
    }

    public function test_functools_reduce_with_initial(): void
    {
        $product = Functools::reduce(fn($a, $b) => $a * $b, [1, 2, 3], 10);
        $this->assertSame(60, $product);
    }

    public function test_functools_reduce_single_element(): void
    {
        $result = Functools::reduce(fn($a, $b) => $a + $b, [42]);
        $this->assertSame(42, $result);
    }

    public function test_functools_reduce_empty_throws(): void
    {
        $this->expectException(ValueError::class);
        Functools::reduce(fn($a, $b) => $a + $b, []);
    }

    public function test_functools_lru_cache(): void
    {
        $callCount = 0;
        $fn = Functools::lru_cache(function (int $x) use (&$callCount) {
            $callCount++;
            return $x * $x;
        }, maxsize: 4);

        $this->assertSame(4, $fn(2));
        $this->assertSame(4, $fn(2)); // cached
        $this->assertSame(1, $callCount);

        $this->assertSame(9, $fn(3));
        $this->assertSame(2, $callCount);

        $info = Functools::cache_info($fn);
        $this->assertSame(1, $info['hits']);
        $this->assertSame(2, $info['misses']);
        $this->assertSame(4, $info['maxsize']);
        $this->assertSame(2, $info['currsize']);
    }

    public function test_functools_cache_clear(): void
    {
        $count = 0;
        $fn = Functools::lru_cache(function ($x) use (&$count) {
            $count++;
            return $x;
        });
        $fn(1);
        $fn(1);
        $this->assertSame(1, $count);

        Functools::cache_clear($fn);
        $fn(1);
        $this->assertSame(2, $count);
    }

    public function test_functools_cache_unbounded(): void
    {
        $fn = Functools::cache(fn($x) => $x * 2);
        $this->assertSame(10, $fn(5));
        $this->assertSame(10, $fn(5));
    }

    public function test_functools_cmp_to_key(): void
    {
        $cmp = fn($a, $b) => $b - $a; // reverse order
        $comparator = Functools::cmp_to_key($cmp);
        $arr = [3, 1, 2];
        usort($arr, $comparator);
        $this->assertSame([3, 2, 1], $arr);
    }

    public function test_functools_compose(): void
    {
        $double = fn($x) => $x * 2;
        $addOne = fn($x) => $x + 1;
        $transform = Functools::compose($double, $addOne);
        $this->assertSame(11, $transform(5)); // (5*2)+1 = 11
    }

    public function test_functools_identity(): void
    {
        $this->assertSame(42, Functools::identity(42));
        $this->assertSame('hello', Functools::identity('hello'));
    }

    public function test_functools_helper_partial(): void
    {
        $mul = fn($a, $b) => $a * $b;
        $double = py_partial($mul, 2);
        $this->assertSame(10, $double(5));
    }

    public function test_functools_helper_reduce(): void
    {
        $this->assertSame(10, py_reduce(fn($a, $b) => $a + $b, [1, 2, 3, 4]));
    }

    public function test_functools_helper_lru_cache(): void
    {
        $calls = 0;
        $fn = py_lru_cache(function (int $n) use (&$calls): int {
            $calls++;
            return $n * 2;
        });
        $this->assertSame(10, $fn(5));
        $this->assertSame(10, $fn(5)); // cached
        $this->assertSame(1, $calls);
    }

    public function test_functools_helper_cache(): void
    {
        $calls = 0;
        $fn = py_cache(function (int $n) use (&$calls): int {
            $calls++;
            return $n + 1;
        });
        $this->assertSame(6, $fn(5));
        $this->assertSame(6, $fn(5)); // cached
        $this->assertSame(1, $calls);
    }

    public function test_functools_helper_cmp_to_key(): void
    {
        $cmp = fn($a, $b) => $a - $b;
        $keyFn = py_cmp_to_key($cmp);
        $this->assertInstanceOf(\Closure::class, $keyFn);
    }

    public function test_functools_helper_compose(): void
    {
        $double = fn($x) => $x * 2;
        $inc = fn($x) => $x + 1;
        $fn = py_compose($double, $inc); // double first, then inc
        $this->assertSame(11, $fn(5)); // (5*2)+1 = 11
    }

    public function test_functools_wraps_basic(): void
    {
        $greet = fn(string $name): string => "Hello, {$name}";
        $wrapper = function (string $name) use ($greet): string {
            return strtoupper($greet($name));
        };
        $wrapped = Functools::wraps($wrapper, $greet);
        $this->assertSame($wrapper, $wrapped); // returns same closure
    }

    public function test_functools_wraps_metadata(): void
    {
        $original = fn(int $x): int => $x * 2;
        $wrapper = function (int $x) use ($original): int {
            return $original($x) + 1;
        };
        Functools::wraps($wrapper, $original, ['doc' => 'doubles x']);

        $meta = Functools::wrapped($wrapper);
        $this->assertNotNull($meta);
        $this->assertSame($original, $meta->wrapped);
        $this->assertIsString($meta->name);
        $this->assertSame('doubles x', $meta->doc);
    }

    public function test_functools_wrapped_returns_null_for_unknown(): void
    {
        $fn = fn() => 1;
        $this->assertNull(Functools::wrapped($fn));
    }

    public function test_functools_wraps_callable_name_string(): void
    {
        $wrapper = function () { return 1; };
        Functools::wraps($wrapper, 'strtoupper');
        $meta = Functools::wrapped($wrapper);
        $this->assertSame('strtoupper', $meta->name);
    }

    public function test_functools_wraps_callable_name_array(): void
    {
        $wrapper = function () { return 1; };
        Functools::wraps($wrapper, [PyDateTime::class, 'now']);
        $meta = Functools::wrapped($wrapper);
        $this->assertStringContainsString('PyDateTime', $meta->name);
        $this->assertStringContainsString('now', $meta->name);
    }

    // ═══════════════════════════════════════════════════════════
    //  PyCsv
    // ═══════════════════════════════════════════════════════════

    public function test_csv_reader_from_string(): void
    {
        $csv = "Alice,30\nBob,25";
        $rows = PyCsv::reader_from_string($csv);
        $this->assertInstanceOf(PyList::class, $rows);
        $this->assertSame(2, count($rows));
        $this->assertInstanceOf(PyList::class, $rows[0]);
        $this->assertSame('Alice', $rows[0][0]->toPhp());
        $this->assertSame('30', $rows[0][1]->toPhp());
    }

    public function test_csv_dictreader_from_string(): void
    {
        $csv = "name,age\nAlice,30\nBob,25";
        $rows = PyCsv::DictReader_from_string($csv);
        $this->assertInstanceOf(PyList::class, $rows);
        $this->assertSame(2, count($rows));
        $this->assertInstanceOf(PyDict::class, $rows[0]);
        $this->assertSame('Alice', $rows[0]['name']->toPhp());
        $this->assertSame('30', $rows[0]['age']->toPhp());
    }

    public function test_csv_dictreader_custom_fieldnames(): void
    {
        $csv = "Alice,30\nBob,25";
        $rows = PyCsv::DictReader_from_string($csv, fieldnames: ['name', 'age']);
        $this->assertSame(2, count($rows));
        $this->assertSame('Alice', $rows[0]['name']->toPhp());
    }

    public function test_csv_writer_and_reader_roundtrip(): void
    {
        $tmpFile = __DIR__ . '/test_csv_roundtrip_' . uniqid() . '.csv';
        try {
            $data = [['Name', 'Age'], ['Alice', '30'], ['Bob', '25']];
            PyCsv::writer($tmpFile, $data);
            $this->assertFileExists($tmpFile);

            $rows = PyCsv::reader($tmpFile);
            $this->assertSame(3, count($rows));
            $this->assertSame('Alice', $rows[1][0]->toPhp());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function test_csv_dictwriter_and_dictreader_roundtrip(): void
    {
        $tmpFile = __DIR__ . '/test_csv_dict_roundtrip_' . uniqid() . '.csv';
        try {
            PyCsv::DictWriter($tmpFile, ['name', 'age'], [
                ['name' => 'Alice', 'age' => '30'],
                ['name' => 'Bob', 'age' => '25'],
            ]);
            $this->assertFileExists($tmpFile);

            $rows = PyCsv::DictReader($tmpFile);
            $this->assertSame(2, count($rows));
            $this->assertSame('Alice', $rows[0]['name']->toPhp());
            $this->assertSame('25', $rows[1]['age']->toPhp());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function test_csv_writer_to_string(): void
    {
        $csvStr = PyCsv::writer_to_string([['a', 'b'], ['1', '2']]);
        $this->assertInstanceOf(PyString::class, $csvStr);
        $this->assertStringContainsString('a,b', $csvStr->toPhp());
        $this->assertStringContainsString('1,2', $csvStr->toPhp());
    }

    public function test_csv_dictwriter_to_string(): void
    {
        $csvStr = PyCsv::DictWriter_to_string(['x', 'y'], [['x' => '1', 'y' => '2']]);
        $this->assertInstanceOf(PyString::class, $csvStr);
        $this->assertStringContainsString('x,y', $csvStr->toPhp());
        $this->assertStringContainsString('1,2', $csvStr->toPhp());
    }

    public function test_csv_reader_returns_pystring_values(): void
    {
        $csv = "hello,world";
        $rows = PyCsv::reader_from_string($csv);
        $this->assertInstanceOf(\QXS\pythonic\PyString::class, $rows[0][0]);
    }

    public function test_csv_reader_file_not_found(): void
    {
        $this->expectException(\RuntimeException::class);
        PyCsv::reader('/nonexistent/path/file.csv');
    }

    public function test_csv_tab_delimiter(): void
    {
        $csv = "name\tage\nAlice\t30";
        $rows = PyCsv::DictReader_from_string($csv, delimiter: "\t");
        $this->assertSame('Alice', $rows[0]['name']->toPhp());
    }

    public function test_csv_helper_functions(): void
    {
        $tmpFile = __DIR__ . '/test_csv_helper_' . uniqid() . '.csv';
        try {
            PyCsv::writer($tmpFile, [['a', 'b'], ['1', '2']]);
            $rows = py_csv_reader($tmpFile);
            $this->assertSame(2, count($rows));

            $dictRows = py_csv_dictreader($tmpFile);
            $this->assertSame(1, count($dictRows));
        } finally {
            @unlink($tmpFile);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  Operator
    // ═══════════════════════════════════════════════════════════

    public function test_operator_itemgetter_single(): void
    {
        $getName = Operator::itemgetter('name');
        $this->assertSame('Alice', $getName(['name' => 'Alice', 'age' => 30]));
    }

    public function test_operator_itemgetter_multiple(): void
    {
        $getMulti = Operator::itemgetter('name', 'age');
        $result = $getMulti(['name' => 'Alice', 'age' => 30]);
        $this->assertInstanceOf(PyTuple::class, $result);
        $this->assertSame(['Alice', 30], $result->toPhp());
    }

    public function test_operator_itemgetter_with_arrayaccess(): void
    {
        $dict = new PyDict(['x' => 10, 'y' => 20]);
        $getX = Operator::itemgetter('x');
        $this->assertSame(10, $getX($dict));
    }

    public function test_operator_attrgetter_single(): void
    {
        $obj = new \stdClass();
        $obj->name = 'Bob';
        $getName = Operator::attrgetter('name');
        $this->assertSame('Bob', $getName($obj));
    }

    public function test_operator_attrgetter_multiple(): void
    {
        $obj = new \stdClass();
        $obj->x = 1;
        $obj->y = 2;
        $getXY = Operator::attrgetter('x', 'y');
        $result = $getXY($obj);
        $this->assertInstanceOf(PyTuple::class, $result);
        $this->assertSame([1, 2], $result->toPhp());
    }

    public function test_operator_attrgetter_dotted(): void
    {
        $inner = new \stdClass();
        $inner->value = 42;
        $outer = new \stdClass();
        $outer->inner = $inner;
        $getDeep = Operator::attrgetter('inner.value');
        $this->assertSame(42, $getDeep($outer));
    }

    public function test_operator_methodcaller(): void
    {
        $upper = Operator::methodcaller('upper');
        $result = $upper(new PyString('hello'));
        $this->assertSame('HELLO', $result->toPhp());
    }

    public function test_operator_methodcaller_with_args(): void
    {
        $replacer = Operator::methodcaller('replace', 'o', '0');
        $result = $replacer(new PyString('hello world'));
        $this->assertSame('hell0 w0rld', $result->toPhp());
    }

    public function test_operator_arithmetic(): void
    {
        $this->assertSame(5, Operator::add()(2, 3));
        $this->assertSame(-1, Operator::sub()(2, 3));
        $this->assertSame(6, Operator::mul()(2, 3));
        $this->assertSame(2.5, Operator::truediv()(5, 2));
        $this->assertSame(2, Operator::floordiv()(5, 2));
        $this->assertSame(1, Operator::mod()(5, 2));
        $this->assertSame(8, Operator::pow()(2, 3));
        $this->assertSame(-5, Operator::neg()(5));
        $this->assertSame(5, Operator::abs()(-5));
    }

    public function test_operator_comparison(): void
    {
        $this->assertTrue(Operator::lt()(1, 2));
        $this->assertFalse(Operator::lt()(2, 1));
        $this->assertTrue(Operator::le()(2, 2));
        $this->assertTrue(Operator::eq()(3, 3));
        $this->assertTrue(Operator::ne()(3, 4));
        $this->assertTrue(Operator::ge()(5, 5));
        $this->assertTrue(Operator::gt()(6, 5));
    }

    public function test_operator_logical(): void
    {
        $this->assertFalse(Operator::not_()(true));
        $this->assertTrue(Operator::truth()(1));
        $this->assertFalse(Operator::truth()(0));
    }

    public function test_operator_contains(): void
    {
        $fn = Operator::contains();
        $this->assertTrue($fn([1, 2, 3], 2));
        $this->assertFalse($fn([1, 2, 3], 5));
        $this->assertTrue($fn(new PyList([10, 20]), 10));
        $this->assertTrue($fn('hello world', 'world'));
    }

    public function test_operator_concat(): void
    {
        $fn = Operator::concat();
        $result = $fn(new PyString('hello '), 'world');
        $this->assertSame('hello world', $result->toPhp());
    }

    public function test_operator_itemgetter_as_sort_key(): void
    {
        $data = py([
            ['name' => 'Charlie', 'age' => 35],
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
        ]);
        $sorted = $data->sorted(key: Operator::itemgetter('name'));
        $this->assertSame('Alice', $sorted[0]['name']);
        $this->assertSame('Bob', $sorted[1]['name']);
        $this->assertSame('Charlie', $sorted[2]['name']);
    }

    public function test_operator_helper_itemgetter(): void
    {
        $fn = py_itemgetter('x');
        $this->assertSame(10, $fn(['x' => 10]));
    }

    public function test_operator_helper_attrgetter(): void
    {
        $obj = new \stdClass();
        $obj->val = 99;
        $fn = py_attrgetter('val');
        $this->assertSame(99, $fn($obj));
    }

    public function test_operator_helper_methodcaller(): void
    {
        $s = new PyString('hello world');
        $fn = py_methodcaller('upper');
        $result = $fn($s);
        $this->assertSame('HELLO WORLD', $result->toPhp());
    }

    // ═══════════════════════════════════════════════════════════
    //  PyDateTime & PyTimeDelta
    // ═══════════════════════════════════════════════════════════

    public function test_datetime_now(): void
    {
        $dt = PyDateTime::now();
        $this->assertInstanceOf(PyDateTime::class, $dt);
        $this->assertSame((int)date('Y'), $dt->year);
    }

    public function test_datetime_from_string(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:00');
        $this->assertSame(2024, $dt->year);
        $this->assertSame(6, $dt->month);
        $this->assertSame(15, $dt->day);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(30, $dt->minute);
        $this->assertSame(0, $dt->second);
    }

    public function test_datetime_fromtimestamp(): void
    {
        $ts = mktime(10, 30, 0, 6, 15, 2024);
        $dt = PyDateTime::fromtimestamp($ts);
        $this->assertSame(2024, $dt->year);
        $this->assertSame(6, $dt->month);
        $this->assertSame(15, $dt->day);
    }

    public function test_datetime_strptime(): void
    {
        $dt = PyDateTime::strptime('2024-06-15', '%Y-%m-%d');
        $this->assertSame(2024, $dt->year);
        $this->assertSame(6, $dt->month);
        $this->assertSame(15, $dt->day);
    }

    public function test_datetime_strftime(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:00');
        $formatted = $dt->strftime('%Y-%m-%d %H:%M:%S');
        $this->assertInstanceOf(PyString::class, $formatted);
        $this->assertSame('2024-06-15 10:30:00', $formatted->toPhp());
    }

    public function test_datetime_isoformat(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:00');
        $iso = $dt->isoformat();
        $this->assertInstanceOf(PyString::class, $iso);
        $this->assertSame('2024-06-15T10:30:00', $iso->toPhp());
    }

    public function test_datetime_date_and_time(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:45');
        $this->assertSame('2024-06-15', $dt->date()->toPhp());
        $this->assertSame('10:30:45', $dt->time()->toPhp());
    }

    public function test_datetime_weekday(): void
    {
        $dt = new PyDateTime('2024-06-15'); // Saturday
        $this->assertSame(5, $dt->weekday()); // Python: 0=Mon, 5=Sat
        $this->assertSame(6, $dt->isoweekday()); // ISO: 6=Sat
    }

    public function test_datetime_isocalendar(): void
    {
        $dt = new PyDateTime('2024-06-15');
        $iso = $dt->isocalendar();
        $this->assertInstanceOf(PyTuple::class, $iso);
        $this->assertSame(2024, $iso[0]);
    }

    public function test_datetime_timestamp(): void
    {
        $dt = new PyDateTime('2024-06-15 00:00:00');
        $ts = $dt->timestamp();
        $this->assertIsFloat($ts);
    }

    public function test_datetime_add_timedelta(): void
    {
        $dt = new PyDateTime('2024-06-15 10:00:00');
        $delta = new PyTimeDelta(days: 5, hours: 3);
        $future = $dt->add($delta);
        $this->assertSame(20, $future->day);
        $this->assertSame(13, $future->hour);
    }

    public function test_datetime_sub_timedelta(): void
    {
        $dt = new PyDateTime('2024-06-15 10:00:00');
        $delta = new PyTimeDelta(days: 5);
        $past = $dt->sub($delta);
        $this->assertSame(10, $past->day);
    }

    public function test_datetime_diff(): void
    {
        $dt1 = new PyDateTime('2024-06-20 10:00:00');
        $dt2 = new PyDateTime('2024-06-15 10:00:00');
        $diff = $dt1->diff($dt2);
        $this->assertInstanceOf(PyTimeDelta::class, $diff);
        $this->assertSame(5, $diff->getDays());
    }

    public function test_datetime_comparison(): void
    {
        $a = new PyDateTime('2024-06-15');
        $b = new PyDateTime('2024-06-20');
        $this->assertTrue($a->__lt($b));
        $this->assertFalse($a->__gt($b));
        $this->assertTrue($a->__le($a));
        $this->assertTrue($b->__ge($b));
    }

    public function test_datetime_replace(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:00');
        $replaced = $dt->replace(year: 2025, month: 1);
        $this->assertSame(2025, $replaced->year);
        $this->assertSame(1, $replaced->month);
        $this->assertSame(15, $replaced->day);
    }

    public function test_datetime_repr(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:00');
        $repr = $dt->__repr();
        $this->assertStringContainsString('datetime.datetime(2024, 6, 15, 10, 30, 0)', $repr);
    }

    public function test_datetime_combine(): void
    {
        $dt = PyDateTime::combine('2024-06-15', '10:30:00');
        $this->assertSame(2024, $dt->year);
        $this->assertSame(10, $dt->hour);
    }

    public function test_datetime_json(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:00');
        $json = json_encode($dt);
        $this->assertStringContainsString('2024-06-15', $json);
    }

    public function test_datetime_tostring(): void
    {
        $dt = new PyDateTime('2024-06-15 10:30:00');
        $str = (string)$dt;
        $this->assertStringContainsString('2024-06-15', $str);
    }

    public function test_datetime_helper(): void
    {
        $dt = py_datetime('2024-01-01');
        $this->assertInstanceOf(PyDateTime::class, $dt);
        $this->assertSame(2024, $dt->year);
    }

    // ─── PyTimeDelta ─────────────────────────────────────────

    public function test_timedelta_basic(): void
    {
        $d = new PyTimeDelta(days: 5, hours: 3, minutes: 30);
        $this->assertSame(5, $d->getDays());
        $expected = 3 * 3600 + 30 * 60;
        $this->assertSame($expected, $d->getSeconds());
    }

    public function test_timedelta_total_seconds(): void
    {
        $d = new PyTimeDelta(days: 1);
        $this->assertSame(86400.0, $d->total_seconds());
    }

    public function test_timedelta_weeks(): void
    {
        $d = new PyTimeDelta(weeks: 2);
        $this->assertSame(14, $d->getDays());
    }

    public function test_timedelta_add(): void
    {
        $a = new PyTimeDelta(days: 3);
        $b = new PyTimeDelta(days: 5);
        $c = $a->add($b);
        $this->assertSame(8, $c->getDays());
    }

    public function test_timedelta_sub(): void
    {
        $a = new PyTimeDelta(days: 10);
        $b = new PyTimeDelta(days: 3);
        $c = $a->sub($b);
        $this->assertSame(7, $c->getDays());
    }

    public function test_timedelta_mul(): void
    {
        $d = new PyTimeDelta(days: 3);
        $result = $d->mul(4);
        $this->assertSame(12, $result->getDays());
    }

    public function test_timedelta_neg(): void
    {
        $d = new PyTimeDelta(days: 5);
        $neg = $d->neg();
        $this->assertSame(-5, $neg->getDays());
    }

    public function test_timedelta_abs(): void
    {
        $d = new PyTimeDelta(days: -5);
        $abs = $d->abs();
        // Depending on normalization, days should be positive
        $this->assertGreaterThanOrEqual(0, $abs->total_seconds());
    }

    public function test_timedelta_repr(): void
    {
        $d = new PyTimeDelta(days: 5, seconds: 3600);
        $repr = $d->__repr();
        $this->assertStringContainsString('timedelta', $repr);
        $this->assertStringContainsString('days=5', $repr);
    }

    public function test_timedelta_tostring(): void
    {
        $d = new PyTimeDelta(days: 2, hours: 3, minutes: 30);
        $str = (string)$d;
        $this->assertStringContainsString('2 days', $str);
        $this->assertStringContainsString('3:30:00', $str);
    }

    public function test_timedelta_bool(): void
    {
        $zero = new PyTimeDelta();
        $nonzero = new PyTimeDelta(seconds: 1);
        $this->assertFalse($zero->__bool());
        $this->assertTrue($nonzero->__bool());
    }

    public function test_timedelta_helper(): void
    {
        $d = py_timedelta(days: 3, hours: 2);
        $this->assertInstanceOf(PyTimeDelta::class, $d);
        $this->assertSame(3, $d->getDays());
    }

    public function test_timedelta_eq(): void
    {
        $a = new PyTimeDelta(days: 3);
        $b = new PyTimeDelta(days: 3);
        $c = new PyTimeDelta(days: 5);
        $this->assertTrue($a->__eq($b));
        $this->assertFalse($a->__eq($c));
    }

    public function test_timedelta_lt(): void
    {
        $small = new PyTimeDelta(days: 1);
        $big   = new PyTimeDelta(days: 5);
        $this->assertTrue($small->__lt($big));
        $this->assertFalse($big->__lt($small));
        $this->assertFalse($small->__lt($small));
    }

    public function test_timedelta_le(): void
    {
        $a = new PyTimeDelta(days: 3);
        $b = new PyTimeDelta(days: 3);
        $c = new PyTimeDelta(days: 5);
        $this->assertTrue($a->__le($b));
        $this->assertTrue($a->__le($c));
        $this->assertFalse($c->__le($a));
    }

    public function test_timedelta_gt(): void
    {
        $small = new PyTimeDelta(hours: 1);
        $big   = new PyTimeDelta(hours: 5);
        $this->assertTrue($big->__gt($small));
        $this->assertFalse($small->__gt($big));
    }

    public function test_timedelta_ge(): void
    {
        $a = new PyTimeDelta(seconds: 100);
        $b = new PyTimeDelta(seconds: 100);
        $c = new PyTimeDelta(seconds: 200);
        $this->assertTrue($a->__ge($b));
        $this->assertTrue($c->__ge($a));
        $this->assertFalse($a->__ge($c));
    }

    public function test_timedelta_attribute_access_days(): void
    {
        $d = new PyTimeDelta(days: 7, hours: 2);
        $this->assertSame(7, $d->days);
        $this->assertSame(2 * 3600, $d->seconds);
        $this->assertSame(0, $d->microseconds);
    }

    public function test_timedelta_attribute_access_total_seconds(): void
    {
        $d = new PyTimeDelta(days: 1);
        $this->assertSame(86400.0, $d->total_seconds);
    }

    public function test_timedelta_attribute_isset(): void
    {
        $d = new PyTimeDelta(days: 1);
        $this->assertTrue(isset($d->days));
        $this->assertTrue(isset($d->seconds));
        $this->assertTrue(isset($d->microseconds));
        $this->assertFalse(isset($d->nonexistent));
    }

    public function test_timedelta_attribute_unknown_throws(): void
    {
        $this->expectException(\QXS\pythonic\AttributeError::class);
        $d = new PyTimeDelta(days: 1);
        $d->foobar;
    }

    // ═══════════════════════════════════════════════════════════
    //  Heapq
    // ═══════════════════════════════════════════════════════════

    public function test_heapq_push_pop(): void
    {
        $heap = new PyList();
        Heapq::heappush($heap, 5);
        Heapq::heappush($heap, 1);
        Heapq::heappush($heap, 3);
        $this->assertSame(1, Heapq::heappop($heap));
        $this->assertSame(3, Heapq::heappop($heap));
        $this->assertSame(5, Heapq::heappop($heap));
    }

    public function test_heapq_pop_empty(): void
    {
        $this->expectException(\UnderflowException::class);
        Heapq::heappop(new PyList());
    }

    public function test_heapq_heapify(): void
    {
        $data = new PyList([3, 1, 4, 1, 5, 9, 2, 6]);
        Heapq::heapify($data);
        // After heapify, first element should be smallest
        $this->assertSame(1, $data[0]);
        // Popping should give sorted order
        $sorted = [];
        while (count($data) > 0) {
            $sorted[] = Heapq::heappop($data);
        }
        $this->assertSame([1, 1, 2, 3, 4, 5, 6, 9], $sorted);
    }

    public function test_heapq_heapreplace(): void
    {
        $heap = new PyList([1, 3, 5]);
        Heapq::heapify($heap);
        $old = Heapq::heapreplace($heap, 10);
        $this->assertSame(1, $old);
        $this->assertSame(3, count($heap));
    }

    public function test_heapq_heappushpop(): void
    {
        $heap = new PyList([2, 4, 6]);
        Heapq::heapify($heap);
        // Push 1 then pop — should return 1 (smaller than heap min)
        $result = Heapq::heappushpop($heap, 1);
        $this->assertSame(1, $result);
        $this->assertSame(3, count($heap));

        // Push 10 then pop — should return heap min (2)
        $result = Heapq::heappushpop($heap, 10);
        $this->assertSame(2, $result);
    }

    public function test_heapq_nlargest(): void
    {
        $result = Heapq::nlargest(3, [5, 1, 8, 3, 9, 2]);
        $this->assertInstanceOf(PyList::class, $result);
        $this->assertSame([9, 8, 5], $result->toPhp());
    }

    public function test_heapq_nsmallest(): void
    {
        $result = Heapq::nsmallest(3, [5, 1, 8, 3, 9, 2]);
        $this->assertInstanceOf(PyList::class, $result);
        $this->assertSame([1, 2, 3], $result->toPhp());
    }

    public function test_heapq_nlargest_with_key(): void
    {
        $data = [['n' => 'a', 'v' => 3], ['n' => 'b', 'v' => 1], ['n' => 'c', 'v' => 5]];
        $result = Heapq::nlargest(2, $data, key: fn($x) => $x['v']);
        $this->assertSame(2, count($result));
        $this->assertSame(5, $result[0]['v']);
        $this->assertSame(3, $result[1]['v']);
    }

    public function test_heapq_nsmallest_with_key(): void
    {
        $data = [['n' => 'a', 'v' => 3], ['n' => 'b', 'v' => 1], ['n' => 'c', 'v' => 5]];
        $result = Heapq::nsmallest(2, $data, key: fn($x) => $x['v']);
        $this->assertSame(2, count($result));
        $this->assertSame(1, $result[0]['v']);
        $this->assertSame(3, $result[1]['v']);
    }

    public function test_heapq_merge(): void
    {
        $result = Heapq::merge([1, 3, 5], [2, 4, 6]);
        $this->assertInstanceOf(PyList::class, $result);
        $this->assertSame([1, 2, 3, 4, 5, 6], $result->toPhp());
    }

    public function test_heapq_merge_multiple(): void
    {
        $result = Heapq::merge([1, 4], [2, 5], [3, 6]);
        $this->assertSame([1, 2, 3, 4, 5, 6], $result->toPhp());
    }

    public function test_heapq_with_pylist(): void
    {
        $heap = new PyList();
        Heapq::heappush($heap, 10);
        Heapq::heappush($heap, 5);
        Heapq::heappush($heap, 8);
        Heapq::heappush($heap, 1);
        $this->assertSame(1, Heapq::heappop($heap));
        $this->assertSame(5, Heapq::heappop($heap));
    }

    public function test_heapq_nlargest_from_pylist(): void
    {
        $data = new PyList([10, 3, 7, 1, 8, 5]);
        $result = Heapq::nlargest(3, $data);
        $this->assertSame([10, 8, 7], $result->toPhp());
    }

    public function test_heapq_helper_push_pop(): void
    {
        $heap = new PyList();
        py_heappush($heap, 5);
        py_heappush($heap, 2);
        py_heappush($heap, 8);
        $this->assertSame(2, py_heappop($heap));
        $this->assertSame(5, py_heappop($heap));
    }

    public function test_heapq_helper_heapify(): void
    {
        $data = new PyList([5, 3, 8, 1, 2]);
        py_heapify($data);
        $this->assertSame(1, $data[0]); // min at root
    }

    public function test_heapq_helper_nlargest_nsmallest(): void
    {
        $data = [10, 3, 7, 1, 8, 5];
        $largest = py_nlargest(3, $data);
        $this->assertSame([10, 8, 7], $largest->toPhp());
        $smallest = py_nsmallest(3, $data);
        $this->assertSame([1, 3, 5], $smallest->toPhp());
    }

    public function test_heapq_helper_merge(): void
    {
        $result = py_heapmerge([1, 4], [2, 5], [3, 6]);
        $this->assertSame([1, 2, 3, 4, 5, 6], $result->toPhp());
    }

    // ═══════════════════════════════════════════════════════════
    //  Bisect
    // ═══════════════════════════════════════════════════════════

    public function test_bisect_left_basic(): void
    {
        $a = [1, 3, 5, 5, 7, 9];
        $this->assertSame(2, Bisect::bisect_left($a, 5));
        $this->assertSame(0, Bisect::bisect_left($a, 0));
        $this->assertSame(6, Bisect::bisect_left($a, 10));
    }

    public function test_bisect_right_basic(): void
    {
        $a = [1, 3, 5, 5, 7, 9];
        $this->assertSame(4, Bisect::bisect_right($a, 5));
        $this->assertSame(0, Bisect::bisect_right($a, 0));
        $this->assertSame(6, Bisect::bisect_right($a, 10));
    }

    public function test_bisect_alias(): void
    {
        $a = [1, 3, 5, 7];
        $this->assertSame(Bisect::bisect_right($a, 5), Bisect::bisect($a, 5));
    }

    public function test_bisect_left_with_pylist(): void
    {
        $list = new PyList([10, 20, 30, 40]);
        $this->assertSame(2, Bisect::bisect_left($list, 30));
    }

    public function test_bisect_left_lo_hi(): void
    {
        $a = [1, 3, 5, 7, 9];
        // search only in slice [1..3) → elements [3, 5]
        $this->assertSame(1, Bisect::bisect_left($a, 3, lo: 1, hi: 3));
        $this->assertSame(2, Bisect::bisect_left($a, 5, lo: 1, hi: 3));
    }

    public function test_bisect_left_with_key(): void
    {
        $items = [['v' => 10], ['v' => 20], ['v' => 30]];
        $key   = fn($x) => $x['v'];
        $this->assertSame(1, Bisect::bisect_left($items, ['v' => 20], key: $key));
        $this->assertSame(2, Bisect::bisect_left($items, ['v' => 25], key: $key));
    }

    public function test_bisect_right_with_key(): void
    {
        $items = [['v' => 10], ['v' => 20], ['v' => 20], ['v' => 30]];
        $key   = fn($x) => $x['v'];
        $this->assertSame(3, Bisect::bisect_right($items, ['v' => 20], key: $key));
    }

    public function test_bisect_left_negative_lo_throws(): void
    {
        $this->expectException(\ValueError::class);
        Bisect::bisect_left([1, 2, 3], 2, lo: -1);
    }

    public function test_bisect_empty(): void
    {
        $this->assertSame(0, Bisect::bisect_left([], 5));
        $this->assertSame(0, Bisect::bisect_right([], 5));
    }

    public function test_insort_right_array(): void
    {
        $a = [1, 3, 5, 7];
        Bisect::insort_right($a, 4);
        $this->assertSame([1, 3, 4, 5, 7], $a);
    }

    public function test_insort_left_array(): void
    {
        $a = [1, 3, 5, 5, 7];
        Bisect::insort_left($a, 5);
        // Should insert BEFORE existing 5s (index 2)
        $this->assertSame([1, 3, 5, 5, 5, 7], $a);
        $this->assertSame(5, $a[2]);
    }

    public function test_insort_alias(): void
    {
        $a = [1, 3, 7];
        $b = $a;
        Bisect::insort($a, 5);
        Bisect::insort_right($b, 5);
        $this->assertSame($b, $a);
    }

    public function test_insort_pylist(): void
    {
        $list = new PyList([10, 20, 30, 40]);
        Bisect::insort($list, 25);
        $this->assertSame([10, 20, 25, 30, 40], $list->toPhp());
    }

    public function test_insort_with_key(): void
    {
        $items = [['v' => 10], ['v' => 30], ['v' => 50]];
        Bisect::insort($items, ['v' => 20], key: fn($x) => $x['v']);
        $this->assertSame(20, $items[1]['v']);
        $this->assertCount(4, $items);
    }

    public function test_bisect_index(): void
    {
        $a = [1, 3, 5, 7, 9];
        $this->assertSame(2, Bisect::index($a, 5));
        $this->assertSame(0, Bisect::index($a, 1));
        $this->assertSame(-1, Bisect::index($a, 4));
        $this->assertSame(-1, Bisect::index($a, 10));
    }

    public function test_bisect_count(): void
    {
        $a = [1, 2, 2, 2, 3, 4, 4];
        $this->assertSame(3, Bisect::count($a, 2));
        $this->assertSame(2, Bisect::count($a, 4));
        $this->assertSame(0, Bisect::count($a, 5));
        $this->assertSame(1, Bisect::count($a, 1));
    }

    public function test_bisect_contains(): void
    {
        $a = [1, 3, 5, 7, 9];
        $this->assertTrue(Bisect::contains($a, 5));
        $this->assertFalse(Bisect::contains($a, 6));
        $this->assertTrue(Bisect::contains($a, 1));
        $this->assertTrue(Bisect::contains($a, 9));
    }

    public function test_bisect_index_with_key(): void
    {
        $items = [['k' => 'a'], ['k' => 'c'], ['k' => 'e']];
        $key = fn($x) => $x['k'];
        $this->assertSame(1, Bisect::index($items, ['k' => 'c'], key: $key));
        $this->assertSame(-1, Bisect::index($items, ['k' => 'b'], key: $key));
    }

    public function test_bisect_helper_functions(): void
    {
        $a = [1, 3, 5, 7];
        $this->assertSame(2, py_bisect_left($a, 5));
        $this->assertSame(3, py_bisect_right($a, 5));
        py_insort($a, 4);
        $this->assertSame([1, 3, 4, 5, 7], $a);
        $this->assertSame(1, py_bisect_index([1, 3, 5], 3));
        $this->assertTrue(py_bisect_contains([1, 3, 5], 3));
        $this->assertFalse(py_bisect_contains([1, 3, 5], 2));
        $b = [1, 3, 5];
        py_insort_left($b, 3);
        $this->assertSame([1, 3, 3, 5], $b);
        $c = [1, 3, 5];
        py_insort_right($c, 3);
        $this->assertSame([1, 3, 3, 5], $c);
    }

    // ═══════════════════════════════════════════════════════════
    //  Shutil
    // ═══════════════════════════════════════════════════════════

    private function shutil_temp_dir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phpusing_shutil_test_' . uniqid();
        mkdir($dir, 0777, true);
        return $dir;
    }

    private function shutil_cleanup(string $dir): void
    {
        if (is_dir($dir)) {
            Shutil::rmtree($dir);
        }
    }

    public function test_shutil_copyfile(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'hello');
            $result = Shutil::copyfile($tmp . '/a.txt', $tmp . '/b.txt');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'b.txt');
            $this->assertSame('hello', file_get_contents($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_copy_into_dir(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'data');
            mkdir($tmp . '/sub');
            $result = Shutil::copy($tmp . '/a.txt', $tmp . '/sub');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertStringContainsString('a.txt', (string)$result);
            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'a.txt');
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_copy2_preserves_mtime(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            $src = $tmp . DIRECTORY_SEPARATOR . 'a.txt';
            file_put_contents($src, 'data');
            touch($src, 1000000);
            clearstatcache();
            $result = Shutil::copy2($tmp . '/a.txt', $tmp . '/b.txt');
            $this->assertInstanceOf(PyPath::class, $result);
            clearstatcache();
            $this->assertSame(1000000, filemtime($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_copytree(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            mkdir($tmp . '/src/sub', 0777, true);
            file_put_contents($tmp . '/src/a.txt', 'aaa');
            file_put_contents($tmp . '/src/sub/b.txt', 'bbb');

            $result = Shutil::copytree($tmp . '/src', $tmp . '/dst');
            $this->assertInstanceOf(PyPath::class, $result);

            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . 'a.txt');
            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'b.txt');
            $this->assertSame('bbb', file_get_contents($tmp . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_copytree_dirs_exist_ok(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            mkdir($tmp . '/src');
            file_put_contents($tmp . '/src/a.txt', 'aaa');
            mkdir($tmp . '/dst');

            Shutil::copytree($tmp . '/src', $tmp . '/dst', dirs_exist_ok: true);
            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . 'a.txt');
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_copytree_existing_throws(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            mkdir($tmp . '/src');
            file_put_contents($tmp . '/src/a.txt', 'aaa');
            mkdir($tmp . '/dst');

            $this->expectException(\RuntimeException::class);
            Shutil::copytree($tmp . '/src', $tmp . '/dst');
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_copytree_with_ignore(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            mkdir($tmp . '/src');
            file_put_contents($tmp . '/src/keep.txt', 'keep');
            file_put_contents($tmp . '/src/skip.log', 'skip');
            file_put_contents($tmp . '/src/skip2.log', 'skip2');

            Shutil::copytree($tmp . '/src', $tmp . '/dst', ignore: Shutil::ignore_patterns('*.log'));

            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . 'keep.txt');
            $this->assertFileDoesNotExist($tmp . DIRECTORY_SEPARATOR . 'dst' . DIRECTORY_SEPARATOR . 'skip.log');
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_rmtree(): void
    {
        $tmp = $this->shutil_temp_dir();
        $target = $tmp . DIRECTORY_SEPARATOR . 'deep';
        mkdir($target . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b', 0777, true);
        file_put_contents($target . DIRECTORY_SEPARATOR . 'a' . DIRECTORY_SEPARATOR . 'b' . DIRECTORY_SEPARATOR . 'f.txt', 'x');

        Shutil::rmtree($target);
        $this->assertDirectoryDoesNotExist($target);

        // Cleanup parent
        rmdir($tmp);
    }

    public function test_shutil_rmtree_nonexistent_throws(): void
    {
        $this->expectException(\QXS\pythonic\FileNotFoundError::class);
        Shutil::rmtree('/nonexistent_dir_' . uniqid());
    }

    public function test_shutil_rmtree_ignore_errors(): void
    {
        // Should not throw
        Shutil::rmtree('/nonexistent_dir_' . uniqid(), ignore_errors: true);
        $this->assertTrue(true);
    }

    public function test_shutil_move_file(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'content');
            $result = Shutil::move($tmp . '/a.txt', $tmp . '/b.txt');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertFileDoesNotExist($tmp . DIRECTORY_SEPARATOR . 'a.txt');
            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'b.txt');
            $this->assertSame('content', file_get_contents($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_move_into_dir(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'stuff');
            mkdir($tmp . '/dest');
            $result = Shutil::move($tmp . '/a.txt', $tmp . '/dest');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'dest' . DIRECTORY_SEPARATOR . 'a.txt');
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_move_nonexistent_throws(): void
    {
        $this->expectException(\QXS\pythonic\FileNotFoundError::class);
        Shutil::move('/nonexistent_' . uniqid(), '/tmp/dst');
    }

    public function test_shutil_disk_usage(): void
    {
        $usage = Shutil::disk_usage(sys_get_temp_dir());
        $this->assertArrayHasKey('total', $usage);
        $this->assertArrayHasKey('used', $usage);
        $this->assertArrayHasKey('free', $usage);
        $this->assertGreaterThan(0, $usage['total']);
        $this->assertGreaterThan(0, $usage['free']);
        $this->assertSame($usage['total'], $usage['used'] + $usage['free']);
    }

    public function test_shutil_which(): void
    {
        // php should be findable in our Docker environment
        $result = Shutil::which('php');
        $this->assertInstanceOf(PyPath::class, $result);
        $this->assertStringContainsString('php', (string)$result);
    }

    public function test_shutil_which_not_found(): void
    {
        $result = Shutil::which('nonexistent_binary_' . uniqid());
        $this->assertNull($result);
    }

    public function test_shutil_ignore_patterns(): void
    {
        $ignore = Shutil::ignore_patterns('*.log', '*.tmp');
        $ignored = $ignore('/some/dir', ['a.txt', 'b.log', 'c.tmp', 'd.php']);
        $this->assertContains('b.log', $ignored);
        $this->assertContains('c.tmp', $ignored);
        $this->assertNotContains('a.txt', $ignored);
        $this->assertNotContains('d.php', $ignored);
    }

    public function test_shutil_copyfile_not_found_throws(): void
    {
        $this->expectException(\QXS\pythonic\FileNotFoundError::class);
        Shutil::copyfile('/nonexistent_' . uniqid(), '/tmp/dst.txt');
    }

    public function test_shutil_with_pypath(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            $src = new PyPath($tmp . '/a.txt');
            file_put_contents($tmp . DIRECTORY_SEPARATOR . 'a.txt', 'pypath test');
            $dst = new PyPath($tmp . '/b.txt');
            $result = Shutil::copyfile($src, $dst);
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertSame('pypath test', file_get_contents($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_helper_functions(): void
    {
        $result = py_which('php');
        $this->assertInstanceOf(PyPath::class, $result);
    }

    public function test_shutil_helper_rmtree(): void
    {
        $tmp = $this->shutil_temp_dir();
        mkdir($tmp . '/del');
        file_put_contents($tmp . '/del/f.txt', 'x');
        py_rmtree($tmp . '/del');
        $this->assertDirectoryDoesNotExist($tmp . DIRECTORY_SEPARATOR . 'del');
        rmdir($tmp);
    }

    public function test_shutil_helper_copytree(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            mkdir($tmp . '/s');
            file_put_contents($tmp . '/s/f.txt', 'hello');
            $result = py_copytree($tmp . '/s', $tmp . '/d');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertFileExists($tmp . DIRECTORY_SEPARATOR . 'd' . DIRECTORY_SEPARATOR . 'f.txt');
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_helper_copyfile(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'copy me');
            $result = py_copyfile($tmp . '/a.txt', $tmp . '/b.txt');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertSame('copy me', file_get_contents($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_helper_copy(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'perm copy');
            $result = py_copy($tmp . '/a.txt', $tmp . '/b.txt');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertSame('perm copy', file_get_contents($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_helper_copy2(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'meta copy');
            touch($tmp . DIRECTORY_SEPARATOR . 'a.txt', 1000000);
            clearstatcache();
            $result = py_copy2($tmp . '/a.txt', $tmp . '/b.txt');
            $this->assertInstanceOf(PyPath::class, $result);
            clearstatcache();
            $this->assertSame(1000000, filemtime($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_helper_move(): void
    {
        $tmp = $this->shutil_temp_dir();
        try {
            file_put_contents($tmp . '/a.txt', 'moveme');
            $result = py_move($tmp . '/a.txt', $tmp . '/b.txt');
            $this->assertInstanceOf(PyPath::class, $result);
            $this->assertFileDoesNotExist($tmp . DIRECTORY_SEPARATOR . 'a.txt');
            $this->assertSame('moveme', file_get_contents($tmp . DIRECTORY_SEPARATOR . 'b.txt'));
        } finally {
            $this->shutil_cleanup($tmp);
        }
    }

    public function test_shutil_helper_disk_usage(): void
    {
        $usage = py_disk_usage(sys_get_temp_dir());
        $this->assertArrayHasKey('total', $usage);
        $this->assertArrayHasKey('free', $usage);
        $this->assertGreaterThan(0, $usage['total']);
    }
}

// ─── Test DataClass ──────────────────────────────────────────

class TestUser extends PyDataClass
{
    public function __construct(
        public string $name,
        public int $age,
        public string $email = '',
    ) {
        parent::__construct();
    }
}
