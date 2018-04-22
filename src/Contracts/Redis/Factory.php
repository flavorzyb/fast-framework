<?php

namespace Fast\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  string  $name
     * @return \Fast\Redis\Connections\Connection
     */
    public function connection($name = null);
}
