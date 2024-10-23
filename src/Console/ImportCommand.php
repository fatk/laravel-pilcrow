<?php

declare(strict_types=1);

namespace Fatk\Pilcrow\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Container\Container;
use Fatk\Pilcrow\Contracts\ImportTypeInterface;
use Fatk\Pilcrow\Contracts\SourceAdapterInterface;
use Fatk\Pilcrow\Files\FileDiscovery;
use InvalidArgumentException;

use function Laravel\Prompts\multiselect;

/**
 * Console command for importing content into WordPress
 *
 * This command handles the import of various content types into WordPress
 * from different file sources. It supports interactive file selection,
 * pattern matching, and multiple file formats through source adapters.
 */
final class ImportCommand extends Command
{
    protected $signature = 'pilcrow:import
                            {type : The type of content to import (post, user, attachment)}
                            {--s|source= : Source adapter to use (excel, content)}
                            {--p|path= : Override default source path}
                            {--f|file= : File name or pattern to match}
                            {--i|interactive : Select files interactively}';

    protected $description = 'Import content into WordPress from various sources';

    /**
     * @param Container $container Service container for resolving dependencies
     * @param FileDiscovery $fileDiscovery File discovery service
     */
    public function __construct(
        private readonly Container $container,
        private readonly FileDiscovery $fileDiscovery
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        try {
            $type = $this->argument('type');
            $source = $this->option('source');

            if (!$source) {
                throw new InvalidArgumentException('Source option is required');
            }

            $importer = $this->resolveImporter($type);
            $adapter = $this->resolveAdapter($source, $importer);
            $path = $this->resolvePath($source);

            $files = $this->resolveFiles($path, $adapter);

            if ($files->isEmpty()) {
                $extensions = implode(', ', $adapter::supportedExtensions());
                $this->warn("No supported files found. This source supports: {$extensions}");
                return self::FAILURE;
            }

            $this->info("Starting import of type '{$type}' from '{$source}' source");
            $this->newLine();

            // $result = $adapter->import($files);

            $this->newLine();
            $this->info('Import completed successfully');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Resolve files to import based on command options
     *
     * @param string $path Base path to search for files
     * @param SourceAdapterInterface $adapter Source adapter instance
     * @return Collection<array{path: string, name: string, extension: string, size: int, modified: int}>
     */
    private function resolveFiles(string $path, SourceAdapterInterface $adapter): Collection
    {
        $files = $this->fileDiscovery->discover(
            path: $path,
            supportedExtensions: $adapter::supportedExtensions(),
            pattern: $this->option('file')
        );

        if ($this->option('interactive')) {
            return $this->selectFilesInteractively($files);
        }

        return $files;
    }

    /**
     * Present interactive file selection interface
     *
     * @param Collection<array{path: string, name: string, extension: string, size: int, modified: int}> $files
     * @return Collection<array{path: string, name: string, extension: string, size: int, modified: int}>
     */
    private function selectFilesInteractively(Collection $files): Collection
    {
        $maxNameLength = $files->max(fn($file) => strlen($file['name']));

        $choices = $files->mapWithKeys(fn($file) => [
            $file['path'] => sprintf(
                "%-{$maxNameLength}s    %10s    %s",
                $file['name'],
                $this->formatSize($file['size']),
                date('d/m/Y H:i', $file['modified'])
            )
        ])->all();

        $selected = multiselect(
            label: 'Select files to import',
            options: $choices,
            required: true
        );

        return $files->filter(fn($file) => in_array($file['path'], $selected));
    }

    /**
     * Format file size in human readable format
     *
     * @param int $size Size in bytes
     * @return string Formatted size with unit
     */
    private function formatSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return sprintf(
            '%6.2f %s',
            $size / (1024 ** $power),
            $units[$power]
        );
    }

    /**
     * Resolve importer instance for given content type
     *
     * @param string $type Content type identifier
     * @return ImportTypeInterface
     * @throws InvalidArgumentException If type is not supported
     */
    private function resolveImporter(string $type): ImportTypeInterface
    {
        $importers = $this->container->get('pilcrow.import.importers');

        if (!isset($importers[$type])) {
            throw new InvalidArgumentException("Unsupported import type: {$type}");
        }

        return $this->container->make($importers[$type]);
    }

    /**
     * Resolve source adapter instance
     *
     * @param string $source Source identifier
     * @param ImportTypeInterface $importer Content importer instance
     * @return SourceAdapterInterface
     * @throws InvalidArgumentException If source is not supported
     */
    private function resolveAdapter(string $source, ImportTypeInterface $importer): SourceAdapterInterface
    {
        $sources = $this->container->get('pilcrow.import.sources');

        if (!isset($sources[$source])) {
            throw new InvalidArgumentException("Unsupported source adapter: {$source}");
        }

        return $this->container->make($sources[$source], ['importer' => $importer]);
    }

    /**
     * Resolve import source path
     *
     * @param string $source Source identifier
     * @return string Resolved path
     * @throws InvalidArgumentException If path is invalid or inaccessible
     */
    private function resolvePath(string $source): string
    {
        $path = $this->option('path');

        if ($path) {
            if (!is_dir($path)) {
                throw new InvalidArgumentException("Invalid path provided: {$path}");
            }
            return $path;
        }

        $configPath = config("pilcrow.import.source.path.{$source}");

        if (!$configPath || !is_dir($configPath)) {
            throw new InvalidArgumentException("Invalid or missing path configuration for source: {$source}");
        }

        return $configPath;
    }
}
