<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\SourceAdapters;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\{Collection, Str};
use Fatk\Pilcrow\Contracts\{SourceAdapterInterface, ImportTypeInterface};
use Fatk\Pilcrow\Logging\ImportLog;
use RuntimeException;

/**
 * Excel source adapter for importing content from Excel files
 */
final class ExcelAdapter implements SourceAdapterInterface
{
    /**
     * @param ImportTypeInterface $importer Content importer implementation
     */
    public function __construct(
        private readonly ImportTypeInterface $importer
    ) {}

    /**
     * Process and import content from Excel files
     *
     * @param Collection<array{path: string, name: string, extension: string}> $files
     * @return ImportLog
     * @throws RuntimeException
     */
    public function import(Collection $files): ImportLog
    {
        try {
            $log = new ImportLog();

            $files->each(function (array $file) use ($log): void {
                $rows = $this->processExcelFile($file['path']);

                $results = $rows->map(
                    fn(array $row): Collection => $this->importer->import(collect($row))
                );

                $log->add($file['path'], $results);
            });

            return $log;
        } catch (\Throwable $e) {
            throw new RuntimeException(
                "Excel import failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Convert Excel file into collection of data rows
     *
     * @param string $path
     * @return Collection<int, array<string, mixed>>
     * @throws RuntimeException
     */
    private function processExcelFile(string $path): Collection
    {
        $rows = collect(Excel::toArray(null, $path)[0] ?? []);

        if ($rows->isEmpty()) {
            return collect();
        }

        $headers = collect($rows->first())
            ->map(fn($header) => Str::snake(strtolower(trim($header ?? ''))))
            ->values();

        return $rows->slice(1)
            ->map(fn($row) => $headers->combine(
                collect($row)->map(fn($value) => is_string($value) ? trim($value) : $value)
            )->all());
    }

    /**
     * @return string[]
     */
    public static function supportedExtensions(): array
    {
        return ['xlsx', 'xls', 'csv'];
    }
}
