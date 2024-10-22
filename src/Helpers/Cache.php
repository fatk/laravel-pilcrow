<?php

namespace Fatk\Pilcrow\Helpers;

use Illuminate\Support\Collection;

class Cache
{
    /**
     * The collection holding cached key-value pairs.
     *
     * @var Collection<string, mixed>
     */
    protected Collection $cache;

    /**
     * Constructor to initialize the cache collection.
     */
    public function __construct()
    {
        $this->cache = new Collection();
    }

    /**
     * Get a cached value or compute and store it if not found.
     *
     * @param string $key The key to retrieve or store.
     * @param callable $callback The function to compute the value if not cached.
     * @return mixed The cached value.
     */
    public function resolve(string $key, callable $callback): mixed
    {
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $value = $callback();
        $this->cache->put($key, $value);

        return $value;
    }

    /**
     * Check if a key exists in the cache.
     *
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    /**
     * Get a cached value by key.
     *
     * @param string $key The key to retrieve.
     * @return mixed|null The cached value or null if not found.
     */
    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    /**
     * Store a value in the cache.
     *
     * @param string $key The key to store the item under.
     * @param mixed $value The value to store.
     * @return void
     */
    public function put(string $key, mixed $value): void
    {
        $this->cache->put($key, $value);
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->cache = new Collection();
    }
}
