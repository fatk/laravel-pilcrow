<?php

namespace Fatk\Pilcrow;

use Fatk\Pilcrow\Helpers\PostType;
use Fatk\Pilcrow\Helpers\Post;

use Illuminate\Support\ServiceProvider;

class PilcrowServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pilcrow.php', 'pilcrow');

        // Register the service the package provides.
        $this->app->singleton('pilcrow', function ($app) {
            return new Pilcrow;
        });

        $this->app->singleton(PostType::class, function ($app) {
            return new PostType();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'pilcrow',
            PostType::class
        ];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__ . '/../config/pilcrow.php' => config_path('pilcrow.php'),
        ], 'pilcrow.config');


        // Registering package commands.
        // $this->commands([]);
    }
}
