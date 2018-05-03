<?php

namespace Melihovv\LaravelLogViewer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class LaravelLogViewer
 * ----
 * The class for registering the facade name form the package
 * 
 * @author  Tim Joosten        <https://github.com/tjoosten>
 * @author  Alexander Melihovv <https://github.com/melihovv>
 * @license MIT License        <https://github.com/Misfits-BE/laravel-log-viewer/blob/master/LICENSE>
 */
class LaravelLogViewer extends Facade
{
    /**
     * Method for registering the facade for the package. 
     * 
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'log-viewer';
    }
}
