<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use Illuminate\Support\Str;

/**
 * String helpers.
 */
class StringSupport
{
    /**
     * Change the given string case type.
     *
     * @param string $string   The string to convert.
     * @param string $caseType The case-type to use - "snake", "kebab", "camel" or "pascal".
     * @return string
     */
    public static function changeCase(string $string, string $caseType): string
    {
        return Str::of($string)
            ->replace(['_', '-'], ' ')
            ->explode('.')
            ->map(
                fn($value) => mb_strtolower($caseType) === 'pascal'
                    ? (string) Str::of($value)->camel()->ucfirst()
                    : (string) Str::$caseType($value)
            )
            ->implode('.');
    }
}
