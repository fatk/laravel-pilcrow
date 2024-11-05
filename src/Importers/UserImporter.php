<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Importers;

use Fatk\Pilcrow\Contracts\ImportTypeInterface;
use Illuminate\Support\Collection;
use Fatk\Pilcrow\Helpers\User;

/**
 * Handles the import of WordPress users
 *
 * This importer manages user creation and updates, including
 * user metadata, roles, and capabilities. It handles password
 * hashing and user notification if required.
 */
final class UserImporter implements ImportTypeInterface
{

    private static ?Collection $roles;

    public function __construct()
    {
        self::$roles ??= collect(wp_roles()?->roles)->keys();
    }

    /**
     * Import users from processed data
     *
     * @inheritDoc
     */
    public function import(Collection $data): Collection
    {
        if (!blank($data->get('user_login'))) {
            $user = new User($data->get('user_login'));

            $this->processFields($data, $user);

            $user->set($data->filter());
            $status = $user->save();
        }

        return collect([
            'id' => $user?->find()?->ID ?? 'N/A',
            'login' => $data->get('user_login'),
            'display name' => $data->get('display_name'),
            'role' => $data->get('role'),
            'status' => $status ?? User::STATUS['SAVE_SKIPPED']
        ]);
    }

    /**
     * Process fields requiring special handling
     *
     * @param Collection<string, mixed> $data
     * @param User $user
     */
    private function processFields(Collection $data, User $user): void
    {
        if (!blank($data->get('seo_title'))) {
            $user->setSeo(
                title: $data->get('seo_title'),
                description: $data->get('seo_description') ?? '',
                focusKeyword: $data->get('seo_keyword') ?? ''
            );
        }

        $data->forget(['seo_title', 'seo_description', 'seo_keyword']);

        $socials = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'spotify'];
        $profiles = $data->only($socials)->filter();

        if ($profiles->isNotEmpty()) {
            $user->setSocialProfiles($profiles);
        }

        $data->forget($socials);

        $metadata = $data
            ->filter(fn($value, $key) => str_starts_with($key, 'm:'))
            ->mapWithKeys(fn($value, $key) => [substr($key, 2) => $value])
            ->filter();

        if ($metadata->isNotEmpty()) {
            $user->setMetadata($metadata);
        }

        $data->forget($data->filter(fn($value, $key) => str_starts_with($key, 'm:'))->keys());
    }
}
