<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Integration;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Support\Monitor;
use CodeDistortion\LaravelAutoReg\Tests\Integration\Support\TestInitTrait;
use CodeDistortion\LaravelAutoReg\Tests\LaravelTestCase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;

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
     * @test
     *
     * @return void
     */
    #[Test]
    public static function test_the_service_provider_registers_everything(): void
    {
        [$autoRegDTO, $detect] = static::newDetect('scenario1');
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */

        static::runServiceProvider($detect);

        $timers = app(Monitor::class);



        // test config
        static::assertSame('test config 1', config('my_app1::test_config1.something'));
        static::assertSame('test config 2', config('my_app1::test_config2.something'));

        // test translations
        static::assertSame('HELLO TRANS 1 - ENGLISH', trans('my_app1::translations1.hello'));
        static::assertSame('HELLO TRANS 2 - ENGLISH', trans('my_app1::sub_dir1/sub_dir2/translations2.hello'));

        // test views
        $output = view('my-app1::blade-template-with-the-lot')->render();
        static::assertStringContainsString('BLADE TEMPLATE WITH THE LOT', $output);
        static::assertStringContainsString('BLADE TEMPLATE 1', $output);
        static::assertStringContainsString('BLADE TEMPLATE 2', $output);
        static::assertStringContainsString('ANONYMOUS COMPONENT 1', $output);
        static::assertStringContainsString('ANONYMOUS COMPONENT 2', $output);
        static::assertStringContainsString('VIEW COMPONENT 1', $output);
        static::assertStringContainsString('VIEW COMPONENT 2', $output);
//        static::assertStringContainsString('LIVEWIRE COMPONENT 1', $output); // @todo
//        static::assertStringContainsString('LIVEWIRE COMPONENT 2', $output); // @todo
        // access a blade template in a sub-directory
        $output = view('my-app1::sub-dir1.sub-dir2.blade-template2')->render();
        static::assertStringContainsString('BLADE TEMPLATE 2', $output);

        // test web-routes
        static::assertStringEndsWith('/web-route-1', route('web-route-1'));
        static::assertStringEndsWith('/api-route-1', route('api-route-1'));

        // test broadcast
        static::assertTrue($timers->didThisRun('channels.php'));

        // test console commands - closure
        Artisan::call('test:test-closure-command-1');
        static::assertStringContainsString('TEST CLOSURE COMMAND 1', Artisan::output());

        // command classes
        Artisan::call('test:test-command1');
        static::assertStringContainsString('TEST COMMAND 1', Artisan::output());
        Artisan::call('test:test-command2');
        static::assertStringContainsString('TEST COMMAND 2', Artisan::output());

        // test migrations
        // @todo

        // test service-providers
        static::assertTrue($timers->didThisRun('TestServiceProvider1'));
        static::assertTrue($timers->didThisRun('TestServiceProvider2'));
    }
}
