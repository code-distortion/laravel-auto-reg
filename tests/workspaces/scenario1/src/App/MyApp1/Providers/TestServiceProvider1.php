<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Scenario1App\MyApp1\Providers;

use CodeDistortion\LaravelAutoReg\Support\Monitor;
use Illuminate\Support\ServiceProvider;

/**
 * A service-provider for testing purposes.
 */
class TestServiceProvider1 extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        app(Monitor::class)->iRan('TestServiceProvider1');
    }
}
