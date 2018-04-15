<?php

namespace Fast\Tests\Support;

use ArrayAccess;
use stdClass;
use Fast\Support\Collection;
use Mockery as m;
use Fast\Support\Arr;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDataGet()
    {
        $object = (object) ['users' => ['name' => ['Taylor', 'Otwell']]];
        $array = [(object) ['users' => [(object) ['name' => 'Taylor']]]];
        $dottedArray = ['users' => ['first.name' => 'Taylor', 'middle.name' => null]];
        $arrayAccess = new TestArrayAccess(['price' => 56, 'user' => new TestArrayAccess(['name' => 'John']), 'email' => null]);

        $this->assertEquals('Taylor', data_get($object, 'users.name.0'));
        $this->assertEquals('Taylor', data_get($array, '0.users.0.name'));
        $this->assertNull(data_get($array, '0.users.3'));
        $this->assertEquals('Not found', data_get($array, '0.users.3', 'Not found'));
        $this->assertEquals('Not found', data_get($array, '0.users.3', function () {
            return 'Not found';
        }));
        $this->assertEquals('Taylor', data_get($dottedArray, ['users', 'first.name']));
        $this->assertNull(data_get($dottedArray, ['users', 'middle.name']));
        $this->assertEquals('Not found', data_get($dottedArray, ['users', 'last.name'], 'Not found'));
        $this->assertEquals(56, data_get($arrayAccess, 'price'));
        $this->assertEquals('John', data_get($arrayAccess, 'user.name'));
        $this->assertEquals('void', data_get($arrayAccess, 'foo', 'void'));
        $this->assertEquals('void', data_get($arrayAccess, 'user.foo', 'void'));
        $this->assertNull(data_get($arrayAccess, 'foo'));
        $this->assertNull(data_get($arrayAccess, 'user.foo'));
        $this->assertNull(data_get($arrayAccess, 'email', 'Not found'));
    }

    public function testDataGetKeyIsNull()
    {
        $data = ['123' => 'test'];
        $this->assertEquals($data, data_get($data, null));
    }

    public function testDataGetTargetIsCollection() {
        $data = new Collection(['key' => 'test']);
        $this->assertEquals(['test'], data_get($data, ['*']));
    }

    public function testDataGetTargetIsObject() {
        $data = new \stdClass();
        $this->assertEquals('test', data_get($data, ['*'], 'test'));
    }

    public function testDataSet()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'boom'],
            data_set($data, 'baz', 'boom')
        );

        $this->assertEquals(
            ['foo' => 'bar', 'baz' => 'kaboom'],
            data_set($data, 'baz', 'kaboom')
        );

        $this->assertEquals(
            ['foo' => [], 'baz' => 'kaboom'],
            data_set($data, 'foo.*', 'noop')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => 'kaboom'],
            data_set($data, 'foo.bar', 'boom')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => ['bar' => 'boom']],
            data_set($data, 'baz.bar', 'boom')
        );

        $this->assertEquals(
            ['foo' => ['bar' => 'boom'], 'baz' => ['bar' => ['boom' => ['kaboom' => 'boom']]]],
            data_set($data, 'baz.bar.boom.kaboom', 'boom')
        );
    }

    public function testDataSetKeyIsArray()
    {
        $data = ['foo' => ['test' => 'bar'], 'baz' => ['test' => 'boom']];
        $result = ['foo' => ['test' => 'test1'], 'baz' => ['test' => 'test1']];
        $this->assertEquals($result, data_set($data, ['*', 'test'], 'test1'));

        $data = ['foo' => ['test' => 'bar'], 'baz' => ['test' => 'boom']];
        $result = ['foo' => ['test' => 'bar'], 'baz' => ['test' => 'boom'], 'key' => ['test2' => 'test1']];
        $this->assertEquals($result, data_set($data, ['key', 'test2'], 'test1'));
    }

    public function testDataSetTargetIsObject()
    {
        $data = new stdClass();
        $data->foo = ['test' => 'bar'];
        $result = new stdClass();
        $result->foo = ['test' => 'bar'];
        $result->bar = 'test1';
        $this->assertEquals($result, data_set($data, ['bar'], 'test1'));

        $data = new stdClass();
        $data->foo = ['test' => 'bar'];
        $result = new stdClass();
        $result->foo = ['test' => 'bar'];
        $result->key = ['bar' => 'test1'];
        $this->assertEquals($result, data_set($data, ['key', 'bar'], 'test1'));
    }

    public function testValue()
    {
        $this->assertEquals('foo', value('foo'));
        $this->assertEquals('foo', value(function () {
            return 'foo';
        }));
    }

    public function testArrayCollapse()
    {
        $array = [[1], [2], [3], ['foo', 'bar'], collect(['baz', 'boom'])];
        $this->assertEquals([1, 2, 3, 'foo', 'bar', 'baz', 'boom'], Arr::collapse($array));
    }
}

class TestArrayAccess implements ArrayAccess
{
    protected $attributes = [];

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->attributes);
    }

    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
}
