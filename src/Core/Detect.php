<?php

namespace CodeDistortion\LaravelAutoReg\Core;

use CodeDistortion\LaravelAutoReg\Core\Resolvers\BroadcastClosureResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\CommandClassResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\CommandClosureFileResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\ConfigFileResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\LivewireComponentResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\MigrationDirResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\RouteApiClosureFileResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\RouteWebClosureFileResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\ServiceProviderClassResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\TranslationDirResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\ViewComponentResolver;
use CodeDistortion\LaravelAutoReg\Core\Resolvers\ViewDirectoryResolver;
use CodeDistortion\LaravelAutoReg\Exceptions\CacheExistsButCouldNotBeReadException;
use CodeDistortion\LaravelAutoReg\Exceptions\FilesystemException;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Support\Cache;
use CodeDistortion\LaravelAutoReg\Support\FileList;
use CodeDistortion\LaravelAutoReg\Support\PathPattern;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Broadcast;

/**
 * Resolve the user-land resources so they can be registered.
 */
class Detect
{
    /** @var AutoRegDTO The object representing this package's config values. */
    private AutoRegDTO $autoRegDTO;

    /** @var Cache Cache management object. */
    private Cache $cache;

    /** @var boolean Whether the data was loaded from cache or not. */
    private bool $wasLoadedFromCache = false;

    /** @var Collection<FileList> An internal cache of the files in the "source-dir" dirs and their FQCN's. */
    private Collection $allFiles;



    /**
     * Constructor.
     *
     * @param AutoRegDTO $autoRegDTO This package's config settings.
     * @param Cache      $cache      Cache management object.
     * @param boolean    $initialise Should the config be loaded automatically?.
     */
    public function __construct(AutoRegDTO $autoRegDTO, Cache $cache, bool $initialise = true)
    {
        $this->autoRegDTO = $autoRegDTO;
        $this->cache = $cache;

        if ($initialise) {
            $this->initialise();
        }
    }



    /**
     * Saves a cache of the currently loaded settings.
     *
     * @return void
     */
    public function saveCache(): void
    {
        $this->cache->save(
            $this->autoRegDTO->cacheableMainData(),
            $this->autoRegDTO->cacheableMetaData()
        );
    }

    /**
     * Removes the cache (if it exists).
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * Check if the data was loaded from cache.
     *
     * @return boolean
     */
    public function wasLoadedFromCache(): bool
    {
        return $this->wasLoadedFromCache;
    }

    /**
     * Initialise this object's values.
     *
     * @return void
     */
    private function initialise(): void
    {
        $mainCacheData = $metaCacheData = null;
        try {
            $cacheExisted = $this->cache->load($mainCacheData, $metaCacheData);
        } catch (CacheExistsButCouldNotBeReadException $e) {
            $cacheExisted = true;
        }

        $this->wasLoadedFromCache = $this->hydrateFromCacheData($mainCacheData, $metaCacheData);
        if ($this->wasLoadedFromCache) {
            return;
        }

        $needMeta = $this->autoRegDTO->getNeedMeta() || $cacheExisted;
        $this->loadFresh($needMeta);

        if ($cacheExisted) {
            $this->saveCache();
        }
    }

    /**
     * Use the loaded data (and re-cache when the version doesn't match).
     *
     * @param array<string, mixed>|null $mainCacheData The cached "main" data.
     * @param array<string, mixed>|null $metaCacheData The cached "meta" data.
     * @return boolean
     */
    private function hydrateFromCacheData(?array $mainCacheData, ?array $metaCacheData): bool
    {
        if (!$this->autoRegDTO->isMainContentOk($mainCacheData)) {
            return false;
        }

        if (!$this->autoRegDTO->isMetaContentOk($metaCacheData)) {
            return false;
        }

        $mainCacheData = (is_array($mainCacheData) ? $mainCacheData : []); // for phpstan
        $this->autoRegDTO->hydrateFromCache($mainCacheData, $metaCacheData);
        return true;
    }

    /**
     * Resolve the data fresh.
     *
     * @param boolean $needMeta Should the meta-data be generated?.
     * @return void
     */
    public function loadFresh(bool $needMeta): void
    {
        $resolvers = [
            new BroadcastClosureResolver(
                $this->buildPathPatterns('patterns.broadcast'),
                $this->autoRegDTO->config('enabled.broadcast', false) // is disabled by default - because it is slower
            ),

            new CommandClassResolver(
                $this->buildPathPatterns('patterns.command_classes'),
                $this->autoRegDTO->config('enabled.command_classes', true)
            ),

            new CommandClosureFileResolver(
                $this->buildPathPatterns('patterns.command_closures'),
                $this->autoRegDTO->config('enabled.command_closures', true)
            ),

            (new ConfigFileResolver(
                $this->buildPathPatterns('patterns.configs'),
                $this->autoRegDTO->config('enabled.configs', true)
            ))
                ->setUseAppName($this->autoRegDTO->config('settings.configs.use_app_name', true))
                ->setPathCaseType($this->autoRegDTO->config('settings.configs.path_case', 'snake')),

            new LivewireComponentResolver(
                $this->buildPathPatterns('patterns.livewire'),
                $this->autoRegDTO->config('enabled.livewire', true)
            ),

            new MigrationDirResolver(
                $this->buildPathPatterns('patterns.migrations'),
                $this->autoRegDTO->config('enabled.migrations', true)
            ),

            new RouteApiClosureFileResolver(
                $this->buildPathPatterns('patterns.routes_api'),
                $this->autoRegDTO->config('enabled.routes_api', true)
            ),

            new RouteWebClosureFileResolver(
                $this->buildPathPatterns('patterns.routes_web'),
                $this->autoRegDTO->config('enabled.routes_web', true)
            ),

            new ServiceProviderClassResolver(
                $this->buildPathPatterns('patterns.service_providers'),
                $this->autoRegDTO->config('enabled.service_providers', true)
            ),

            (new TranslationDirResolver(
                $this->buildPathPatterns('patterns.translations'),
                $this->autoRegDTO->config('enabled.translations', true)
            ))
                ->setPathCaseType($this->autoRegDTO->config('settings.translations.path_case', 'snake')),

            new ViewComponentResolver(
                $this->buildPathPatterns('patterns.view_components'),
                $this->autoRegDTO->config('enabled.view_components', true)
            ),

            (new ViewDirectoryResolver(
                $this->buildPathPatterns('patterns.view_templates'),
                $this->autoRegDTO->config('enabled.view_templates', true)
            ))
                ->setPathCaseType($this->autoRegDTO->config('settings.views.templates.path_case', 'snake')),
        ];

        /** @var ResolverAbstract $resolver */
        foreach ($resolvers as $resolver) {

            $resolver->resolve(
                $this->getAllFilesList(),
                $this->getDetectedAppList(),
                $needMeta
            );

            $this->autoRegDTO->setResolved(
                $resolver->getType(),
                $resolver->getRegData(),
                $resolver->getMetaData()
            );
        }
//        dd($this->getAllFilesList());
//        dd($this->autoRegDTO);
    }


    /**
     * Build a collection of path-pattern objects.
     *
     * @param string $configKey The key use when getting the path-patterns from the config.
     * @return Collection<PathPattern>
     */
    private function buildPathPatterns(string $configKey)
    {
        $patterns = $this->autoRegDTO->config($configKey, []);
        return collect(Arr::wrap($patterns))
            ->map(fn(string $pattern) => new PathPattern($pattern));
    }

    /**
     * Get the list of files and their FQCN (when available).
     *
     * The list is cached in PHP memory so the files are only inspected once.
     *
     * @return Collection<FileList>
     */
    private function getAllFilesList(): Collection
    {
        return $this->allFiles ??= $this->resolveAllFilesList();
    }

    /**
     * Build the list of files and their FQCN (when available).
     *
     * @return Collection<FileList>
     */
    private function resolveAllFilesList(): Collection
    {
        $sourceDirs = Arr::wrap($this->autoRegDTO->config('source_dir'));

        return collect($sourceDirs)
            ->unique()
            ->map(fn($sourceDir) => str_replace('\\', '/', $sourceDir))
            ->map(function ($sourceDir) {
                $resolvedSourceDir = str_replace('\\', '/', (string) realpath($sourceDir));
                if (!mb_strlen($resolvedSourceDir)) {
                    throw FilesystemException::sourceDirNotFound($sourceDir);
                }
                return $resolvedSourceDir;
            })
//            ->filter() // skip when the directory wasn't found
            ->map(
                fn($sourceDir, $sourceName) => new FileList(
                    $this->autoRegDTO->laravelBaseDir,
                    $sourceDir,
                    is_string($sourceName) ? $sourceName : null,
                    $this->autoRegDTO->config('ignore', [])
                )
            );
    }


    /**
     * Determine if a given resource-type is enabled.
     *
     * @param string $name The resource-type to check.
     * @return boolean
     */
    public function resourceEnabled(string $name): bool
    {
        return $this->autoRegDTO->config('enabled.' . Settings::TYPE_TO_CONFIG_NAME_MAP[$name]);
    }



    /**
     * Resolve the list of apps that have been detected.
     *
     * @return string[]
     */
    private function getDetectedAppList(): array
    {
        return $this->autoRegDTO->getAllMeta()->flatten(1)->pluck('app')->unique()->values()->all();
    }

    /**
     * Retrieve all the meta information about the detected resources.
     *
     * @return Collection|mixed[]
     */
    public function getAllMeta(): Collection
    {
        return $this->autoRegDTO->getAllMeta();
    }

    /**
     * Check if any resources were detected.
     *
     * @return boolean
     */
    public function resourcesWereDetected()
    {
        return $this->autoRegDTO->getAllMeta()->flatten(1)->isNotEmpty();
    }



    /**
     * Retrieve the list of broadcast-closure-files.
     *
     * @return string[]
     */
    public function getBroadcastClosureFiles(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__BROADCAST_CLOSURE_FILE);
    }

    /**
     * Retrieve the list of command-classes.
     *
     * @return string[]
     */
    public function getCommandClasses(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__COMMAND_CLASS);
    }

    /**
     * Retrieve the list of command-closure-files.
     *
     * @return string[]
     */
    public function getCommandClosureFiles(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__COMMAND_CLOSURE_FILE);
    }

    /**
     * Retrieve the list of config-files.
     *
     * @return string[]
     */
    public function getConfigFiles(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__CONFIG_FILE);
    }

    /**
     * Retrieve the list of livewire-component-classes.
     *
     * @return string[]
     */
    public function getLivewireComponentClasses(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__LIVEWIRE_COMPONENT_CLASS);
    }

    /**
     * Retrieve the list of migration-directories.
     *
     * @return string[]
     */
    public function getMigrationDirectories(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__MIGRATION_DIRECTORY);
    }

    /**
     * Retrieve the list of api-route-files.
     *
     * @return string[]
     */
    public function getRouteApiFiles(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__ROUTE_API_FILE);
    }

    /**
     * Retrieve the list of web-route-files.
     *
     * @return string[]
     */
    public function getRouteWebFiles(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__ROUTE_WEB_FILE);
    }

    /**
     * Retrieve the list of service-provider-classes.
     *
     * @return string[]
     */
    public function getServiceProviderClasses(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__SERVICE_PROVIDER_CLASS);
    }

    /**
     * Retrieve the list of translation-directories.
     *
     * @return string[]
     */
    public function getTranslationDirectories(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__TRANSLATION_DIRECTORY);
    }

    /**
     * Retrieve the list of view-component-classes.
     *
     * @return string[]
     */
    public function getViewComponentClasses(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__VIEW_COMPONENT_CLASS);
    }

    /**
     * Retrieve the list of view-directories.
     *
     * @return string[]
     */
    public function getViewDirectories(): array
    {
        return $this->autoRegDTO->getResolved(Settings::TYPE__VIEW_DIRECTORY);
    }



    /**
     * Retrieve the middleware for api routes to use.
     *
     * @return string[]
     */
    public function routeApiMiddleware(): array
    {
        return $this->autoRegDTO->config('settings.routes.api.middleware', ['api']);
    }

    /**
     * Retrieve the middleware for web routes to use.
     *
     * @return string[]
     */
    public function routeWebMiddleware(): array
    {
        return $this->autoRegDTO->config('settings.routes.web.middleware', ['web']);
    }

    /**
     * Check if the Broadcast::routes(); should be run.
     *
     * @see Broadcast::routes();
     * @return boolean
     */
    public function shouldRegisterBroadcastRoutes(): bool
    {
        if (!$this->autoRegDTO->config('enabled.broadcast', false)) {
            return false;
        }

        if (!$this->autoRegDTO->config('settings.broadcast.run_auth', false)) {
            return false;
        }

        return true;
    }

    /**
     * Get the "main" cache file path.
     *
     * @return string
     */
    public function getMainCachePath(): string
    {
        return $this->cache->getMainCachePath();
    }

    /**
     * Get the "meta" cache file path.
     *
     * @return string
     */
    public function getMetaCachePath(): string
    {
        return $this->cache->getMetaCachePath();
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @param string|null $path The path to add.
     * @return string
     */
    public function basePath(?string $path = ''): string
    {
        return $this->autoRegDTO->laravelBaseDir . ($path ? '/' . $path : $path);
    }
}
