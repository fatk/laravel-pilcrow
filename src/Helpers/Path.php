<?php

namespace Fatk\Pilcrow\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class Path
 *
 * Handles operations on URL paths, including parsing, segmenting,
 * and removing post type prefixes.
 */
class Path
{
    /**
     * The original path string.
     *
     * @var string
     */
    protected string $path;

    /**
     * The segments of the path, split by '/'.
     *
     * @var Collection|null
     */
    protected ?Collection $segments;

    /**
     * Path constructor.
     *
     * @param string $path The path or URL string to initialize the object with.
     * @throws InvalidArgumentException If the path is empty or invalid.
     */
    public function __construct(string $path)
    {
        $this->path = Str::isUrl($path) ? $this->fromUrl($path) : $path;
        $this->path = $this->path === '/' ? '/' : trim($this->path, '/');

        if (empty($this->path)) {
            throw new InvalidArgumentException("The provided path is empty or invalid.");
        }

        $this->segments = null;
    }

    /**
     * Create a Path instance from a full URL, extracting its path component.
     *
     * @param string $url The full URL to extract the path from.
     * @return static A new Path instance.
     * @throws InvalidArgumentException If the URL is invalid or the path is empty.
     */
    protected function fromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === false || is_null($path)) {
            throw new InvalidArgumentException("The provided URL is invalid or has no path.");
        }

        return $path;
    }

    /**
     * Get the original path as a string.
     *
     * @return string The original path.
     */
    public function get(): string
    {
        return $this->path;
    }

    /**
     * Get the segments of the path as a collection.
     *
     * @return Collection The path segments as a collection.
     */
    public function segment(): Collection
    {
        if ($this->segments === null) {
            $this->segments = $this->path === '/' ? collect(['/']) : collect(explode('/', $this->path))->filter();
        }

        return $this->segments;
    }

    /**
     * Remove the post type prefix from the path, if it exists.
     *
     * @param string $type The post type whose prefix needs to be removed.
     * @return string The path without the post type prefix.
     */
    public function removePostTypePrefix(string $type): string
    {
        return $this->removePrefix((new PostType($type))?->getPrefix());
    }

    /**
     * Remove the taxonomy prefix from the path, if it exists.
     *
     * @param string $taxonomy The taxonomy whose prefix needs to be removed
     * @return string The path without the taxonomy prefix
     */
    public function removeTaxonomyPrefix(string $taxonomy): string
    {
        return  $this->removePrefix((new Taxonomy($taxonomy))?->getPrefix());
    }

    /**
     * Remove prefix from the path, if it exists.
     *
     * @param string $prefix The prefix to be removed
     * @return string The path without the prefix
     */
    protected function removePrefix(?string $prefix): string
    {
        if ($this->path === '/' || !$prefix) {
            return $this->path;
        }

        $segments = $this->segment();

        if ($segments->first() === $prefix) {
            return $segments->slice(1)->join('/');
        }

        return $this->path;
    }
}
