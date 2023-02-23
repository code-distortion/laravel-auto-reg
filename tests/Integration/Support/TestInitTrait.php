<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Integration\Support;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\LaravelAutoRegServiceProvider;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Support\Cache;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\LivewireManager;

trait TestInitTrait
{
    /**
     * Generate the "main" cache file path.
     *
     * @return string
     */
    private static function mainCachePath(): string
    {
        return base_path("../../../../tests/workspaces/temp/laravel-auto-reg.php");
    }

    /**
     * Generate the "meta" cache file path.
     *
     * @return string
     */
    private static function metaCachePath(): string
    {
        return base_path("../../../../tests/workspaces/temp/laravel-auto-reg-meta.php");
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // this needs to be registered while testing, otherwise app('livewire') fails
        app()->singleton('livewire', LivewireManager::class);
    }



    /**
     * Set up a new Detect object, ready to test.
     *
     * @param string               $scenario                The name of the scenario to use.
     * @param array<string, mixed> $replaceConfig           Values to replace the config data with.
     * @param boolean              $needMeta                Should the meta-data be generated / loaded from cache?.
     * @param boolean              $removeExistingCacheFile Should the cache file be removed if it exists?.
     * @return mixed[]
     */
    private static function newDetect(
        string $scenario,
        array $replaceConfig = [],
        bool $needMeta = true,
        bool $removeExistingCacheFile = true
    ): array {

        $workspaceDir = str_replace(
            '\\',
            '/',
            (string) realpath(base_path("../../../../tests/workspaces/{$scenario}"))
        );

        if ($removeExistingCacheFile) {
            static::removeCacheFiles([static::mainCachePath(), static::metaCachePath()]);
        }

        $configData = static::replaceConfigData(
            require($workspaceDir . '/config.php'),
            $replaceConfig
        );




        $autoRegDTO = new AutoRegDTO(
            $workspaceDir,
            $configData,
            $needMeta
        );

        $cache = new Cache(
            new Filesystem(),
            static::mainCachePath(),
            static::metaCachePath(),
            $needMeta
        );

        $runInitialisation = true;

        $detect = new Detect($autoRegDTO, $cache, $runInitialisation);

        return [$autoRegDTO, $detect];
    }

    /**
     * Build register and boot a new LaravelAutoRegServiceProvider, with the given populated Detect object.
     *
     * @param Detect $detect The Detect object for the LaravelAutoRegServiceProvider to use.
     * @return LaravelAutoRegServiceProvider
     */
    private static function runServiceProvider(Detect $detect): LaravelAutoRegServiceProvider
    {
        $sp = new LaravelAutoRegServiceProvider(app());
        $sp->overrideWithThisDetect($detect);
        $sp->register();
        $sp->boot();

        return $sp;
    }

    /**
     * Remove the cache file (if it exists).
     *
     * @param string|array<int, string> $paths The path to the cache file.
     * @return void
     */
    private static function removeCacheFiles($paths): void
    {
        $paths = (is_array($paths) ? $paths : [$paths]);
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Update config data with some alterations.
     *
     * @param mixed[]              $configData    The config-data to update.
     * @param array<string, mixed> $replaceConfig The replacement data.
     * @return mixed[]
     */
    private static function replaceConfigData(array $configData, array $replaceConfig): array
    {
        // reset the arrays, one level up from the given keys
        foreach (Arr::dot($replaceConfig) as $key => $value) {
            $parentKey = tap(
                collect(Str::of($key)->explode('.'))
            )->pop()->implode('.');
            Arr::set($configData, $parentKey, []);
        }

        // set the new values
        foreach (Arr::dot($replaceConfig) as $key => $value) {
            Arr::set($configData, $key, $value);
        }

        return $configData;
    }
}
