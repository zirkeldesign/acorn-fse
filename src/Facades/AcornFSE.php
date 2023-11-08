<?php

namespace Zirkeldesign\AcornFSE\Facades;

use Illuminate\Support\Facades\Facade;

class AcornFSE extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'AcornFSE';
    }
}
