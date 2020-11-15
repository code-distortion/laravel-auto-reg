<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Integration;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Support\Monitor;
use CodeDistortion\LaravelAutoReg\Tests\Integration\Support\TestInitTrait;
use CodeDistortion\LaravelAutoReg\Tests\LaravelTestCase;
use Illuminate\Support\Facades\Artisan;

/**
 * Test the LaravelAutoRegServiceProvider class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelAutoRegServiceProviderTest extends LaravelTestCase
{
    use TestInitTrait;



    /**
     * Test that the LaravelAutoRegServiceProvider registers everything it's supposed to.
     *
     * @return void
     */
    public function test_the_service_provider_registers_everything(): void
    {
        [$autoRegDTO, $detect] = $this->newDetect('scenario1');
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */

        $this->runServiceProvider($detect);

        $timers = app(Monitor::class);



        // test config
        $this->assertSame('test config 1', config('my_app1::test_config1.something'));
        $this->assertSame('test config 2', config('my_app1::test_config2.something'));

        // test translations
        $this->assertSame('HELLO TRANS 1 - ENGLISH', trans('my_app1::translations1.hello'));
        $this->assertSame('HELLO TRANS 2 - ENGLISH', trans('my_app1::sub_dir1/sub_dir2/translations2.hello'));

        // test views
        $output = view('my-app1::blade-template-with-the-lot')->render();
        $this->assertStringContainsString('BLADE TEMPLATE WITH THE LOT', $output);
        $this->assertStringContainsString('BLADE TEMPLATE 1', $output);
        $this->assertStringContainsString('BLADE TEMPLATE 2', $output);
        $this->assertStringContainsString('ANONYMOUS COMPONENT 1', $output);
        $this->assertStringContainsString('ANONYMOUS COMPONENT 2', $output);
        $this->assertStringContainsString('VIEW COMPONENT 1', $output);
        $this->assertStringContainsString('VIEW COMPONENT 2', $output);
//        $this->assertStringContainsString('LIVEWIRE COMPONENT 1', $output); // @todo
//        $this->assertStringContainsString('LIVEWIRE COMPONENT 2', $output); // @todo
        // access a blade template in a sub-directory
        $output = view('my-app1::sub-dir1.sub-dir2.blade-template2')->render();
        $this->assertStringContainsString('BLADE TEMPLATE 2', $output);

        // test web-routes
        $this->assertStringEndsWith('/web-route-1', route('web-route-1'));
        $this->assertStringEndsWith('/api-route-1', route('api-route-1'));

        // test broadcast
        $this->assertTrue($timers->didThisRun('channels.php'));

        // test console commands - closure
        Artisan::call('test:test-closure-command-1');
        $this->assertStringContainsString('TEST CLOSURE COMMAND 1', Artisan::output());

        // command classes
        Artisan::call('test:test-command1');
        $this->assertStringContainsString('TEST COMMAND 1', Artisan::output());
        Artisan::call('test:test-command2');
        $this->assertStringContainsString('TEST COMMAND 2', Artisan::output());

        // test migrations
        // @todo

        // test service-providers
        $this->assertTrue($timers->didThisRun('TestServiceProvider1'));
        $this->assertTrue($timers->didThisRun('TestServiceProvider2'));
    }
}
