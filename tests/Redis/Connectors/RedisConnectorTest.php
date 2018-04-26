<?php
namespace Fast\Tests\Redis\Connectors;

use Fast\Redis\Connectors\RedisConnector;
use PHPUnit\Framework\TestCase;

class RedisConnectorTest extends TestCase
{
    /**
     * @var RedisConnector
     */
    private $connector = null;

    protected function setUp()
    {
        parent::setUp();
        $this->connector = new RedisConnector();
    }

    private function getConfigs() {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;
        return [
            'host' => $host,
            'port' => $port,
            'timeout' => 5000,
        ];
    }

    private function getOptions() {
        return [
            'password' => '123456',
            'database' => 1,
            'prefix' => 'test',
            'persistent' => true,
            'read_timeout' => 5000,
        ];
    }

    private function getClusterOptions() {
        return [
            'timeout' => 5000,
            'prefix' => 'test',
            'persistent' => true,
            'read_timeout' => 5000,
        ];
    }

    private function getClusterConfigs() {
        $host = getenv('REDIS_HOST') ?: '10.3.218.2';
        return [
            [
                'host' => $host,
                'port' => 10004,
                'timeout' => 5000,
            ],
            [
                'host' => $host,
                'port' => 10005,
                'timeout' => 5000,
            ],
            [
            'host' => $host,
            'port' => 10006,
            'timeout' => 5000,
            ]
        ];
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
