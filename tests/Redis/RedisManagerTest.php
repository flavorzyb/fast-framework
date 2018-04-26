<?php
namespace Fast\Tests\Redis;

use Fast\Redis\RedisManager;
use PHPUnit\Framework\TestCase;

class RedisManagerTest extends TestCase
{
    /**
     * @var RedisManager
     */
    private $manager = null;

    protected function setUp()
    {
        parent::setUp();
        $this->manager = new RedisManager($this->getConfigs());
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    private function getConfigs() {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;
        return [
            'default' => [
                'host' => $host,
                'port' => $port,
                'timeout' => 5,
            ],
            'clusters' => [
                'cluster' => [
                    [
                        'host' => '10.3.218.2',
                        'port' => 10004,
                        'timeout' => 5,
                    ],
                    [
                        'host' => '10.3.218.2',
                        'port' => 10005,
                        'timeout' => 5,
                    ],
                    [
                        'host' => '10.3.218.2',
                        'port' => 10006,
                        'timeout' => 5,
                    ]
                ]
            ],
        ];
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
