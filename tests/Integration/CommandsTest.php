<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Integration;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Tests\Integration\Support\TestInitTrait;
use CodeDistortion\LaravelAutoReg\Tests\LaravelTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Test the Laravel Auto-Reg commands.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CommandsTest extends LaravelTestCase
{
    use TestInitTrait;



    /**
     * Test that the auto-reg:list command runs properly.
     *
     * @test
     * @return void
     */
    public function test_command_list(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = $this->newDetect('scenario1');
        $this->runServiceProvider($detect);

        Artisan::call('auto-reg:list');
        $this->assertSame(
            "
Laravel Auto-Reg cache status: NOT CACHED

Source: /src/App

+---------+------------------+---------------------------------------------------------------------------+-------------------------------------------------------------+
| App     | Type             | File / directory                                                          | Usage example                                               |
+---------+------------------+---------------------------------------------------------------------------+-------------------------------------------------------------+
| my_app1 | broadcast        | /src/App/MyApp1/Routes/channels.php                                       |                                                             |
| my_app1 | command          | /src/App/MyApp1/Commands/SubDir1/SubDir2/TestCommand2.php                 | php artisan test:test-command2                              |
| my_app1 | command          | /src/App/MyApp1/Commands/TestCommand1.php                                 | php artisan test:test-command1                              |
| my_app1 | command-closure  | /src/App/MyApp1/Routes/console.php                                        |                                                             |
| my_app1 | config           | /src/App/MyApp1/Configs/sub_dir1/sub_dir2/test_config2.php                | config('my_app1::test_config2.something');                  |
| my_app1 | config           | /src/App/MyApp1/Configs/test_config1.php                                  | config('my_app1::test_config1.something');                  |
| my_app1 | livewire         | /src/App/MyApp1/Resources/Livewire/LivewireComponent1.php                 | <livewire:my-app1::livewire-component1 />                   |
| my_app1 | livewire         | /src/App/MyApp1/Resources/Livewire/SubDir1/SubDir2/LivewireComponent2.php | <livewire:my-app1::sub-dir1.sub-dir2.livewire-component2 /> |
| my_app1 | migration        | /src/App/MyApp1/Database/Migrations                                       |                                                             |
| my_app1 | route-api        | /src/App/MyApp1/Routes/api.php                                            |                                                             |
| my_app1 | route-web        | /src/App/MyApp1/Routes/web.php                                            |                                                             |
| my_app1 | service-provider | /src/App/MyApp1/Providers/SubDir1/SubDir2/TestServiceProvider2.php        |                                                             |
| my_app1 | service-provider | /src/App/MyApp1/Providers/TestServiceProvider1.php                        |                                                             |
| my_app1 | translation      | /src/App/MyApp1/Resources/Lang                                            | __('my_app1::sub_dir1/sub_dir2/translations2.hello');       |
| my_app1 | view             | /src/App/MyApp1/Resources/Views                                           | <x-my-app1::anonymous-component1 />                         |
|         |                  |                                                                           | <x-my-app1::sub-dir1.sub-dir2.anonymous-component2 />       |
|         |                  |                                                                           | view('my-app1::blade-template-with-the-lot');               |
|         |                  |                                                                           | view('my-app1::blade-template1');                           |
|         |                  |                                                                           | view('my-app1::sub-dir1.sub-dir2.blade-template2');         |
| my_app1 | view-component   | /src/App/MyApp1/Resources/ViewComponents                                  | <x-my-app1::sub-dir1.sub-dir2.view-component2 />            |
|         |                  |                                                                           | <x-my-app1::view-component1 />                              |
+---------+------------------+---------------------------------------------------------------------------+-------------------------------------------------------------+

",
            Artisan::output()
        );
    }



    /**
     * Test that the auto-reg:list command runs properly - when there's no resources to register.
     *
     * @test
     * @return void
     */
    public function test_command_list_when_empty(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = $this->newDetect('scenario_empty');
        $this->runServiceProvider($detect);

        Artisan::call('auto-reg:list');
        $this->assertSame(
            "No resources were detected.
",
            Artisan::output()
        );
    }



    /**
     * Test that the auto-reg:cache command runs properly.
     *
     * @test
     * @return void
     */
    public function test_command_cache(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = $this->newDetect('scenario1');
        $this->runServiceProvider($detect);

        $this->assertFalse(file_exists($detect->getMainCachePath()));

        Artisan::call('auto-reg:cache');
        $this->assertSame(
            "Auto-Reg cache cleared!
Auto-Reg cached successfully!
",
            Artisan::output()
        );

        $this->assertFileExists($detect->getMainCachePath());
    }



    /**
     * Test that the auto-reg:cache command runs properly - when there's no resources to register.
     *
     * @test
     * @return void
     */
    public function test_command_cache_when_empty(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = $this->newDetect('scenario_empty');
        $this->runServiceProvider($detect);

        $this->assertFalse(file_exists($detect->getMainCachePath()));

        Artisan::call('auto-reg:cache');
        $this->assertSame(
            "Auto-Reg cache cleared!
No resources were detected.
Auto-Reg cached successfully!
",
            Artisan::output()
        );

        $this->assertFileExists($detect->getMainCachePath());
    }



    /**
     * Test that the auto-reg:clear command runs properly.
     *
     * @test
     * @return void
     */
    public function test_command_clear(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = $this->newDetect('scenario1');
        $this->runServiceProvider($detect);

        $this->assertFalse(file_exists($detect->getMainCachePath()));
        $detect->loadFresh(true);
        $detect->saveCache();
        $this->assertFileExists($detect->getMainCachePath());

        Artisan::call('auto-reg:clear');
        $this->assertSame(
            "Auto-Reg cache cleared!
",
            Artisan::output()
        );

        $this->assertFalse(file_exists($detect->getMainCachePath()));
    }



    /**
     * Test that the auto-reg:stats command runs properly.
     *
     * @test
     * @return void
     */
    public function test_command_stats(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = $this->newDetect('scenario1');
        $this->runServiceProvider($detect);

        Artisan::call('auto-reg:stats');
        $output = preg_replace('/[0-9]+\.[0-9]{3}/', 'x.xxx', Artisan::output());
        $this->assertSame(
            "
Laravel Auto-Reg cache status: NOT CACHED

+---------------------------+------------+-----------------+
| Action                    | Time taken | # registrations |
+---------------------------+------------+-----------------+
| resource detection        | x.xxxms    |                 |
| register broadcast        | x.xxxms    | 1               |
| register command          | x.xxxms    | 2               |
| register command-closure  | x.xxxms    | 1               |
| register config           | x.xxxms    | 2               |
| register livewire         | x.xxxms    | 2               |
| register migration        | x.xxxms    | 1               |
| register route-api        | x.xxxms    | 1               |
| register route-web        | x.xxxms    | 1               |
| register service-provider | x.xxxms    | 2               |
| register translation      | x.xxxms    | 1               |
| register view             | x.xxxms    | 1               |
| register view-component   | x.xxxms    | 1               |
| total                     | x.xxxms    | 16              |
+---------------------------+------------+-----------------+

",
            $output
        );
    }



    /**
     * Provide data for the test_that_resources_are_disabled test.
     *
     * @return mixed[]
     */
    public function enabledTypesDataProvider(): array
    {
        return collect(Settings::TYPE_TO_CONFIG_NAME_MAP)->map(fn($configName, $name) => [$name, $configName])->all();
    }

    /**
     * Test that the auto-reg:stats command runs properly.
     *
     * @test
     * @dataProvider enabledTypesDataProvider
     * @param string $name       The resource to enable.
     * @param string $configName The name of the resource in the config.
     * @return void
     */
    public function test_that_resources_are_disabled(string $name, string $configName): void
    {
        $replaceConfig = [
            'enabled' => (array) array_combine(
                Settings::TYPE_TO_CONFIG_NAME_MAP,
                array_fill(0, count(Settings::TYPE_TO_CONFIG_NAME_MAP), false)
            )
        ];
        $replaceConfig['enabled'][$configName] = true;

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = $this->newDetect('scenario1', $replaceConfig);
        $this->runServiceProvider($detect);

        Artisan::call('auto-reg:stats');

        $output = str_replace("\r", "\n", str_replace("\r\n", "\n", Artisan::output()));
        $output = collect(explode("\n", $output))
            ->splice(7) // remove the top of the output
            ->splice(0, -4) // remove the bottom of the output
            ->map(
                fn($row) => // pick out the column containing the resource-name
                    collect(explode('|', $row))
                        ->map(fn($col) => trim($col))
                        ->splice(1, 1)
            )
            ->flatten(1)
            ->toArray();

        $this->assertSame(['register ' . $name], $output);
    }
}
