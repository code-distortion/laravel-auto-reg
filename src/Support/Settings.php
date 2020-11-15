<?php

namespace CodeDistortion\LaravelAutoReg\Support;

/**
 * Laravel AutoReg settings.
 */
class Settings
{
    /**
     * The name of the config file.
     *
     * @const string
     */
    public const LARAVEL_CONFIG_NAME = 'code_distortion.laravel_auto_reg';

    /**
     * The types of files that can be registered.
     *
     * @const string
     */
    public const TYPE__BROADCAST_CLOSURE_FILE = 'broadcast';
    /** @const string */
    public const TYPE__COMMAND_CLASS = 'command';
    /** @const string */
    public const TYPE__COMMAND_CLOSURE_FILE = 'command-closure';
    /** @const string */
    public const TYPE__CONFIG_FILE = 'config';
    /** @const string */
    public const TYPE__LIVEWIRE_COMPONENT_CLASS = 'livewire';
    /** @const string */
    public const TYPE__MIGRATION_DIRECTORY = 'migration';
    /** @const string */
    public const TYPE__ROUTE_API_FILE = 'route-api';
    /** @const string */
    public const TYPE__ROUTE_WEB_FILE = 'route-web';
    /** @const string */
    public const TYPE__SERVICE_PROVIDER_CLASS = 'service-provider';
    /** @const string */
    public const TYPE__TRANSLATION_DIRECTORY = 'translation';
    /** @const string */
    public const TYPE__VIEW_COMPONENT_CLASS = 'view-component';
    /** @const string */
    public const TYPE__VIEW_DIRECTORY = 'view';

    /**
     * The resource-type names in the config file.
     *
     * @const string
     */
    public const TYPE_TO_CONFIG_NAME_MAP = [
        self::TYPE__BROADCAST_CLOSURE_FILE => 'broadcast',
        self::TYPE__COMMAND_CLASS => 'command_classes',
        self::TYPE__COMMAND_CLOSURE_FILE => 'command_closures',
        self::TYPE__CONFIG_FILE => 'configs',
        self::TYPE__LIVEWIRE_COMPONENT_CLASS => 'livewire',
        self::TYPE__MIGRATION_DIRECTORY => 'migrations',
        self::TYPE__ROUTE_API_FILE => 'routes_api',
        self::TYPE__ROUTE_WEB_FILE => 'routes_web',
        self::TYPE__SERVICE_PROVIDER_CLASS => 'service_providers',
        self::TYPE__TRANSLATION_DIRECTORY => 'translations',
        self::TYPE__VIEW_COMPONENT_CLASS => 'view_components',
        self::TYPE__VIEW_DIRECTORY => 'view_templates',
    ];

    /**
     * A version number stored in the cache, used to invalidate cached data when the format changes.
     *
     * @const integer
     */
    public const CACHE_DATA_VERSION = 1;
}
