<?php

namespace Fast\Tests\Cache;

use DateTime;
use DateInterval;
use Mockery as m;
use DateTimeImmutable;
use Fast\Support\Carbon;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        Carbon::setTestNow();
    }

    public function testGetReturnsValueFromCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArray()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => 'bar', 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $repo->get(['foo', 'bar']));
    }

    public function testGetReturnsMultipleValuesFromCacheWhenGivenAnArrayWithDefaultValues()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->once()->with(['foo', 'bar'])->andReturn(['foo' => null, 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'default', 'bar' => 'baz'], $repo->get(['foo' => 'default', 'bar']));
    }

    public function testDefaultValueIsReturned()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->times(2)->andReturn(null);
        $this->assertEquals('bar', $repo->get('foo', 'bar'));
        $this->assertEquals('baz', $repo->get('boom', function () {
            return 'baz';
        }));
    }

    public function testSettingDefaultCacheTime()
    {
        $repo = $this->getRepository();
        $repo->setDefaultCacheTime(10);
        $this->assertEquals(10, $repo->getDefaultCacheTime());
    }

    public function testHasMethod()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->once()->with('bar')->andReturn('bar');

        $this->assertTrue($repo->has('bar'));
        $this->assertFalse($repo->has('foo'));
    }

    public function testOffsetMethods() {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('forget')->with('foo')->andReturn(true);
        $repo->getStore()->shouldReceive('put')->with('foo', 'bar', 60)->andReturn(true);

        $repo->offsetSet('foo', 'bar');
        $this->assertTrue($repo->offsetUnset('foo'));
        $this->assertFalse($repo->offsetExists('foo'));
        $this->assertEquals(null, $repo->offsetGet('foo'));
    }

    public function testRememberMethodCallsPutAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 10);
        $result = $repo->remember('foo', 10, function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);

        /*
         * Use Carbon object...
         */
        Carbon::setTestNow(Carbon::now());

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->times(2)->andReturn(null);
        $repo->getStore()->shouldReceive('put')->once()->with('foo', 'bar', 602 / 60);
        $repo->getStore()->shouldReceive('put')->once()->with('baz', 'qux', 598 / 60);
        $result = $repo->remember('foo', Carbon::now()->addMinutes(10)->addSeconds(2), function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);
        $result = $repo->remember('baz', Carbon::now()->addMinutes(10)->subSeconds(2), function () {
            return 'qux';
        });
        $this->assertEquals('qux', $result);
    }

    public function testRememberForeverMethodCallsForeverAndReturnsDefault()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->andReturn(null, null, 'test');
        $repo->getStore()->shouldReceive('forever')->with('foo', 'bar');
        $result = $repo->rememberForever('foo', function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);

        $result = $repo->sear('foo', function () {
            return 'bar';
        });
        $this->assertEquals('bar', $result);

        $result = $repo->rememberForever('foo', function () {
            return 'bar';
        });
        $this->assertEquals('test', $result);
    }

    public function testRememberGetValue()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->andReturn('test');
        $result = $repo->remember('foo', 100, function () {
            return 'bar';
        });
        $this->assertEquals('test', $result);
    }

    public function testPuttingMultipleItemsInCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1);
        $repo->put(['foo' => 'bar', 'bar' => 'baz'], 1);

        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testSettingMultipleItemsInCache()
    {
        // Alias of PuttingMultiple
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('putMany')->once()->with(['foo' => 'bar', 'bar' => 'baz'], 1);
        $repo->setMultiple(['foo' => 'bar', 'bar' => 'baz'], 1);

        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testPutWithDatetimeInPastOrZeroSecondsDoesntSaveItem()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->never();
        $repo->put('foo', 'bar', Carbon::now()->subMinutes(10));
        $repo->put('foo', 'bar', Carbon::now());

        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function dataProviderTestGetMinutes()
    {
        Carbon::setTestNow(Carbon::parse($this->getTestDate()));

        return [
            [Carbon::now()->addMinutes(5)],
            [(new DateTime($this->getTestDate()))->modify('+5 minutes')],
            [(new DateTimeImmutable($this->getTestDate()))->modify('+5 minutes')],
            [new DateInterval('PT5M')],
            [5],
        ];
    }

    /**
     * @dataProvider dataProviderTestGetMinutes
     * @param mixed $duration
     */
    public function testGetMinutes($duration)
    {
        Carbon::setTestNow(Carbon::parse($this->getTestDate()));

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->once()->with($key = 'foo', $value = 'bar', 5);
        $repo->put($key, $value, $duration);

        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testForgettingCacheKey()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $this->assertTrue($repo->forget('a-key'));
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testRemovingCacheKey()
    {
        // Alias of Forget
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $this->assertTrue($repo->delete('a-key'));
    }

    public function testSettingCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('put')->with($key = 'foo', $value = 'bar', 1);
        $repo->set($key, $value, 1);

        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testClearingWholeCache()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('flush')->andReturn(true);
        $this->assertTrue($repo->clear());
    }

    public function testGettingMultipleValuesFromCache()
    {
        $keys = ['key1', 'key2', 'key3'];
        $default = ['key2' => 5];

        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('many')->with(['key2', 'key1', 'key3'])->andReturn(['key1' => 1, 'key2' => null, 'key3' => null]);
        $this->assertEquals(['key1' => 1, 'key2' => 5, 'key3' => null], $repo->getMultiple($keys, $default));

        $repo->getStore()->shouldReceive('many')->with(['key1', 'key2', 'key3'])->andReturn(['key1' => 1, 'key2' => null, 'key3' => null]);
        $this->assertEquals(['key1' => 1, 'key2' => null, 'key3' => null], $repo->getMultiple($keys, null));
    }

    public function testPull() {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->with('foo')->andReturn(true);
        $repo->getStore()->shouldReceive('forget')->with('bar')->andReturn(true);
        $repo->getStore()->shouldReceive('get')->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->with('bar')->andReturn('test');
        $this->assertEquals('bar', $repo->pull('foo', 'bar'));
        $this->assertEquals('test', $repo->pull('bar', 'bar'));
    }

    public function testIncrementAndDecrement() {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('increment')->once()->with('foo', 1)->andReturn(2);
        $repo->getStore()->shouldReceive('decrement')->once()->with('bar', 1)->andReturn(3);
        $this->assertEquals(2, $repo->increment('foo'));
        $this->assertEquals(3, $repo->decrement('bar'));
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testRemovingMultipleKeys()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->getStore()->shouldReceive('forget')->once()->with('a-second-key')->andReturn(true);
        $this->assertEquals(true, $repo->deleteMultiple(['a-key', 'a-second-key']));
    }

    public function testClone() {
        $repo = $this->getRepository();
        $result = clone $repo;
        $this->assertEquals($repo->getStore(), $result->getStore());
    }

    protected function getRepository()
    {
        $repository = new \Fast\Cache\Repository(m::mock('\Fast\Contracts\Cache\Store'));
        return $repository;
    }

    protected function getTestDate()
    {
        return '2030-07-25 12:13:14 UTC';
    }
}
