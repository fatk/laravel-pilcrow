<?php

namespace Fatk\WpKit;

use Fatk\WpKit\Helpers\PostType;
use Fatk\WpKit\Helpers\Post;

use Illuminate\Support\ServiceProvider;

class WpKitServiceProvider extends ServiceProvider
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
        $this->mergeConfigFrom(__DIR__ . '/../config/wp-kit.php', 'wp-kit');

        // Register the service the package provides.
        $this->app->singleton('wp-kit', function ($app) {
            return new WpKit;
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
            'wp-kit',
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
            __DIR__ . '/../config/wp-kit.php' => config_path('wp-kit.php'),
        ], 'wp-kit.config');


        // Registering package commands.
        // $this->commands([]);
    }
}
