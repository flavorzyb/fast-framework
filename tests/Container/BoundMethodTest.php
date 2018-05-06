<?php
namespace Fast\Tests\Container;

use Fast\Container\BoundMethod;
use Fast\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BoundMethodTest extends TestCase
{
    /**
     * @var \Fast\Container\Container
     */
    private $container = null;

    protected function setUp()
    {
        parent::setUp();
        $this->container = new Container();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testCallBoundMethodWithCallable()
    {
        $stub = new BoundMethodTestCallStub;
        $result = BoundMethod::call($this->container, [$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);

        $container = m::mock('\Fast\Container\Container');
        $container->shouldReceive('hasMethodBinding')->andReturn(true);
        $container->shouldReceive('callMethodBinding')->andReturn(['foo', 'bar2']);
        $result = BoundMethod::call($container, [$stub, 'work'], ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar2'], $result);
    }

    public function testCallBoundStaticMethodWithCallable()
    {
        $result = BoundMethod::call($this->container, '\Fast\Tests\Container\BoundMethodTestCallStub::staticWork', ['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallBoundMethodSyntaxWithCallable()
    {
        $result = BoundMethod::call($this->container, '\Fast\Tests\Container\BoundMethodTestCallStub@work', ['k' => 'foo', 'v' =>'bar']);
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function testCallBoundMethodDefaultValueWithCallable()
    {
        $result = BoundMethod::call($this->container, '\Fast\Tests\Container\BoundMethodTestCallStub@inject', []);
        $this->assertInstanceOf('\Fast\Tests\Container\BoundMethodTestConcreteStub', $result[0]);
        $this->assertEquals('taylor', $result[1]);

    }
}

class BoundMethodTestConcreteStub
{
}


class BoundMethodTestCallStub
{
    public function work($k, $v)
    {
        return [$k, $v];
    }

    public function inject(BoundMethodTestConcreteStub $stub, $default = 'taylor')
    {
        return func_get_args();
    }

    public function unresolvable($foo, $bar)
    {
        return func_get_args();
    }

    public static function staticWork() {
        return func_get_args();
    }
}
