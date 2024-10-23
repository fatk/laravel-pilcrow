<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Contracts;

/**
 * Contract for WordPress content type importers
 *
 * This interface defines the contract for classes that handle the import
 * of specific WordPress content types (posts, users, attachments, etc.).
 * Implementations should handle the transformation of raw data into
 * WordPress entities and their persistence.
 */
interface ImportTypeInterface
{
    /**
     * Import content from processed data
     *
     * Handles the transformation and persistence of data into WordPress
     * entities. The structure of the input data will depend on the
     * source adapter being used.
     *
     * @param array<mixed> $data Processed data from the source adapter
     * @return bool True if import was successful, false otherwise
     *
     * @throws \RuntimeException If the import process fails
     */
    public function import(array $data): bool;
}
