<?php

namespace Fatk\Pilcrow\Helpers;

use Illuminate\Support\Collection;
use Fatk\Pilcrow\Helpers\Cache;

/**
 * Class Taxonomy
 *
 * Handles WordPress taxonomy operations and rewrite slug retrieval with caching.
 *
 * @package Fatk\Pilcrow\Helpers
 */
class Taxonomy
{
    /**
     * Collection cache for taxonomy rewrite slugs.
     *
     * @var Collection
     */
    protected static Cache $cache;

    /**
     * @var string
     */
    protected string $taxonomy;

    /**
     * @param string $taxonomy The taxonomy name
     */
    public function __construct(string $taxonomy)
    {
        self::$cache ??= new Cache();
        $this->taxonomy = $taxonomy;
    }

    /**
     * Retrieve the rewrite slug for the given taxonomy.
     *
     * @return string|null The rewrite slug or null if not found.
     */
    public function getPrefix(): ?string
    {
        return self::$cache->resolve(
            $this->taxonomy,
            fn() => get_taxonomy($this->taxonomy)?->rewrite['slug'] ?? null
        );
    }
}
