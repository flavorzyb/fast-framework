<?php

namespace Fast\Tests\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class RedisStoreTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGetReturnsNullWhenNotFound()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(null);
        $this->assertNull($redis->get('foo'));
    }

    public function testRedisValueIsReturned()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(serialize('foo'));
        $this->assertEquals('foo', $redis->get('foo'));
    }

    public function testRedisMultipleValuesAreReturned()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('mget')->once()->with(['prefix:foo', 'prefix:fizz', 'prefix:norf', 'prefix:null'])
            ->andReturn([
                serialize('bar'),
                serialize('buzz'),
                serialize('quz'),
                null,
            ]);

        $results = $redis->many(['foo', 'fizz', 'norf', 'null']);

        $this->assertEquals('bar', $results['foo']);
        $this->assertEquals('buzz', $results['fizz']);
        $this->assertEquals('quz', $results['norf']);
        $this->assertNull($results['null']);
    }

    public function testRedisValueIsReturnedForNumerics()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(1);
        $this->assertEquals(1, $redis->get('foo'));
    }

    public function testSetMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60 * 60, serialize('foo'));
        $redis->put('foo', 'foo', 60);

        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(serialize('foo'));
        $this->assertEquals('foo', $redis->get('foo'));
    }

    public function testSetMultipleMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        /** @var m\MockInterface $connection */
        $connection = $redis->getRedis();
        $connection->shouldReceive('multi')->once();
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60 * 60, serialize('bar'));
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:baz', 60 * 60, serialize('qux'));
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:bar', 60 * 60, serialize('norf'));
        $connection->shouldReceive('exec')->once();

        $redis->putMany([
            'foo'   => 'bar',
            'baz'   => 'qux',
            'bar' => 'norf',
        ], 60);

        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(serialize('bar'));
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:baz')->andReturn(serialize('qux'));
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:bar')->andReturn(serialize('norf'));
        $this->assertEquals('bar', $redis->get('foo'));
        $this->assertEquals('qux', $redis->get('baz'));
        $this->assertEquals('norf', $redis->get('bar'));
    }

    public function testSetMethodProperlyCallsRedisForNumerics()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60 * 60, 1);
        $redis->put('foo', 1, 60);

        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(1);
        $this->assertEquals(1, $redis->get('foo'));
    }

    public function testIncrementMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('incrBy')->once()->with('prefix:foo', 5);
        $redis->increment('foo', 5);

        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(5);
        $this->assertEquals(5, $redis->get('foo'));
    }

    public function testDecrementMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('decrBy')->once()->with('prefix:foo', 5);
        $redis->decrement('foo', 5);

        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(5);
        $this->assertEquals(5, $redis->get('foo'));
    }

    public function testStoreItemForeverProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('set')->once()->with('prefix:foo', serialize('foo'));
        $redis->forever('foo', 'foo');

        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(serialize('foo'));
        $this->assertEquals('foo', $redis->get('foo'));
    }

    public function testForgetMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('del')->once()->with('prefix:foo');
        $redis->forget('foo');

        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(null);
        $this->assertEquals(null, $redis->get('foo'));
    }

    public function testFlushesCached()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('flushAll')->once()->andReturn('ok');
        $result = $redis->flush();
        $this->assertTrue($result);
    }

    public function testGetAndSetPrefix()
    {
        $redis = $this->getRedis();
        $this->assertEquals('prefix:', $redis->getPrefix());
        $redis->setPrefix('foo');
        $this->assertEquals('foo:', $redis->getPrefix());
        $redis->setPrefix(null);
        $this->assertEmpty($redis->getPrefix());
    }

    protected function getRedis()
    {
        return new \Fast\Cache\RedisStore(m::mock('\Redis'), 'prefix');
    }
}
