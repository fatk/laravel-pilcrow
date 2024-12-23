<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Helpers;

use WP_User;
use RuntimeException;
use Illuminate\Support\Collection;

/**
 * Manages WordPress user operations with caching
 */
class User
{
    public const STATUS = [
        'SAVE_CREATED' => 0,
        'SAVE_UPDATED' => 1,
        'SAVE_SKIPPED' => 2,
        'SAVE_FAILED'  => 3,
        'SAVE_NOOP'    => 4,
    ];

    protected static Cache $cache;
    protected string $login;
    protected ?WP_User $user = null;
    protected Collection $data;

    /**
     * Initialize user with login name
     *
     * @param string $login
     */
    public function __construct(string $login)
    {
        $this->login = trim($login);
        $this->data = collect();
        self::$cache ??= new Cache();
    }

    /**
     * Merge new data with existing user data
     *
     * @param Collection<string, mixed> $data
     * @return self
     */
    public function set(Collection $data): self
    {
        $this->data = $this->data->merge($data);
        return $this;
    }

    /**
     * Set single user data property
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value): void
    {
        $this->data->put($name, $value);
    }

    /**
     * Set user metadata values
     *
     * @param Collection<string, mixed> $metadata
     * @return self
     */
    public function setMetadata(Collection $metadata): self
    {
        $existingMeta = collect($this->data->get('meta_input', []));
        $mergedMeta = $existingMeta->merge($metadata);

        $this->data->put('meta_input', $mergedMeta->toArray());

        return $this;
    }

    /**
     * Set SEO metadata for supported plugins
     *
     * @param string $title
     * @param string $description
     * @param string|null $focusKeyword
     * @return self
     * @throws RuntimeException
     */
    public function setSeo(string $title, string $description, ?string $focusKeyword = null): self
    {
        $seoData = match (true) {
            defined('RANK_MATH_VERSION') => collect([
                'rank_math_title' => $title,
                'rank_math_description' => $description,
                'rank_math_focus_keyword' => $focusKeyword,
            ])->filter(),

            defined('WPSEO_VERSION') => collect([
                '_yoast_wpseo_title' => $title,
                '_yoast_wpseo_metadesc' => $description,
                '_yoast_wpseo_focuskw' => $focusKeyword,
            ])->filter(),

            default => throw new RuntimeException('No supported SEO plugin found'),
        };

        return $this->setMetadata($seoData);
    }

    /**
     * Set social media profile metadata
     *
     * @param Collection<string, string> $profiles
     * @return self
     * @throws RuntimeException
     */
    public function setSocialProfiles(Collection $profiles): self
    {
        if ($profiles->isEmpty()) {
            return $this;
        }

        $seoData = match (true) {
            defined('RANK_MATH_VERSION') => collect([
                'facebook' => $profiles->get('facebook'),
                'twitter' => $profiles->get('twitter'),
                'additional_profile_urls' => $profiles
                    ->only(['instagram', 'linkedin', 'youtube'])
                    ->filter()
                    ->values()
                    ->join(' '),
            ])->filter(),

            defined('WPSEO_VERSION') => $profiles
                ->mapWithKeys(function (string $url, string $network) {
                    return match ($network) {
                        'facebook'  => ['facebook' => $url],
                        'twitter'   => ['twitter' => str_replace('@', '', parse_url($url, PHP_URL_PATH) ?? '')],
                        'instagram' => ['instagram_url' => $url],
                        'linkedin'  => ['linkedin' => $url],
                        'youtube'   => ['youtube_url' => $url],
                        default => [],
                    };
                })
                ->mapWithKeys(fn(string $value, string $key) => [
                    "wpseo_{$key}" => $value,
                ]),

            default => throw new RuntimeException('No supported SEO plugin found'),
        };

        return $this->setMetadata($seoData);
    }

    /**
     * Create or update WordPress user
     *
     * @return int
     */
    public function save(): int
    {
        if (!$this->validateSave()) {
            return self::STATUS['SAVE_FAILED'];
        }

        if ($this->exists() && ($this->data->isEmpty() || !$this->hasChangedData())) {
            return self::STATUS['SAVE_NOOP'];
        }

        $this->prepareData();
        $userId = wp_insert_user($this->data->toArray(), true);

        if (is_wp_error($userId)) {
            return self::STATUS['SAVE_FAILED'];
        }

        $this->user = get_user_by('id', $userId) ?: null;

        if (!$this->data->has('ID')) {
            self::$cache->put($this->login, $this->user);
        }

        return $this->data->has('ID') ? self::STATUS['SAVE_UPDATED'] : self::STATUS['SAVE_CREATED'];
    }

    /**
     * Validate required user data
     *
     * @return bool
     */
    protected function validateSave(): bool
    {
        $requiredFields = ['user_login', 'user_email', 'role'];

        return $this->exists() || $this->data->only($requiredFields)->filter()->isNotEmpty();
    }

    /**
     * Prepare data for WordPress user creation/update
     */
    protected function prepareData(): void
    {
        if ($this->exists()) {
            $this->data->put('ID', $this->user->ID);
        } else {
            $this->data->put('user_pass', wp_generate_password());
        }

        $this->data->put('user_login', $this->login);
    }

    /**
     * Check if user data has been modified
     *
     * @return bool
     */
    protected function hasChangedData(): bool
    {
        $data = $this->data->except(['meta_input', 'role']);

        return $data->contains(fn($value, $key) => $this->user->$key !== $value)
            || !in_array($this->data->get('role'), $this->user->roles)
            || $this->hasChangedMetadata();
    }

    /**
     * Check if user metadata has been modified
     *
     * @return bool
     */
    protected function hasChangedMetadata(): bool
    {
        if (!$this->data->has('meta_input') || !$this->user) {
            return false;
        }

        $newMetadata = collect($this->data->get('meta_input', []));
        $currentMetadata = collect(get_user_meta($this->user->ID))
            ->map(fn($value) => $value[0] ?? null);

        return $newMetadata->diffAssoc($currentMetadata)->isNotEmpty();
    }

    /**
     * Check if user exists in WordPress
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->find() !== null;
    }

    /**
     * Find WordPress user by login
     *
     * @return WP_User|null
     */
    public function find(): ?WP_User
    {
        return $this->user ?? self::$cache->resolve(
            $this->login,
            fn() => $this->user = get_user_by('login', $this->login) ?: null
        );
    }
}
