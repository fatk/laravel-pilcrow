<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for source adapters that handle file imports
 *
 * Defines the interface for classes that handle importing content
 * from various file sources into WordPress. Each adapter is responsible
 * for handling specific file types and converting their contents into
 * a format suitable for the importer.
 */
interface SourceAdapterInterface
{
    /**
     * Import content from the provided collection of files
     *
     * @param Collection<array{
     *    path: string,
     *    name: string,
     *    extension: string,
     *    size: int,
     *    modified: int
     * }> $files Files to import
     * @return bool True if import was successful, false otherwise
     */
    public function import(Collection $files): bool;

    /**
     * Get list of file extensions supported by this adapter
     *
     * @return string[] Array of supported file extensions without dots
     */
    public static function supportedExtensions(): array;
}
