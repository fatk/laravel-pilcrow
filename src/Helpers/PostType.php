<?php

namespace Fatk\Pilcrow\Helpers;

use Illuminate\Support\Collection;
use Fatk\Pilcrow\Helpers\Cache;

/**
 * Class PostType
 *
 * Handles WordPress post type operations and rewrite slug retrieval with caching.
 *
 * @package Fatk\Pilcrow\Helpers
 */
class PostType
{
    /**
     * Collection cache for post type rewrite slugs.
     *
     * @var Collection
     */
    protected static Cache $cache;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @param string $type The post type name
     */
    public function __construct(string $type)
    {
        self::$cache ??= new Cache();
        $this->type = $type;
    }

    /**
     * Retrieve the rewrite slug for the given post type.
     *
     * @return string|null The rewrite slug or null if not found.
     */
    public function getPrefix(): ?string
    {
        return self::$cache->resolve(
            $this->type,
            fn() => get_post_type_object($this->type)?->rewrite['slug'] ?? null
        );
    }
}
