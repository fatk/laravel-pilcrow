<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Importers;

use Fatk\Pilcrow\Contracts\ImportTypeInterface;
use Fatk\Pilcrow\Helpers\{Term, Cache};
use Illuminate\Support\Collection;

/**
 * WordPress taxonomy term importer
 */
final class TermImporter implements ImportTypeInterface
{
    /**
     * @var Cache
     */
    private static Cache $cache;

    public function __construct()
    {
        self::$cache ??= new Cache();
    }

    /**
     * Process data and import term into WordPress
     *
     * @param Collection<string, mixed> $data
     * @return Collection<string, mixed>
     */
    public function import(Collection $data): Collection
    {
        if (!blank($data->get('name')) || !blank($data->get('path')) || !blank($data->get('taxonomy'))) {
            $term = new Term(
                path: $data->get('path'),
                taxonomy: $data->get('taxonomy')
            );

            $this->processFields($data, $term);

            $term->set($data->filter());
            $status = $term->save();
        }

        return collect([
            'id' => $term?->find()?->term_id ?? 'N/A',
            'path' => $data->get('path') ?? 'N/A',
            'parent' => $term?->findParent()?->term_id ?? 'N/A',
            'status' => $status ?? Term::STATUS['SAVE_SKIPPED']
        ]);
    }

    /**
     * Process fields requiring special handling
     *
     * @param Collection<string, mixed> $data
     * @param Term $term
     */
    private function processFields(Collection $data, Term $term): void
    {
        if (!blank($data->get('seo_title'))) {
            $term->setSeo(
                title: $data->get('seo_title'),
                description: $data->get('seo_description') ?? '',
                focusKeyword: $data->get('seo_keyword') ?? ''
            );
        }

        $data->forget(['seo_title', 'seo_description', 'seo_keyword']);

        $metadata = $data
            ->filter(fn($value, $key) => str_starts_with($key, 'm:'))
            ->mapWithKeys(fn($value, $key) => [substr($key, 2) => $value])
            ->filter();

        if ($metadata->isNotEmpty()) {
            $term->setMetadata($metadata);
        }

        $data->forget($data->filter(fn($value, $key) => str_starts_with($key, 'm:'))->keys());
    }
}
