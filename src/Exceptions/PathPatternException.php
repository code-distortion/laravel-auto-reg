<?php

namespace CodeDistortion\LaravelAutoReg\Exceptions;

use CodeDistortion\LaravelAutoReg\Support\Settings;

/**
 * Exceptions relating to path-patterns.
 */
class PathPatternException extends LaravelAutoRegException
{
    /**
     * Thrown when a path-pattern is invalid.
     *
     * @param string $pattern The invalid path-pattern.
     * @return self
     */
    public static function invalidPattern(string $pattern): self
    {
        return new self(
            "Laravel Auto-Reg: The path-pattern \"$pattern\" is invalid. "
            . "Check the 'patterns' values in configs/" . Settings::LARAVEL_CONFIG_NAME . ".php"
        );
    }
}
