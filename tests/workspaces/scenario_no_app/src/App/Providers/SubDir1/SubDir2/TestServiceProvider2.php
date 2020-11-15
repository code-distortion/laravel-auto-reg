<?php

namespace CodeDistortion\LaravelAutoReg\Tests\ScenarioNoApp\Providers\SubDir1\SubDir2;

use CodeDistortion\LaravelAutoReg\Support\Monitor;
use Illuminate\Support\ServiceProvider;

/**
 * A service-provider for testing purposes.
 */
class TestServiceProvider2 extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        app(Monitor::class)->iRan('TestServiceProvider2');
    }
}
