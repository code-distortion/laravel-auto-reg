<?php

namespace CodeDistortion\LaravelAutoReg\Support;

/**
 * Environment utilities.
 */
class Environment
{
    /**
     * Detect whether this code is running on vapor.
     *
     * @return boolean
     */
    public static function isOnVapor(): bool
    {
        return ($_ENV['SERVER_SOFTWARE'] ?? null) === 'vapor';
    }

    /**
     * Detected if the "auto-reg:list" command is being run.
     *
     * NOTE: This isn't perfect. Commands can be run by typing less (eg. "php artisan a:l") and this won't pick up
     * those cases. The command hasn't been picked by the time this is needed so checking argv seems like the best way
     * to guess.
     *
     * @return boolean
     */
    public static function runningAutoRegListCommand(): bool
    {
        return app()->runningInConsole()
            ? in_array($_SERVER['argv'][1] ?? null, ['auto-reg:list'])
            : false;
    }

    /**
     * Detected if "auto-reg:cache" or "auto-reg:clear" are being run.
     *
     * NOTE: This isn't perfect. Commands can be run by typing less (eg. "php artisan a:c") and this won't pick up
     * those cases. The command hasn't been picked by the time this is needed so checking argv seems like the best way
     * to guess.
     *
     * @return boolean
     */
    public static function runningAutoRegCacheCommand(): bool
    {
        return app()->runningInConsole()
            ? in_array($_SERVER['argv'][1] ?? null, ['auto-reg:cache', 'auto-reg:clear'])
            : false;
    }
}
