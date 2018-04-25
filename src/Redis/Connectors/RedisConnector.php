<?php
namespace Fast\Redis\Connectors;

use Redis;
use RedisCluster;
use Fast\Support\Arr;

class RedisConnector
{
    /**
     * Create a new Redis connection.
     *
     * @param  array  $config
     * @param  array  $options
     * @return \Redis
     */
    public function connect(array $config, array $options)
    {
        return $this->createClient(array_merge(
            $config, $options, Arr::pull($config, 'options', [])
        ));
    }

    /**
     * Create a new clustered connection.
     *
     * @param  array  $config
     * @param  array  $clusterOptions
     * @param  array  $options
     * @return \RedisCluster
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $options = array_merge($options, $clusterOptions, Arr::pull($config, 'options', []));

        return $this->createRedisClusterInstance(
            array_map([$this, 'buildClusterConnectionString'], $config), $options
        );
    }

    /**
     * Build a single cluster seed string from array.
     *
     * @param  array  $server
     * @return string
     */
    protected function buildClusterConnectionString(array $server)
    {
        return $server['host'].':'.$server['port'].'?'.http_build_query(Arr::only($server, [
                'database', 'password', 'prefix', 'read_timeout',
            ]));
    }

    /**
     * Create the Redis client instance.
     *
     * @param  array  $config
     * @return \Redis
     */
    protected function createClient(array $config)
    {
        $result = new Redis();
        $this->establishConnection($result, $config);

        if (! empty($config['password'])) {
            $result->auth($config['password']);
        }

        if (! empty($config['database'])) {
            $result->select($config['database']);
        }

        if (! empty($config['prefix'])) {
            $result->setOption(Redis::OPT_PREFIX, $config['prefix']);
        }

        if (! empty($config['read_timeout'])) {
            $result->setOption(Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
        }

        return $result;
    }

    /**
     * Establish a connection with the Redis host.
     *
     * @param  \Redis  $client
     * @param  array  $config
     * @return void
     */
    protected function establishConnection($client, array $config)
    {
        $client->{($config['persistent'] ?? false) === true ? 'pconnect' : 'connect'}(
            $config['host'], $config['port'], Arr::get($config, 'timeout', 0)
        );
    }

    /**
     * Create a new redis cluster instance.
     *
     * @param  array  $servers
     * @param  array  $options
     * @return \RedisCluster
     */
    protected function createRedisClusterInstance(array $servers, array $options)
    {
        return new RedisCluster(
            null,
            array_values($servers),
            $options['timeout'] ?? 0,
            $options['read_timeout'] ?? 0,
            isset($options['persistent']) && $options['persistent']
        );
    }
}
