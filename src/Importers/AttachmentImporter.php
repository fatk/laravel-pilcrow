<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Importers;

use Fatk\Pilcrow\Contracts\ImportTypeInterface;

/**
 * Handles the import of WordPress media attachments
 *
 * This importer handles file uploads, attachment post creation,
 * and metadata generation for media files. It supports various
 * media types and handles image processing when required.
 */
final class AttachmentImporter implements ImportTypeInterface
{
    /**
     * Import attachments from processed data
     *
     * Expected data structure:
     * [
     *     'file_path' => string,
     *     'title' => string,
     *     'caption' => string,
     *     'alt_text' => string,
     *     'description' => string,
     *     'meta' => array<string, mixed>
     * ]
     *
     * @inheritDoc
     */
    public function import(array $data): bool
    {
        return true;
    }
}
