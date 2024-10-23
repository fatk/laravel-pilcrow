<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Files;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use InvalidArgumentException;

/**
 * Handles file discovery and filtering for import operations
 *
 * This class is responsible for finding and filtering files based on
 * supported extensions and patterns. It provides file metadata and
 * ensures only valid files are processed.
 */
final class FileDiscovery
{
    private readonly Finder $finder;

    public function __construct()
    {
        $this->finder = new Finder();
    }

    /**
     * Discover files in the specified path matching given criteria
     *
     * @param string $path Directory to search in
     * @param string[] $supportedExtensions List of supported file extensions
     * @param string|null $pattern Optional filename pattern to match
     *
     * @return Collection<array{
     *    path: string,
     *    name: string,
     *    extension: string,
     *    size: int,
     *    modified: int
     * }> Collection of matching file information
     *
     * @throws InvalidArgumentException If the path is invalid or inaccessible
     */
    public function discover(string $path, array $supportedExtensions, ?string $pattern = null): Collection
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException("Invalid directory path: {$path}");
        }

        $this->finder->files()->in($path);

        if ($pattern) {
            $this->finder->name($pattern);
        }

        $extensionPattern = sprintf('/\.(%s)$/i', implode('|', $supportedExtensions));
        $this->finder->name($extensionPattern);

        return collect($this->finder)
            ->map(fn($file) => [
                'path' => $file->getRealPath(),
                'name' => $file->getFilename(),
                'extension' => Str::lower($file->getExtension()),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
            ]);
    }
}
