<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\SourceAdapters;

use Illuminate\Support\Collection;
use Fatk\Pilcrow\Contracts\{SourceAdapterInterface, ImportTypeInterface};

/**
 * Adapter for importing content from text-based files
 *
 * Handles the import of content from various text-based formats
 * including plain text, Markdown, and JSON files.
 */
final class ContentAdapter implements SourceAdapterInterface
{
    /**
     * @param ImportTypeInterface $importer The content type importer
     */
    public function __construct(
        private readonly ImportTypeInterface $importer
    ) {}

    /**
     * @inheritDoc
     */
    public function import(Collection $files): bool
    {
        $data = [];
        return $this->importer->import($data);
    }

    /**
     * @inheritDoc
     */
    public static function supportedExtensions(): array
    {
        return ['txt', 'md', 'json'];
    }
}
