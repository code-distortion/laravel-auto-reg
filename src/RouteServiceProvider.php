<?php

namespace CodeDistortion\LaravelAutoReg;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Support\Monitor;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Register the detected routes.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * This registers the routes because they don't get registered when attempted in LaravelAutoRegServiceProvider
     * (even when it extends the RouteServiceProvider).
     *
     * @return void
     */
    public function boot(): void
    {
        $detect = app(Detect::class);
        $monitor = app(Monitor::class);

        if ($detect->resourceEnabled(Settings::TYPE__ROUTE_API_FILE)) {
            $monitor->timerExists('register ' . Settings::TYPE__ROUTE_API_FILE);
        }
        if ($detect->resourceEnabled(Settings::TYPE__ROUTE_WEB_FILE)) {
            $monitor->timerExists('register ' . Settings::TYPE__ROUTE_WEB_FILE);
        }

        $this->routes(function () {
            $this->registerApiRoutes();
            $this->registerWebRoutes();
        });
    }

    /**
     * Register the API routes.
     *
     * @return void
     */
    private function registerApiRoutes(): void
    {
        $detect = app(Detect::class);
        $monitor = app(Monitor::class);

        $type = Settings::TYPE__ROUTE_API_FILE;
        if (!$detect->resourceEnabled($type)) {
            return;
        }

        $monitor->startTimer('register ' . $type);

        foreach ($detect->getRouteApiFiles() as $path) {
            Route::middleware($detect->routeApiMiddleware())
                ->group($detect->basePath($path));
        }
        $monitor->incRegCount($type, count($detect->getRouteApiFiles()));

        $monitor->stopTimer('register ' . $type);
    }

    /**
     * Register the WEB routes.
     *
     * @return void
     */
    private function registerWebRoutes(): void
    {
        $detect = app(Detect::class);
        $monitor = app(Monitor::class);

        $type = Settings::TYPE__ROUTE_WEB_FILE;
        if (!$detect->resourceEnabled($type)) {
            return;
        }

        $monitor->startTimer('register ' . $type);

        foreach ($detect->getRouteWebFiles() as $path) {
            Route::middleware($detect->routeWebMiddleware())
                ->group($detect->basePath($path));
        }
        $monitor->incRegCount($type, count($detect->getRouteWebFiles()));

        $monitor->stopTimer('register ' . $type);
    }
}
