<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Logging;

use Illuminate\Support\{Collection, Str};
use Fatk\Pilcrow\Helpers\Post;


/**
 * Handles import operation logging and reporting
 */
final class ImportLog
{
    /** @var Collection<string, Collection> File-level logs */
    private Collection $fileLogs;

    public function __construct()
    {
        $this->fileLogs = collect();
    }

    /**
     * Add log entries for a file
     */
    public function add(string $filePath, Collection $entries): void
    {
        $this->fileLogs->put($filePath, $entries);
    }

    /**
     * Generate summary statistics per file
     *
     * @return array{headers: array<string>, rows: array<array<string|int>>}
     */
    public function getSummary(): array
    {
        $headers = ['File', 'Total', 'Created', 'Updated', 'Skipped', 'Failed', 'No Change'];

        $rows = $this->fileLogs->map(function (Collection $entries, string $filePath): array {
            $stats = $this->calculateStats($entries);

            return [
                basename($filePath),
                $stats['total'],
                $stats[Post::STATUS['SAVE_CREATED']],
                $stats[Post::STATUS['SAVE_UPDATED']],
                $stats[Post::STATUS['SAVE_SKIPPED']],
                $stats[Post::STATUS['SAVE_FAILED']],
                $stats[Post::STATUS['SAVE_NOOP']],
            ];
        })->values()->all();

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Generate detailed log with all entry data
     *
     * @return array{headers: array<string>, rows: array<array<mixed>>}
     */
    public function getDetails(): array
    {
        if ($this->fileLogs->isEmpty()) {
            return ['headers' => [], 'rows' => []];
        }

        $allEntries = $this->fileLogs->flatten(1);

        $headers = $allEntries
            ->flatMap(fn(Collection $entry) => $entry->keys())
            ->unique()
            ->map(fn(string $header) => Str::title($header))
            ->values()
            ->all();

        $rows = $allEntries
            ->map(function (Collection $entry) use ($headers): array {
                return collect($headers)
                    ->map(function (string $header) use ($entry): string {
                        $value = $entry->get(Str::lower($header), '');

                        if (Str::lower($header) === 'status') {
                            return $this->formatStatus($value);
                        }

                        return $value;
                    })
                    ->all();
            })
            ->all();

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Format status code to readable text
     */
    private function formatStatus(int $status): string
    {
        return match ($status) {
            Post::STATUS['SAVE_CREATED'] => '<fg=green>CREATED</>',
            Post::STATUS['SAVE_UPDATED'] => 'UPDATED',
            Post::STATUS['SAVE_SKIPPED'] => '<fg=yellow>SKIPPED</>',
            Post::STATUS['SAVE_FAILED'] => '<fg=red>FAILED</>',
            Post::STATUS['SAVE_NOOP'] => '<fg=yellow>NOOP</>',
            default => (string) $status,
        };
    }

    /**
     * Calculate statistics for a collection of entries
     *
     * @param Collection<int, Collection> $entries
     * @return array<string|int, int>
     */
    private function calculateStats(Collection $entries): array
    {
        $stats = [
            'total' => $entries->count(),
            Post::STATUS['SAVE_CREATED'] => 0,
            Post::STATUS['SAVE_UPDATED'] => 0,
            Post::STATUS['SAVE_SKIPPED'] => 0,
            Post::STATUS['SAVE_FAILED'] => 0,
            Post::STATUS['SAVE_NOOP'] => 0,
        ];

        $entries->each(function (Collection $entry) use (&$stats): void {
            $status = $entry->get('status');
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        });

        return $stats;
    }
}
