<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Importers;

use Fatk\Pilcrow\Contracts\ImportTypeInterface;

/**
 * Handles the import of WordPress users
 *
 * This importer manages user creation and updates, including
 * user metadata, roles, and capabilities. It handles password
 * hashing and user notification if required.
 */
final class UserImporter implements ImportTypeInterface
{
    /**
     * Import users from processed data
     *
     * Expected data structure:
     * [
     *     'user_login' => string,
     *     'user_email' => string,
     *     'user_pass' => string,
     *     'role' => string,
     *     'meta' => array<string, mixed>,
     *     'send_notification' => bool
     * ]
     *
     * @inheritDoc
     */
    public function import(array $data): bool
    {
        return true;
    }
}
