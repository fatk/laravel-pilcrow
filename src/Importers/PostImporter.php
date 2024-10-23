<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Importers;

use Fatk\Pilcrow\Contracts\ImportTypeInterface;

/**
 * Handles the import of WordPress posts
 *
 * This importer handles the creation and update of WordPress posts,
 * including their metadata, taxonomies, and other related data.
 * It can handle various post types and their specific requirements.
 */
final class PostImporter implements ImportTypeInterface
{
    /**
     * Import posts from processed data
     *
     * Expected data structure:
     * [
     *     'title' => string,
     *     'content' => string,
     *     'status' => string,
     *     'post_type' => string,
     *     'meta' => array<string, mixed>,
     *     'taxonomies' => array<string, array<string>>
     * ]
     *
     * @inheritDoc
     */
    public function import(array $data): bool
    {
        return true;
    }
}
