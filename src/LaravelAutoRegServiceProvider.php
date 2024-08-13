<?php

namespace CodeDistortion\LaravelAutoReg;

use Closure;
use CodeDistortion\LaravelAutoReg\Commands\CacheCommand;
use CodeDistortion\LaravelAutoReg\Commands\ClearCommand;
use CodeDistortion\LaravelAutoReg\Commands\ListCommand;
use CodeDistortion\LaravelAutoReg\Commands\StatsCommand;
use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Support\Cache;
use CodeDistortion\LaravelAutoReg\Support\Environment;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Support\Monitor;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * LaravelAutoReg service-provider.
 */
class LaravelAutoRegServiceProvider extends ServiceProvider
{
    /** @var Detect A neater reference to this object. */
    private Detect $detect;

    /** @var Detect|null Used when testing - a Detect object to use instead of creating a new one. */
    private ?Detect $overrideDetect = null;

    /** @var Monitor A neater reference to this object. */
    private Monitor $monitor;



    /**
     * Service-provider register method.
     *
     * @return void
     */
    public function register(): void
    {
        $this->initialiseConfigFile();
        $this->registerServices();


        $this->monitor = app(Monitor::class);

        $this->monitor->startTimer('resource detection');
        $this->detect = app(Detect::class);
        $this->monitor->stopTimer('resource detection');
    }

    /**
     * Service-provider boot method.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerCommands();

        // don't register resources when creating or clearing the cache
        // in case the resources that were cached are invalid in some way
        // (e.g. if a file doesn't exist any more)
        if (Environment::runningAutoRegCacheCommand()) {
            return;
        }

        $this->monitor->startTimer('total');

        // more important ones - that may be used by others below
        $this->registerUserConfigFiles();
        $this->registerUserServiceProviders();

        // the rest
        $this->registerRouteServiceProvider();
        $this->registerUserCommandClosures();
        $this->registerUserCommandClasses();
        $this->registerUserBroadcasts();
        $this->registerUserMigrations();
        $this->registerUserViewTemplates();
        $this->registerUserViewComponents();
        $this->registerUserLivewire();
        $this->registerUserTranslationDirs();

        $this->monitor->stopTimer('total');
    }


    /**
     * Set the Detect object to use instead of creating one automatically.
     *
     * (This is only used in testing).
     *
     * @internal
     *
     * @param Detect $detect The Detect object to use.
     * @return void
     */
    public function overrideWithThisDetect(Detect $detect): void
    {
        $this->overrideDetect = $detect;
    }



    /**
     * Initialise the config file.
     *
     * @return void
     */
    private function initialiseConfigFile(): void
    {
        // initialise the config
        $configPath = __DIR__ . '/../config/config.php';
        $this->mergeConfigFrom($configPath, Settings::LARAVEL_CONFIG_NAME);

        // allow the default config to be published
        if ((!$this->app->runningUnitTests()) && ($this->app->runningInConsole())) {

            $this->publishes(
                [$configPath => config_path(Settings::LARAVEL_CONFIG_NAME . '.php')],
                'config'
            );
        }
    }

    /**
     * Register the services with the service-container used by THIS package.
     *
     * @return void
     */
    private function registerServices(): void
    {
        $this->app->singleton(Detect::class, $this->makeDetectCallback());
        $this->app->singleton(Monitor::class, fn() => new Monitor());
    }

    /**
     * Build a callback that creates a new detect object.
     *
     * @return Closure
     */
    private function makeDetectCallback(): Closure
    {
        // use the existing Detect object if it's already set
        // (only used in testing)
        if ($this->overrideDetect) {
            return fn() => $this->overrideDetect;
        }

        // build a new one
        return function () {

            $needMeta = Environment::runningAutoRegListCommand();
            $runInitialisation = !Environment::runningAutoRegCacheCommand();

            $autoRegDTO = new AutoRegDTO(
                base_path(),
                config(Settings::LARAVEL_CONFIG_NAME),
                $needMeta
            );

            $cacheDir = Environment::isOnVapor() ? '/tmp/storage/bootstrap/cache' : $this->app->bootstrapPath('cache');
            $cache = new Cache(
                new Filesystem(),
                "$cacheDir/laravel-auto-reg.php",
                "$cacheDir/laravel-auto-reg-meta.php",
                $needMeta
            );

            return new Detect($autoRegDTO, $cache, $runInitialisation);
        };
    }

    /**
     * Register this package's commands.
     *
     * @return void
     */
    private function registerCommands(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CacheCommand::class,
            ClearCommand::class,
            ListCommand::class,
            StatsCommand::class,
        ]);
    }



    /**
     * Register the user-space migrations.
     *
     * @return void
     */
    private function registerUserBroadcasts(): void
    {
        $type = Settings::TYPE__BROADCAST_CLOSURE_FILE;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

        // register the broadcast routes automatically
        if ($this->detect->shouldRegisterBroadcastRoutes()) {
            Broadcast::routes();
        }

        // include "…/channels.php" files
        foreach ($this->detect->getBroadcastClosureFiles() as $path) {
            require $this->detect->basePath($path);
        }
        $this->monitor->incRegCount(
            $type,
            count($this->detect->getBroadcastClosureFiles())
        );

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space command-classes.
     *
     * @return void
     */
    private function registerUserCommandClasses(): void
    {
        $type = Settings::TYPE__COMMAND_CLASS;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->timerExists('register ' . $type);

        if (!$this->app->runningInConsole()) {
            return;
        }

        // register command classes
        $this->monitor->startTimer('register ' . $type);

        $this->commands($this->detect->getCommandClasses());
        $this->monitor->incRegCount($type, count($this->detect->getCommandClasses()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space command-closures.
     *
     * @return void
     */
    private function registerUserCommandClosures(): void
    {
        $type = Settings::TYPE__COMMAND_CLOSURE_FILE;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->timerExists('register ' . $type);

        if (!$this->app->runningInConsole()) {
            return;
        }

        // include "…/console.php" files
        $this->monitor->startTimer('register ' . $type);

        foreach ($this->detect->getCommandClosureFiles() as $path) {
            require $this->detect->basePath($path);
        }
        $this->monitor->incRegCount($type, count($this->detect->getCommandClosureFiles()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space config files with Laravel's config.
     *
     * @return void
     */
    private function registerUserConfigFiles(): void
    {
        $type = Settings::TYPE__CONFIG_FILE;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

        foreach ($this->detect->getConfigFiles() as $name => $path) {
            $this->mergeConfigFrom($this->detect->basePath($path), $name);
        }
        $this->monitor->incRegCount($type, count($this->detect->getConfigFiles()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space livewire-components.
     *
     * @return void
     */
    private function registerUserLivewire(): void
    {
        if (!class_exists(Livewire::class)) {
            return;
        }

        $type = Settings::TYPE__LIVEWIRE_COMPONENT_CLASS;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

        foreach ($this->detect->getLivewireComponentClasses() as $name => $fqcn) {
            app('livewire')->component($name, $fqcn);
        }
        $this->monitor->incRegCount($type, count($this->detect->getLivewireComponentClasses()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space migrations.
     *
     * @return void
     */
    private function registerUserMigrations(): void
    {
        $type = Settings::TYPE__MIGRATION_DIRECTORY;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

        foreach ($this->detect->getMigrationDirectories() as $dir) {
            $this->loadMigrationsFrom($this->detect->basePath($dir));
        }
        $this->monitor->incRegCount($type, count($this->detect->getMigrationDirectories()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the route-service-provider - to register the user-space routes.
     *
     * Register the routes in a dedicated RouteServiceProvider because they don't get registered when attempted
     * here (even when this class extends the RouteServiceProvider).
     *
     * @return void
     */
    private function registerRouteServiceProvider(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register the user-space translations.
     *
     * @return void
     */
    private function registerUserTranslationDirs(): void
    {
        $type = Settings::TYPE__TRANSLATION_DIRECTORY;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

        foreach ($this->detect->getTranslationDirectories() as $namespace => $dir) {
            $this->loadTranslationsFrom($this->detect->basePath($dir), $namespace);
        }
        $this->monitor->incRegCount($type, count($this->detect->getTranslationDirectories()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space service-providers.
     *
     * @return void
     */
    private function registerUserServiceProviders(): void
    {
        $type = Settings::TYPE__SERVICE_PROVIDER_CLASS;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

        foreach ($this->detect->getServiceProviderClasses() as $fqcn) {
            $this->app->register($fqcn);
        }
        $this->monitor->incRegCount($type, count($this->detect->getServiceProviderClasses()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space view-components.
     *
     * @return void
     */
    private function registerUserViewComponents(): void
    {
        $type = Settings::TYPE__VIEW_COMPONENT_CLASS;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

//        foreach ($this->detect->getViewComponentClasses() as $prefix => $fqcns) {
//            $this->loadViewComponentsAs($prefix, $fqcns);
//        }
        foreach ($this->detect->getViewComponentClasses() as [$prefix, $namespace]) {
            $namespace = ltrim($namespace, '\\');
            Blade::componentNamespace($namespace, $prefix);
        }
        $this->monitor->incRegCount($type, count($this->detect->getViewComponentClasses()));

        $this->monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the user-space views.
     *
     * @return void
     */
    private function registerUserViewTemplates(): void
    {
        $type = Settings::TYPE__VIEW_DIRECTORY;
        if (!$this->detect->resourceEnabled($type)) {
            return;
        }

        $this->monitor->startTimer('register ' . $type);

        foreach ($this->detect->getViewDirectories() as $namespace => $dir) {
            $this->loadViewsFrom($this->detect->basePath($dir), $namespace);
        }
        $this->monitor->incRegCount($type, count($this->detect->getViewDirectories()));

        $this->monitor->stopTimer('register ' . $type);
    }
}
