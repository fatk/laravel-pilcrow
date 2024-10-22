<?php

namespace Fatk\Pilcrow\Facades;

use Illuminate\Support\Facades\Facade;

class Pilcrow extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pilcrow';
    }
}
