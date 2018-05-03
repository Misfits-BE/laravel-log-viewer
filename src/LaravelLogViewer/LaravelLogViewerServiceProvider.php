<?php

namespace Melihovv\LaravelLogViewer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

/**
 * Class LaravelLogViewerServiceProvider
 * ----
 * The class for registering the facade name form the package
 * 
 * @author  Tim Joosten        <https://github.com/tjoosten>
 * @author  Alexander Melihovv <https://github.com/melihovv>
 * @license MIT License        <https://github.com/Misfits-BE/laravel-log-viewer/blob/master/LICENSE>
 */
class LaravelLogViewerServiceProvider extends ServiceProvider
{
    const CONFIG_PATH = __DIR__ . '/../config/laravel-log-viewer.php';

    const VIEWS_PATH = __DIR__ . '/../views';

    public function boot()
    {
        $this->loadViewsFrom(self::VIEWS_PATH, 'laravel-log-viewer');

        $this->publishes([
            self::CONFIG_PATH => config_path('laravel-log-viewer.php'),
        ], 'config');

        $this->publishes([
            self::VIEWS_PATH =>
                resource_path('views/vendor/laravel-log-viewer'),
        ], 'views');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            self::CONFIG_PATH,
            'laravel-log-viewer'
        );

        $this->app->bind('log-viewer', function () {
            $baseDir = Config::get('laravel-log-viewer.base_dir');

            return new LaravelLogViewer(
                $baseDir ?: storage_path('logs'),
                Config::get('laravel-log-viewer.max_file_size')
            );
        });
    }
}
