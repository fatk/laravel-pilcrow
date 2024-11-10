<?php

namespace Fatk\Pilcrow;

use Illuminate\Support\ServiceProvider;
use Fatk\Pilcrow\Console\ImportCommand;
use Fatk\Pilcrow\Importers\{PostImporter, UserImporter, TermImporter, AttachmentImporter};
use Fatk\Pilcrow\SourceAdapters\{ExcelAdapter, ContentAdapter};

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

        $this->registerImporters();
        $this->registerSourceAdapters();

        // Register the service the package provides.
        $this->app->singleton('pilcrow', function ($app) {
            return new Pilcrow;
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
            'pilcrow'
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
        $this->commands([
            ImportCommand::class
        ]);
    }

    private function registerImporters(): void
    {
        $this->app->singleton('pilcrow.import.importers', function () {
            return [
                'post' => PostImporter::class,
                'user' => UserImporter::class,
                'term' => TermImporter::class,
                'attachment' => AttachmentImporter::class,
            ];
        });
    }

    private function registerSourceAdapters(): void
    {
        $this->app->singleton('pilcrow.import.sources', function () {
            return [
                'excel' => ExcelAdapter::class,
                'content' => ContentAdapter::class,
            ];
        });
    }
}
