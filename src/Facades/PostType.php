<?php

namespace Fatk\Pilcrow\Facades;

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
        return \Fatk\Pilcrow\Helpers\PostType::class;
    }
}
