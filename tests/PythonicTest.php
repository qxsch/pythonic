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
use QXS\pythonic\PyException;
use QXS\pythonic\ValueError;
use QXS\pythonic\KeyError;
use QXS\pythonic\IndexError;
use QXS\pythonic\AttributeError;
use QXS\pythonic\StopIteration;

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
