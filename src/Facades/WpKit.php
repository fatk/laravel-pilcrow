<?php

namespace Fatk\WpKit\Facades;

use Illuminate\Support\Facades\Facade;

class WpKit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'wp-kit';
    }
}
