<?php

namespace Fatk\Pilcrow\Helpers;

use Illuminate\Support\Collection;
use Fatk\Pilcrow\Helpers\Cache;

class PostType
{
    /**
     * Collection cache for post type rewrite slugs.
     *
     * @var Collection
     */
    protected Cache $cache;


    public function __construct()
    {
        $this->cache = new Cache();
    }

    /**
     * Retrieve the rewrite slug for the given post type.
     *
     * @param string $type The post type to retrieve the slug for.
     * @return string|null The rewrite slug or null if not found.
     */
    public function getPrefix(string $type): ?string
    {
        // Fetch rewrite slug from post type object and cache it
        return $this->cache->resolve($type, fn() => get_post_type_object($type)?->rewrite['slug'] ?? null);
    }
}
