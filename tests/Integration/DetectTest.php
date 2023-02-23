<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Integration;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\AutoRegDTO;
use CodeDistortion\LaravelAutoReg\Tests\Integration\Support\TestInitTrait;
use CodeDistortion\LaravelAutoReg\Tests\LaravelTestCase;

/**
 * Test the Detect class.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class DetectTest extends LaravelTestCase
{
    use TestInitTrait;



    /** @var mixed[] The resolved data after successfully detecting everything. */
    private static array $allResolved = [
        'broadcast' => [
            'src/App/MyApp1/Routes/channels.php',
        ],
        'command' => [
            '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Commands\\SubDir1\\SubDir2\\TestCommand2',
            '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Commands\\TestCommand1',
        ],
        'command-closure' => [
            'src/App/MyApp1/Routes/console.php',
        ],
        'config' => [
            'my_app1::test_config2' => 'src/App/MyApp1/Configs/sub_dir1/sub_dir2/test_config2.php',
            'my_app1::test_config1' => 'src/App/MyApp1/Configs/test_config1.php',
        ],
        'livewire' => [
            'my-app1::livewire-component1' => '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Resources\\Livewire\\LivewireComponent1',
            'my-app1::sub-dir1.sub-dir2.livewire-component2' => '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Resources\\Livewire\\SubDir1\\SubDir2\\LivewireComponent2',
        ],
        'migration' => [
            'src/App/MyApp1/Database/Migrations',
        ],
        'route-api' => [
            'src/App/MyApp1/Routes/api.php',
        ],
        'route-web' => [
            'src/App/MyApp1/Routes/web.php',
        ],
        'service-provider' => [
            '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Providers\\SubDir1\\SubDir2\\TestServiceProvider2',
            '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Providers\\TestServiceProvider1',
        ],
        'translation' => [
            'my_app1' => 'src/App/MyApp1/Resources/Lang',
        ],
        'view-component' => [
            'my_app1' => [
                'my-app1',
                '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Resources\\ViewComponents',
            ],
        ],
        'view' => [
            'my-app1' => 'src/App/MyApp1/Resources/Views',
        ],
    ];

    /** @var mixed[] The resolved data after successfully detecting everything. */
    private static array $noAppResolved = [
        'broadcast' => [
            'src/App/Routes/channels.php'
        ],
        'command' => [
            "\CodeDistortion\LaravelAutoReg\Tests\ScenarioNoApp\Commands\SubDir1\SubDir2\TestCommand2",
            "\CodeDistortion\LaravelAutoReg\Tests\ScenarioNoApp\Commands\TestCommand1",
        ],
        'command-closure' => [
            'src/App/Routes/console.php'
        ],
        'config' => [],
        'livewire' => [],
        'migration' => [
            'src/App/Database/Migrations'
        ],
        'route-api' => [
            'src/App/Routes/api.php',
        ],
        'route-web' => [
            'src/App/Routes/web.php',
        ],
        'service-provider' => [
            "\CodeDistortion\LaravelAutoReg\Tests\ScenarioNoApp\Providers\SubDir1\SubDir2\TestServiceProvider2",
            "\CodeDistortion\LaravelAutoReg\Tests\ScenarioNoApp\Providers\TestServiceProvider1"
        ],
        'translation' => [],
        'view-component' => [],
        'view' => [],
    ];

    /** @var mixed[] The resolved data when nothing is detected. */
    private static array $noneResolved = [
        'broadcast' => [],
        'command' => [],
        'command-closure' => [],
        'config' => [],
        'livewire' => [],
        'migration' => [],
        'route-api' => [],
        'route-web' => [],
        'service-provider' => [],
        'translation' => [],
        'view-component' => [],
        'view' => [],
    ];



    /**
     * Test that everything is detected properly.
     *
     * @test
     * @return void
     */
    public static function test_everything_is_detected(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1');

        static::assertTrue($detect->resourcesWereDetected());
        static::assertSame(static::$allResolved, $autoRegDTO->getAllResolved());
    }

    /**
     * Test that everything is detected properly when there's no app.
     *
     * @test
     * @return void
     */
    public static function test_no_app_is_detected(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario_no_app');

        static::assertTrue($detect->resourcesWereDetected());
        static::assertSame(static::$noAppResolved, $autoRegDTO->getAllResolved());
    }

    /**
     * Test that everything is detected properly when there's no app.
     *
     * @test
     * @return void
     */
    public static function test_no_app_is_detected_when_config_doesnt_use_app(): void
    {
        $replaceConfig = [];
        $replaceConfig['settings']['configs']['use_app_name'] = false;

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario_no_app', $replaceConfig);

        $match = array_merge(
            static::$noAppResolved,
            [
                'config' => [
                    'test_config2' => 'src/App/Configs/sub_dir1/sub_dir2/test_config2.php',
                    'test_config1' => 'src/App/Configs/test_config1.php',
                ],
            ],
        );

        static::assertTrue($detect->resourcesWereDetected());
        static::assertSame($match, $autoRegDTO->getAllResolved());
    }



    /**
     * Provide data for the test_config_ignore test.
     *
     * @return mixed[]
     */
    public static function configIgnoreDataProvider(): array
    {
        return [
            'ignore none' => [
                'replaceConfig' => [],
                'expectedReplacements' => []
            ],
            'ignore config file' => [
                'replaceConfig' => [
                    'ignore' => ['/src/App/MyApp1/Configs/test_config1.php'],
                ],
                'expectedReplacements' => [
                    'config' => ['my_app1::test_config2' => 'src/App/MyApp1/Configs/sub_dir1/sub_dir2/test_config2.php'],
                ],
            ],
            'ignore config directory' => [
                'replaceConfig' => [
                    'ignore' => ['/src/App/MyApp1/Configs'],
                ],
                'expectedReplacements' => [
                    'config' => [],
                ],
            ],
            'ignore command+config directory' => [
                'replaceConfig' => [
                    'ignore' => [
                        '/src/App/MyApp1/Configs',
                        '/src/App/MyApp1/Commands',
                    ],
                ],
                'expectedReplacements' => [
                    'command' => [],
                    'config' => [],
                ],
            ],
            'ignore service-provider FQCN' => [
                'replaceConfig' => [
                    'ignore' => [
                        '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Providers\\TestServiceProvider1',
                    ],
                ],
                'expectedReplacements' => [
                    'service-provider' => [
                        '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Providers\\SubDir1\\SubDir2\\TestServiceProvider2',
                    ],
                ],
            ],
            'ignore service-provider namespace' => [
                'replaceConfig' => [
                    'ignore' => [
                        '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1\\Providers',
                    ],
                ],
                'expectedReplacements' => [
                    'service-provider' => [],
                ],
            ],
            'ignore all of a namespace' => [
                'replaceConfig' => [
                    'ignore' => [
                        '\\CodeDistortion\\LaravelAutoReg\\Tests\\Scenario1App\\MyApp1',
                    ],
                ],
                'expectedReplacements' => [
                    'command' => [],
                    'livewire' => [],
                    'service-provider' => [],
                    'view-component' => [],
                ],
            ],
            'ignore all via directory' => [
                'replaceConfig' => [
                    'ignore' => ['/src/App/MyApp1'],
                ],
                'expectedReplacements' => static::$noneResolved,
            ],
        ];
    }

    /**
     * Test that Detect appropriately ignores files.
     *
     * @test
     * @dataProvider configIgnoreDataProvider
     * @param array<string, mixed> $replaceConfig        The config values to replace.
     * @param array<string, mixed> $expectedReplacements The values to replace in the expected output.
     * @return void
     */
    public static function test_config_ignore(array $replaceConfig, array $expectedReplacements): void
    {
        $expected = static::$allResolved;
        foreach ($expectedReplacements as $index => $value) {
            $expected[$index] = $value;
        }

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        static::assertSame($expected, $autoRegDTO->getAllResolved());
    }



    /**
     * Provide data for the test_disabled_types test.
     *
     * @return mixed[]
     */
    public static function disabledTypesDataProvider(): array
    {
        return [
            'disable none' => [
                'disabledTypes' => [],
            ],
            'disable broadcast' => [
                'disabledTypes' => [
                    'broadcast' => 'broadcast',
                ],
            ],
            'disable broadcast, config' => [
                'disabledTypes' => [
                    'broadcast' => 'broadcast',
                    'configs' => 'config',
                ],
            ],
        ];
    }

    /**
     * Test that Detect appropriately ignores when certain types are disabled.
     *
     * @test
     * @dataProvider disabledTypesDataProvider
     * @param array<string, string> $disabledTypes The types to be disabled via the config.
     * @return void
     */
    public static function test_disabled_types(array $disabledTypes): void
    {
        $replaceConfig = [];
        $expected = static::$allResolved;
        foreach ($disabledTypes as $typeInConfig => $type) {
            $replaceConfig['enabled'][$typeInConfig] = false;
            $expected[$type] = [];
        }

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        static::assertSame($expected, $autoRegDTO->getAllResolved());
    }



    /**
     * Test that the various "get" methods in the Detect class give the correct responses.
     *
     * @test
     * @return void
     */
    public static function test_get_methods(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1');

        static::assertSame(static::$allResolved['broadcast'], $detect->getBroadcastClosureFiles());
        static::assertSame(static::$allResolved['command'], $detect->getCommandClasses());
        static::assertSame(static::$allResolved['command-closure'], $detect->getCommandClosureFiles());
        static::assertSame(static::$allResolved['config'], $detect->getConfigFiles());
        static::assertSame(static::$allResolved['livewire'], $detect->getLivewireComponentClasses());
        static::assertSame(static::$allResolved['migration'], $detect->getMigrationDirectories());
        static::assertSame(static::$allResolved['route-api'], $detect->getRouteApiFiles());
        static::assertSame(static::$allResolved['route-web'], $detect->getRouteWebFiles());
        static::assertSame(static::$allResolved['service-provider'], $detect->getServiceProviderClasses());
        static::assertSame(static::$allResolved['translation'], $detect->getTranslationDirectories());
        static::assertSame(static::$allResolved['view-component'], $detect->getViewComponentClasses());
        static::assertSame(static::$allResolved['view'], $detect->getViewDirectories());
    }



    /**
     * Test that the correct middleware lists are returned.
     *
     * @test
     * @return void
     */
    public static function test_middleware_list(): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1');

        static::assertSame(['api'], $detect->routeApiMiddleware());
        static::assertSame(['web'], $detect->routeWebMiddleware());


        // replace the middleware with other values
        $replaceConfig = [];
        $replaceConfig['settings']['routes']['api']['middleware'] = ['abc', 'def'];
        $replaceConfig['settings']['routes']['web']['middleware'] = ['123', '456'];

        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */

        static::assertSame(['abc', 'def'], $detect->routeApiMiddleware());
        static::assertSame(['123', '456'], $detect->routeWebMiddleware());
    }



    /**
     * Provide data for the test_disabled_types test.
     *
     * @return mixed[]
     */
    public static function shouldRunBroadcastAuthDataProvider(): array
    {
        return [
            'a' => [
                'replaceConfig' => [
                    'enabled' => [
                        'broadcast' => true,
                    ],
                    'settings' => [
                        'broadcast' => [
                            'run_auth' => true,
                        ],
                    ],
                ],
                'expected' => true,
            ],
            'b' => [
                'replaceConfig' => [
                    'enabled' => [
                        'broadcast' => true,
                    ],
                    'settings' => [
                        'broadcast' => [
                            'run_auth' => false,
                        ],
                    ],
                ],
                'expected' => false,
            ],
            'c' => [
                'replaceConfig' => [
                    'enabled' => [
                        'broadcast' => false,
                    ],
                    'settings' => [
                        'broadcast' => [
                            'run_auth' => true,
                        ],
                    ],
                ],
                'expected' => false,
            ],

            'd' => [
                'replaceConfig' => [
                    'enabled' => [
                        'broadcast' => false,
                    ],
                    'settings' => [
                        'broadcast' => [
                            'run_auth' => false,
                        ],
                    ],
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * Test that the "should run broadcast auth" value is picked up appropriately.
     *
     * @test
     * @dataProvider shouldRunBroadcastAuthDataProvider
     * @param array<string, mixed> $replaceConfig The config values to replace.
     * @param boolean              $expected      The expected outcome.
     * @return void
     */
    public static function test_should_run_broadcast_auth_setting(array $replaceConfig, bool $expected): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        static::assertSame($expected, $detect->shouldRegisterBroadcastRoutes());
    }



    /**
     * Test that the first match found is used (based on the order of the search-patterns) when only one is allowed.
     *
     * @test
     * @return void
     */
    public static function test_that_multiple_view_directories_results_in_one_match(): void
    {
        $replaceConfig = [
            'patterns' => [
                'view_templates' => [
                    'Resources/Views/**/*.php', // the directory matching this should be picked over the one below
                    'SomeOtherThings/Views/**/*.php',
                ],
            ],
        ];

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        static::assertSame(['my-app1' => 'src/App/MyApp1/Resources/Views'], $autoRegDTO->getResolved('view'));


        // the same - but with the patterns in reverse order
        $replaceConfig = [
            'patterns' => [
                'view_templates' => [
                    'SomeOtherThings/Views/**/*.php', // the directory matching this should be picked over the one below
                    'Resources/Views/**/*.php',
                ],
            ],
        ];

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        static::assertSame(['my-app1' => 'src/App/MyApp1/SomeOtherThings/Views'], $autoRegDTO->getResolved('view'));
    }



    /**
     * Test that Detect acts appropriately when no resource files are there to detect.
     *
     * @test
     * @return void
     */
    public static function test_that_the_correct_match_is_picked_when_it_matches_more_than_one_rule(): void
    {
        // detect when the path matches only one search-pattern
        $replaceConfig = [
            'patterns' => [
                'command_classes' => [
                    'CommandsX/**/*.php',
                ],
            ],
        ];

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        static::assertSame(1, count($autoRegDTO->getAllMeta()['command']));
        static::assertSame('my_app1.some_other_things', $autoRegDTO->getAllMeta()['command'][0]['app']);



        // now detect when the path matches more than one search-pattern
        $replaceConfig = [
            'patterns' => [
                'command_classes' => [
                    'CommandsX/**/*.php',
                    'SomeOtherThings/CommandsX/**/*.php',
                ],
            ],
        ];

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        static::assertSame(1, count($autoRegDTO->getAllMeta()['command']));
        static::assertSame('my_app1', $autoRegDTO->getAllMeta()['command'][0]['app']);
    }



    /**
     * Test that Detect detects files from more than one source.
     *
     * @test
     * @return void
     */
    public static function test_detection_from_multiple_sources(): void
    {
        $replaceConfig = [
            'source_dir' => [
                __DIR__ . '/../workspaces/scenario1/src/App',
                __DIR__ . '/../workspaces/scenario1/src/App2',
            ],
        ];

        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', $replaceConfig);

        $apps = collect($autoRegDTO->getAllMeta())
            ->flatten(1)
            ->pluck('source')
            ->unique();

        static::assertTrue($apps->filter(fn($dir) => $dir == '/src/App')->isNotEmpty());
        static::assertTrue($apps->filter(fn($dir) => $dir == '/src/App2')->isNotEmpty());
    }



    /**
     * Provide data for the test_meta_loading test.
     *
     * @return mixed[]
     */
    public static function metaLoadingDataProvider(): array
    {
        return [
            'needs meta' => [
                'needMeta' => true,
            ],
            'no meta' => [
                'needMeta' => false,
            ],
        ];
    }

    /**
     * Test that Detect loads or doesn't load the meta-data.
     *
     * @test
     * @dataProvider metaLoadingDataProvider
     * @param boolean $needMeta Should the meta-data be generated / loaded from cache?.
     * @return void
     */
    public static function test_meta_loading(bool $needMeta): void
    {
        /** @var AutoRegDTO $autoRegDTO */
        /** @var Detect $detect */
        [$autoRegDTO, $detect] = static::newDetect('scenario1', [], $needMeta);

        if ($needMeta) {
            static::assertNotSame([], $autoRegDTO->getAllMeta()['broadcast']);
        } else {
            static::assertSame([], $autoRegDTO->getAllMeta()['broadcast']);
        }
    }
}
