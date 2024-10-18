<?php

namespace Fatk\WpKit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null getPrefix(string $type)
 */
class PostType extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Fatk\WpKit\Helpers\PostType::class;
    }
}
