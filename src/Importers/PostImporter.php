<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Importers;

use Fatk\Pilcrow\Contracts\ImportTypeInterface;
use Fatk\Pilcrow\Helpers\{Post, Cache};
use Illuminate\Support\Collection;

/**
 * WordPress post content importer
 */
final class PostImporter implements ImportTypeInterface
{
    /**
     * @var Cache
     */
    protected static Cache $templateCache;


    public function __construct()
    {
        self::$templateCache ??= new Cache();
    }

    /**
     * Process data and import content into WordPress
     *
     * @param Collection<string, mixed> $data
     * @return Collection<string, mixed>
     */
    public function import(Collection $data): Collection
    {
        $post = new Post(
            path: $data->get('path'),
            type: $data->get('post_type', 'post')
        );

        $this->processFields($data, $post);

        $post->set($data->filter());
        $status = $post->save();

        return collect([
            'id' => $post->find()?->ID,
            'path' => $data->get('path'),
            'parent' => $post->findParent()?->ID,
            'status' => $status
        ]);
    }

    /**
     * Process fields requiring special handling
     *
     * @param Collection<string, mixed> $data
     * @param Post $post
     */
    private function processFields(Collection $data, Post $post): void
    {
        if (!blank($data->get('seo_title'))) {
            $post->setSeo(
                title: $data->get('seo_title'),
                description: $data->get('seo_description') ?? '',
                focusKeyword: $data->get('seo_keyword') ?? ''
            );
            $data->forget(['seo_title', 'seo_description', 'seo_keyword']);
        }

        if (!blank($data->get('template'))) {
            $template = $this->getTemplateFile($data->get('template'), $data->get('post_type', 'post'));

            if ($template !== false) {
                $post->setMetadata(collect(['_wp_page_template' => $template]));
            }
        }

        if (!blank($data->get('post_category'))) {
            $data['post_category'] = $this->resolveCategories($data->get('post_category'));
        }

        if (!blank($data->get('post_author'))) {
            $data['post_author'] = $this->resolveAuthor($data->get('post_author'));
        }

        if (!blank($data->get('tags_input'))) {
            $data['tags_input'] = $this->processTags($data->get('tags_input'));
        }

        if (blank($data->get('post_content'))) {
            $data['post_content'] = '';
        }
    }

    /**
     * Process tags into array
     *
     * @param mixed $tags
     * @return array<string>
     */
    private function processTags(mixed $tags): array
    {
        return collect(is_array($tags) ? $tags : explode(',', (string) $tags))
            ->map('trim')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resolve category IDs from slugs or IDs
     *
     * @param mixed $categories
     * @return array<int>
     */
    private function resolveCategories(mixed $categories): array
    {
        if (empty($categories)) {
            return [];
        }

        return collect(is_array($categories) ? $categories : explode(',', (string) $categories))
            ->map('trim')
            ->map(
                fn($cat) => is_numeric($cat)
                    ? (int) $cat
                    : get_term_by('slug', $cat, 'category')?->term_id
            )
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resolve author ID from username or ID
     *
     * @param mixed $author
     * @return string|null
     */
    private function resolveAuthor(mixed $author): ?string
    {
        if (is_numeric($author)) {
            return (string) $author;
        }

        $user = get_user_by('slug', $author) ?: get_user_by('login', $author);
        return $user?->ID ? (string) $user->ID : null;
    }

    /**
     * Resolve template file from name
     *
     * @param string $template
     * @param string $postType
     * @return string|bool
     */
    private function getTemplateFile(string $template, string $postType): string|bool
    {
        $templates = self::$templateCache->resolve(
            $postType,
            fn(): Collection => collect(wp_get_theme()->get_page_templates(post_type: $postType))
        );

        return $templates->search($template);
    }
}
