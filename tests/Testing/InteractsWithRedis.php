<?php

namespace Fast\Tests\Testing;

trait InteractsWithRedis
{
    protected function getConfigs()
    {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;

        return [
            'host'    => $host,
            'port'    => $port,
            'timeout' => 5,
        ];
    }

    protected function getOptions()
    {
        return [
            'password'     => '123456',
            'database'     => 1,
            'prefix'       => 'test',
            'persistent'   => true,
            'read_timeout' => 5,
        ];
    }

    protected function getClusterOptions()
    {
        return [
            'timeout'      => 5,
            'prefix'       => 'test',
            'persistent'   => true,
            'read_timeout' => 5,
        ];
    }

    protected function getClusterConfigs()
    {
        return [
            [
                'host'    => getenv('REDIS_CLUSTER_HOST_1') ?: '127.0.0.1',
                'port'    => getenv('REDIS_CLUSTER_PORT_1') ?: 10001,
                'timeout' => 5,
            ],
            [
                'host'    => getenv('REDIS_CLUSTER_HOST_2') ?: '127.0.0.1',
                'port'    => getenv('REDIS_CLUSTER_PORT_2') ?: 10002,
                'timeout' => 5,
            ],
            [
                'host'    => getenv('REDIS_CLUSTER_HOST_3') ?: '127.0.0.1',
                'port'    => getenv('REDIS_CLUSTER_PORT_3') ?: 10003,
                'timeout' => 5,
            ],
            [
                'host'    => getenv('REDIS_CLUSTER_HOST_3') ?: '127.0.0.1',
                'port'    => getenv('REDIS_CLUSTER_PORT_4') ?: 10004,
                'timeout' => 5,
            ],
            [
                'host'    => getenv('REDIS_CLUSTER_HOST_5') ?: '127.0.0.1',
                'port'    => getenv('REDIS_CLUSTER_PORT_5') ?: 10005,
                'timeout' => 5,
            ],
            [
                'host'    => getenv('REDIS_CLUSTER_HOST_6') ?: '127.0.0.1',
                'port'    => getenv('REDIS_CLUSTER_PORT_6') ?: 10006,
                'timeout' => 5,
            ],
        ];
    }

    protected function getRedisManagerConfigs() {
        return [
            'default' => $this->getConfigs(),
            'clusters' => [
                'cluster' => $this->getClusterConfigs(),
            ],
        ];
    }
}
