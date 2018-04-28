<?php
namespace Fast\Tests\Redis;

use Fast\Redis\RedisManager;
use Fast\Tests\Testing\InteractsWithRedis;
use PHPUnit\Framework\TestCase;

class RedisManagerTest extends TestCase
{
    use InteractsWithRedis;
    /**
     * @var RedisManager
     */
    private $manager = null;

    protected function setUp()
    {
        parent::setUp();
        $this->manager = new RedisManager($this->getRedisManagerConfigs());
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testConnection()
    {
        $result = $this->manager->connection('default');
        $this->assertTrue($result instanceof \Redis);
    }

    public function testConnections()
    {
        $this->manager->connection('default');
        $result = $this->manager->connections();
        $this->assertTrue($result['default'] instanceof \Redis);

        $result = $this->manager->connection('default');
        $this->assertTrue($result instanceof \Redis);
    }

    public function testClusters()
    {
        $this->manager->connection('cluster');
        $result = $this->manager->connections();
        $this->assertTrue($result['cluster'] instanceof \RedisCluster);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        $this->manager->connection('test');
    }
}
