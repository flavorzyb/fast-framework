<?php

namespace Fast\Cache;

use Closure;
use ArrayAccess;
use DateTimeInterface;
use Fast\Support\Carbon;
use Fast\Contracts\Cache\Store;
use Fast\Support\InteractsWithTime;
use Fast\Contracts\Cache\Repository as CacheContract;

class Repository implements CacheContract, ArrayAccess
{
    use InteractsWithTime;

    /**
     * The cache store implementation.
     *
     * @var \Fast\Contracts\Cache\Store
     */
    protected $store;

    /**
     * The default number of minutes to store items.
     *
     * @var float|int
     */
    protected $default = 60;

    /**
     * Create a new cache repository instance.
     *
     * @param  \Fast\Contracts\Cache\Store  $store
     * @return void
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->many($key);
        }

        $result = $this->store->get($key);

        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        if (is_null($result)) {
            $result = value($default);
        }

        return $result;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $values = $this->store->many(collect($keys)->map(function ($value, $key) {
            return is_string($key) ? $key : $value;
        })->values()->all());

        return collect($values)->map(function ($value, $key) use ($keys) {
            return $this->handleManyResult($keys, $key, $value);
        })->all();
    }

    /**
     * Handle a result for the "many" method.
     *
     * @param  array  $keys
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function handleManyResult($keys, $key, $value)
    {
        if (is_null($value)) {
            return isset($keys[$key]) ? value($keys[$key]) : null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if (is_null($default)) {
            return $this->many($keys);
        }

        foreach ($keys as $key) {
            if (! isset($default[$key])) {
                $default[$key] = null;
            }
        }

        return $this->many($default);
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $result = $this->get($key, $default);
        $this->forget($key);
        return $result;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTimeInterface|\DateInterval|float|int|null  $minutes
     * @return void
     */
    public function put($key, $value, $minutes = null)
    {
        if (is_array($key)) {
            $this->putMany($key, $value);
            return ;
        }

        if (! is_null($minutes = $this->getMinutes($minutes))) {
            $this->store->put($key, $value, $minutes);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->put($key, $value, $ttl);
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  \DateTimeInterface|\DateInterval|float|int  $minutes
     * @return void
     */
    public function putMany(array $values, $minutes)
    {
        if (! is_null($minutes = $this->getMinutes($minutes))) {
            $this->store->putMany($values, $minutes);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->putMany($values, $ttl);
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        return $this->store->increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        return $this->store->decrement($key, $value);
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->store->forever($key, $value);
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param  string  $key
     * @param  \DateTimeInterface|\DateInterval|float|int  $minutes
     * @param  \Closure  $callback
     * @return mixed
     */
    public function remember($key, $minutes, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        if (! is_null($value)) {
            return $value;
        }

        $this->put($key, $value = $callback(), $minutes);

        return $value;
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function sear($key, Closure $callback)
    {
        return $this->rememberForever($key, $callback);
    }

    /**
     * Get an item from the cache, or store the default value forever.
     *
     * @param  string   $key
     * @param  \Closure  $callback
     * @return mixed
     */
    public function rememberForever($key, Closure $callback)
    {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of minutes so it's available for all subsequent requests.
        if (! is_null($value)) {
            return $value;
        }

        $this->forever($key, $value = $callback());

        return $value;
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return $this->store->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->store->flush();
    }

    /**
     * Get the default cache time.
     *
     * @return float|int
     */
    public function getDefaultCacheTime()
    {
        return $this->default;
    }

    /**
     * Set the default cache time in minutes.
     *
     * @param  float|int  $minutes
     * @return $this
     */
    public function setDefaultCacheTime($minutes)
    {
        $this->default = $minutes;

        return $this;
    }

    /**
     * Get the cache store implementation.
     *
     * @return \Fast\Contracts\Cache\Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Determine if a cached value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Store an item in the cache for the default time.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->put($key, $value, $this->default);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetUnset($key)
    {
        return $this->forget($key);
    }

    /**
     * Calculate the number of minutes with the given duration.
     *
     * @param  \DateTimeInterface|\DateInterval|float|int  $duration
     * @return float|int|null
     */
    protected function getMinutes($duration)
    {
        $duration = $this->parseDateInterval($duration);

        if ($duration instanceof DateTimeInterface) {
            $duration = Carbon::now()->diffInSeconds(Carbon::createFromTimestamp($duration->getTimestamp()), false) / 60;
        }

        return (int) ($duration * 60) > 0 ? $duration : null;
    }

    /**
     * Clone cache repository instance.
     *
     * @return void
     */
    public function __clone()
    {
        $this->store = clone $this->store;
    }
}
