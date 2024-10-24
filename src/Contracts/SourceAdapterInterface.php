<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Contracts;

use Illuminate\Support\Collection;
use Fatk\Pilcrow\Logging\ImportLog;

/**
 * Contract for source adapters that handle file imports
 */
interface SourceAdapterInterface
{
    /**
     * Import content from the provided collection of files
     *
     * @param Collection<array{path: string, name: string, extension: string}> $files
     * @return ImportLog
     * @throws \RuntimeException
     */
    public function import(Collection $files): ImportLog;

    /**
     * @return string[]
     */
    public static function supportedExtensions(): array;
}
