<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Source Location/s
     |--------------------------------------------------------------------------
     |
     | This is the base directory (or array of directories) that will be
     | looked through for files and classes to register.
     |
     */

    'source_dir' => null, // base_path('src/App'),

    /*
     |--------------------------------------------------------------------------
     | Enabled Registrations
     |--------------------------------------------------------------------------
     |
     | These types of files will be looked for and registered.
     |
     | Note: Broadcast is disabled by default, it's comparatively slow.
     |
     */

    'enabled' => [
        'broadcast' => false,
        'command_classes' => true,
        'command_closures' => true,
        'configs' => true,
        'livewire' => true,
        'migrations' => true,
        'routes_api' => true,
        'routes_web' => true,
        'service_providers' => true,
        'translations' => true,
        'view_components' => true,
        'view_templates' => true,
    ],

    /*
     |--------------------------------------------------------------------------
     | Search Patterns
     |--------------------------------------------------------------------------
     |
     | These patterns will be used to find classes and files to register.
     |
     */

    'patterns' => [
        'broadcast' => 'Routes/channels.php',
        'command_classes' => 'Commands/**/*.php',
        'command_closures' => 'Routes/console.php',
        'configs' => 'Configs/**/*.php',
        'livewire' => 'Resources/Livewire/**/*.php',
        'migrations' => 'Database/Migrations/*.php',
        'routes_api' => 'Routes/api.php',
        'routes_web' => 'Routes/web.php',
        'service_providers' => 'Providers/**/*.php',
        'translations' => 'Resources/Lang/**/*.php',
        'view_components' => 'Resources/ViewComponents/**/*.php',
        'view_templates' => 'Resources/Views/**/*.php',
    ],

    /*
     |--------------------------------------------------------------------------
     | Registration Settings
     |--------------------------------------------------------------------------
     |
     | These settings configure how the various files and classes are handled.
     |
     */

    'settings' => [

        'broadcast' => [
            'run_auth' => true, // automatically call Broadcast::routes(); to handle broadcast-auth and sockets
        ],

        'configs' => [
            'use_app_name' => true, // include the "app" in the name when registering a config file?
            'path_case' => 'snake', // "snake", "kebab", "camel" or "pascal"
        ],
        'routes' => [
            'api' => [
                'middleware' => ['api'],
            ],
            'web' => [
                'middleware' => ['web'],
            ],
        ],

        'translations' => [
            'path_case' => 'snake', // "snake", "kebab", "camel" or "pascal"
        ],

        'views' => [
            'templates' => [
                'path_case' => 'kebab', // "snake", "kebab", "camel" or "pascal"
            ],
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Files and Classes to Ignore
     |--------------------------------------------------------------------------
     |
     | This is the list of things to ignore - they won't be registered. Paths
     | are relative to the base of your project.
     |
     | e.g. [
     |     '/src/App/Dashboard/Providers/MyProvider.php', // ignore a file
     |     '/src/App/Dashboard/Providers',                // ignore a directory
     |     '\App\Dashboard\Providers\MyProvider',         // ignore a class
     |     '\App\Dashboard\Providers',                    // ignore a namespace
     | ]
     |
     */

    'ignore' => [],

];
