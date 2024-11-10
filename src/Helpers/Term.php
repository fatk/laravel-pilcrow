<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Helpers;

use WP_Term;
use RuntimeException;
use Illuminate\Support\Collection;

/**
 * Class Term
 *
 * Manages WordPress taxonomy term operations with caching and path-based lookups.
 *
 * @package Fatk\Pilcrow\Helpers
 */
class Term
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

    /**
     * @var Cache
     */
    protected static Cache $cache;

    /**
     * @var Path
     */
    protected Path $path;

    /**
     * @var string
     */
    protected string $taxonomy;

    /**
     * @var WP_Term|null
     */
    protected ?WP_Term $term = null;

    /**
     * @var WP_Term|null
     */
    protected ?WP_Term $parent = null;

    /**
     * @var Collection
     */
    protected Collection $data;

    /**
     * Term constructor.
     *
     * @param string $path Term path
     * @param string $taxonomy Taxonomy name
     */
    public function __construct(string $path, string $taxonomy)
    {
        $this->path = new Path($path);
        $this->taxonomy = $taxonomy;
        $this->data = collect();
        self::$cache ??= new Cache();
    }

    /**
     * Set term data from a Collection.
     *
     * @param Collection<string, string> $data Term data
     * @return self
     */
    public function set(Collection $data): self
    {
        $this->data = $this->data->merge($data);
        return $this;
    }

    /**
     * Magic method to set term data.
     *
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function __set(string $name, $value): void
    {
        $this->data->put($name, $value);
    }

    /**
     * Set metadata for the term from a Collection.
     *
     * @param Collection<string, string> $metadata Metadata collection
     * @return self
     */
    public function setMetadata(Collection $metadata): self
    {
        $existingMeta = collect($this->data->get('meta_input', []));
        $mergedMeta = $existingMeta->merge($metadata);

        $this->data->put('meta_input', $mergedMeta->toArray());

        return $this;
    }

    /**
     * Set SEO metadata for supported plugins
     *
     * @param string $title
     * @param string $description
     * @param string|null $focusKeyword
     * @return self
     * @throws RuntimeException
     */
    public function setSeo(string $title, string $description, ?string $focusKeyword = null): self
    {
        $seoData = match (true) {
            defined('RANK_MATH_VERSION') => collect([
                'rank_math_title' => $title,
                'rank_math_description' => $description,
                'rank_math_focus_keyword' => $focusKeyword,
            ])->filter(),

            defined('WPSEO_VERSION') => collect([
                '_yoast_wpseo_title' => $title,
                '_yoast_wpseo_metadesc' => $description,
                '_yoast_wpseo_focuskw' => $focusKeyword,
            ])->filter(),

            default => throw new RuntimeException('No supported SEO plugin found'),
        };

        return $this->setMetadata($seoData);
    }

    /**
     * Creates or Updates the term
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

        $this->prepareData();
        $termName = $this->data->get('name');

        if ($this->exists()) {
            $result = wp_update_term(
                $this->term->term_id,
                $this->taxonomy,
                $this->data->toArray()
            );
        } else {
            $result = wp_insert_term(
                $termName,
                $this->taxonomy,
                $this->data->except('name')->toArray()
            );
        }

        if (is_wp_error($result)) {
            return self::STATUS['SAVE_FAILED'];
        }

        $this->term = get_term($result['term_id'], $this->taxonomy);

        if ($this->data->has('meta_input')) {
            foreach ($this->data->get('meta_input') as $key => $value) {
                update_term_meta($this->term->term_id, $key, $value);
            }
        }

        if (!$this->data->has('term_id')) {
            self::$cache->put("{$this->taxonomy}:{$this->path->get()}", $this->term);
        }

        return $this->data->has('term_id') ? self::STATUS['SAVE_UPDATED'] : self::STATUS['SAVE_CREATED'];
    }

    /**
     * Gets the term's slug
     *
     * @return string|null term's slug
     */
    protected function getTermSlug(): ?string
    {
        return (new Path($this->path->removeTaxonomyPrefix($this->taxonomy)))->segment()->last();
    }

    /**
     * Validate if the term can be saved.
     *
     * @return bool
     */
    protected function validateSave(): bool
    {
        return $this->exists() || $this->data->has('name');
    }

    /**
     * Prepare term data for saving.
     */
    protected function prepareData(): void
    {
        if ($this->exists()) {
            $this->data->put('term_id', $this->term?->term_id);
        }

        $this->data->put('slug', $this->getTermSlug());

        if ($parent = $this->findParent()) {
            $this->data->put('parent', $parent->term_id);
        }
    }

    /**
     * Check if the term data has changed.
     *
     * @return bool
     */
    protected function hasChangedData(): bool
    {
        $changed = $this->data
            ->only(['name', 'slug', 'parent', 'description'])
            ->filter(fn($value, $key) => $this->term?->$key !== $value);

        return !$this->term ? true :
            $this->data
            ->only(['name', 'slug', 'parent', 'description'])
            ->contains(fn($value, $key) => $this->term?->$key !== $value)
            || $this->hasChangedMetadata();
    }

    /**
     * Check if the term metadata has changed.
     *
     * @return bool
     */
    protected function hasChangedMetadata(): bool
    {
        if (!$this->data->has('meta_input') || !$this->term) {
            return false;
        }

        $newMetadata = collect($this->data->get('meta_input', []));
        $currentMetadata = collect(get_term_meta($this->term->term_id))
            ->map(fn($value) => $value[0] ?? null);

        return $newMetadata->diffAssoc($currentMetadata)->isNotEmpty();
    }

    /**
     * Check if the term exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->find() !== null;
    }

    /**
     * Find the term by path.
     *
     * @return WP_Term|null
     */
    public function find(): ?WP_Term
    {
        return $this->term ?? self::$cache->resolve(
            "{$this->taxonomy}:{$this->path->get()}",
            fn() => $this->term = $this->findByPath($this->path)
        );
    }

    /**
     * Find a term by its path.
     *
     * @param Path $path
     * @return WP_Term|null
     */
    protected function findByPath(Path $path): ?WP_Term
    {
        if ($path->get() === '/') {
            return null;
        }

        return get_term_by('slug', $this->getTermSlug(), $this->taxonomy) ?: null;
    }

    /**
     * Find the parent term.
     *
     * @return WP_Term|null
     */
    public function findParent(): ?WP_Term
    {
        if ($this->parent !== null) {
            return $this->parent;
        }

        $segments = $this->path->segment();
        if ($segments->isEmpty() || $segments->count() < 2) {
            return null;
        }

        $parentPath = new Path($segments->slice(0, -1)->join('/'));
        return self::$cache->resolve(
            "{$this->taxonomy}:{$parentPath->get()}",
            fn() => $this->parent = $this->findByPath($parentPath)
        );
    }
}
