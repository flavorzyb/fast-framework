<?php

namespace Fast\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  string|null  $name
     * @return \Fast\Contracts\Cache\Repository
     */
    public function store($name = null);
}
