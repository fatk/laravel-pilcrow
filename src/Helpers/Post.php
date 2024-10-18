<?php

namespace Fatk\WpKit\Helpers;

use WP_Post;
use WP_Error;
use Illuminate\Support\Collection;

/**
 * Class Post
 *
 * Manages WordPress post operations with caching and path-based lookups.
 *
 * @package Fatk\WpKit\Helpers
 */
class Post
{
    /**
     * Status constants for save operations.
     */
    public const STATUS = [
        'SAVE_CREATED' => 0,
        'SAVE_UPDATED' => 1,
        'SAVE_SKIPPED' => 2,
        'SAVE_FAILED'  => 3,
        'SAVE_NOOP'    => 4,
    ];

    /** @var Cache $cache Static cache instance */
    protected static Cache $cache;

    /** @var Path $path Path instance */
    protected Path $path;

    /** @var string $type Post type */
    protected string $type;

    /** @var WP_Post|null $post Current post */
    protected ?WP_Post $post = null;

    /** @var WP_Post|null $parent Parent post */
    protected ?WP_Post $parent = null;

    /** @var Collection $data Post data */
    protected Collection $data;

    /**
     * Post constructor.
     *
     * @param string $path Post path
     * @param string $type Post type (default: 'post')
     */
    public function __construct(string $path, string $type = 'post')
    {
        $this->path = new Path($path);
        $this->type = $type;
        $this->data = collect();
        self::$cache ??= new Cache();
    }

    /**
     * Set post data from a Collection.
     *
     * @param Collection $data Post data
     * @return self
     */
    public function set(Collection $data): self
    {
        $this->data = $this->data->merge($data);
        return $this;
    }

    /**
     * Magic method to set post data.
     *
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function __set(string $name, $value): void
    {
        $this->data->put($name, $value);
    }

    /**
     * Creates or Updates the post
     *
     * @return int Save status
     */
    public function save(): int
    {
        if (!$this->validateSave()) {
            return self::STATUS['SAVE_FAILED'];
        }

        if ($this->exists() && ($this->data->isEmpty() || !$this->hasChangedData())) {
            return self::STATUS['SAVE_NOOP'];
        }

        $this->preparePostData();
        $post_id = wp_insert_post($this->data->toArray(), true);

        if (is_wp_error($post_id)) {
            return self::STATUS['SAVE_FAILED'];
        }

        $this->post = get_post($post_id);

        if (!$this->data->has('ID')) {
            self::$cache->put($this->path->get(), $this->post);
        }

        return $this->data->has('ID') ? self::STATUS['SAVE_UPDATED'] : self::STATUS['SAVE_CREATED'];
    }

    /**
     * Validate if the post can be saved.
     *
     * @return bool
     */
    protected function validateSave(): bool
    {
        $requiredFields = ['post_title', 'post_content', 'post_author'];

        return $this->exists() || $this->data->only($requiredFields)->filter()->isNotEmpty();
    }

    /**
     * Prepare post data for saving.
     */
    protected function preparePostData(): void
    {
        if ($this->exists()) {
            $this->data->put('ID', $this->post->ID);
        }
        $this->data->put('post_name', $this->path->segment()->last());
        $this->data->put('post_type', $this->type);
        $this->data->put('post_parent', ($this->findParent())?->ID ?? 0);
    }

    /**
     * Check if the post data has changed.
     *
     * @return bool
     */
    protected function hasChangedData(): bool
    {
        return $this->data->contains(fn($value, $key) => $this->post->$key !== $value);
    }

    /**
     * Check if the post exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->find() !== null;
    }

    /**
     * Find the post by path.
     *
     * @return WP_Post|null
     */
    public function find(): ?WP_Post
    {
        return $this->post ?? self::$cache->resolve(
            $this->path->get(),
            fn() => $this->post = $this->findByPath($this->path)
        );
    }

    /**
     * Find the parent post.
     *
     * @return WP_Post|null
     */
    public function findParent(): ?WP_Post
    {
        if ($this->parent !== null) {
            return $this->parent;
        }

        $segments = $this->path->segment();
        if ($segments->isEmpty()) {
            return null;
        }

        $parentPath = new Path($segments->slice(0, -1)->join('/'));
        return self::$cache->resolve(
            $parentPath->get(),
            fn() => $this->parent = $this->findByPath($parentPath)
        );
    }

    /**
     * Find a post by its path.
     *
     * @param Path $path
     * @return WP_Post|null
     */
    protected function findByPath(Path $path): ?WP_Post
    {
        if ($path->get() === '/' && get_option('show_on_front') === 'page') {
            return get_post(get_option('page_on_front'));
        }

        return get_page_by_path(
            page_path: $path->removePostTypePrefix($this->type),
            post_type: $this->type
        );
    }
}
