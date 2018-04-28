<?php

namespace Fast\Tests\Redis\Connectors;

use Fast\Redis\Connectors\RedisConnector;
use Fast\Tests\Testing\InteractsWithRedis;
use PHPUnit\Framework\TestCase;

class RedisConnectorTest extends TestCase
{
    use InteractsWithRedis;

    /**
     * @var RedisConnector
     */
    private $connector = null;

    protected function setUp()
    {
        parent::setUp();
        $this->connector = new RedisConnector();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testConnect()
    {
        $result = $this->connector->connect($this->getConfigs(), $this->getOptions());
        $this->assertTrue($result instanceof \Redis);
    }

    public function testConnectToCluster()
    {
        $result = $this->connector->connectToCluster($this->getClusterConfigs(), $this->getClusterOptions(), $this->getOptions());
        $this->assertTrue($result instanceof \RedisCluster);
    }
}
