<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\SourceAdapters;

use Illuminate\Support\Collection;
use Fatk\Pilcrow\Contracts\{SourceAdapterInterface, ImportTypeInterface};

/**
 * Adapter for importing content from Excel files
 *
 * Handles the import of content from Excel spreadsheets (.xlsx, .xls)
 * and CSV files. Converts spreadsheet data into a format suitable
 * for the content importer.
 */
final class ExcelAdapter implements SourceAdapterInterface
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
        return ['xlsx', 'xls', 'csv'];
    }
}
