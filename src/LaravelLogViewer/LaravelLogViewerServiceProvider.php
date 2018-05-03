<?php

namespace Melihovv\LaravelLogViewer;

use Route;
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
    const VIEWS_PATH  = __DIR__ . '/../views';

    /**
     * Bootstrap any application services.
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(self::VIEWS_PATH, 'laravel-log-viewer');

        $this->publishes([self::CONFIG_PATH => config_path('laravel-log-viewer.php')], 'config');
        $this->publishes([self::VIEWS_PATH  => resource_path('views/vendor/laravel-log-viewer')], 'views');

        if (config('laravel-log-viewer.debug_only', true) && empty('app.debug')) {
            return;
        }

        // Register the route for the logs view. 
        Route::get(config('laravel-log-viewer.url', 'Melihovv\LaravelLogViewer\LaravelLogViewerController@index'))
            ->name('log.viewer.index');
    }

    /**
     * Register bindings in the container.
     * 
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'laravel-log-viewer');

        $this->app->bind('log-viewer', function (): LaravelLogViewer {
            $baseDir = Config::get('laravel-log-viewer.base_dir');
            return new LaravelLogViewer($baseDir ?: storage_path('logs'), Config::get('laravel-log-viewer.max_file_size'));
        });
    }
}
