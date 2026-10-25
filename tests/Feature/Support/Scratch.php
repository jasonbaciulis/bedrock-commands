<?php

declare(strict_types=1);

namespace Bedrock\Commands\Tests\Feature\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Path;
use Statamic\Facades\Stache;
use Statamic\Fields\FieldsetRepository;
use Statamic\Stache\Stores\Store;
use Symfony\Component\Finder\SplFileInfo;

/**
 * The isolated workspace the console command tests run against, so the commands
 * mutate copies instead of the real kit.
 */
final class Scratch
{
    /**
     * Fieldsets directory the Statamic fieldset repository points at during
     * scaffold tests.
     */
    public static function fieldsetsPath(): string
    {
        return self::path().'/fieldsets';
    }

    /**
     * Provision an isolated scaffold workspace (fieldsets + views), point the
     * Statamic fieldset repository at it, and rebind the bedrock-commands.*
     * config so the commands under test write into it. The fixture parent
     * fieldsets are copied in as seeds.
     */
    public static function setUpScaffoldWorkspace(): void
    {
        $blocksViews = self::path().'/views/blocks';
        $setsViews = self::path().'/views/sets';

        File::ensureDirectoryExists(self::fieldsetsPath());
        File::ensureDirectoryExists($blocksViews);
        File::ensureDirectoryExists($setsViews);

        File::copyDirectory(self::fixturesPath().'/fieldsets', self::fieldsetsPath());

        Fieldset::setDirectory(self::fieldsetsPath());

        config([
            'bedrock-commands.blocks_views_path' => $blocksViews,
            'bedrock-commands.sets_views_path' => $setsViews,
        ]);
    }

    public static function resetFieldsetRepository(): void
    {
        Fieldset::swap((new FieldsetRepository)->setDirectory(self::fieldsetsPath()));
    }

    /**
     * Copy the content tree into this worker's workspace and repoint every
     * Stache store at the copy. Parallel workers otherwise seed and delete
     * entries in one shared directory, and a Stache traversal that lists that
     * directory then stats a file another worker has just removed dies.
     */
    public static function isolateContentTree(): void
    {
        $fixtureContentPath = self::fixturesPath().'/content';
        $contentPath = self::contentPath();

        self::copyContentFiles($fixtureContentPath, $contentPath);

        // AddonTestCase configures the stores as src/../tests/__fixtures__, so the
        // directories only match the fixture path once they are resolved.
        Stache::stores()
            ->filter(fn (Store $store): bool => Str::startsWith(Path::resolve($store->directory()), $fixtureContentPath))
            ->each(fn (Store $store) => $store->directory(
                Str::replaceStart($fixtureContentPath, $contentPath, Path::resolve($store->directory()))
            ));

        Stache::clear();
    }

    /**
     * The published stub goes inside this worker's workspace, so the parallel
     * workers that test the bundled stubs never read it.
     */
    public static function publishStub(string $stub, string $contents): void
    {
        app()->setBasePath(self::path().'/base');

        File::ensureDirectoryExists(base_path('stubs/bedrock-commands'));
        File::put(base_path("stubs/bedrock-commands/{$stub}"), $contents);
    }

    public static function delete(): void
    {
        File::deleteDirectory(self::path());
    }

    private static function path(): string
    {
        return storage_path('framework/testing/bedrock-'.ParallelWorker::id());
    }

    private static function fixturesPath(): string
    {
        return dirname(__DIR__, 2).'/__fixtures__';
    }

    private static function contentPath(): string
    {
        return self::path().'/content-root/content';
    }

    /**
     * Files a previous run left behind are skipped, so a crashed run cannot
     * seed the next one with stray entries.
     */
    private static function copyContentFiles(string $source, string $destination): void
    {
        collect(File::allFiles($source))
            ->reject(fn (SplFileInfo $file): bool => Str::startsWith($file->getFilename(), 'test-'))
            ->each(function (SplFileInfo $file) use ($destination): void {
                $target = $destination.'/'.$file->getRelativePathname();

                File::ensureDirectoryExists(dirname($target));
                File::copy($file->getPathname(), $target);
            });
    }
}
