<?php

namespace CodeDistortion\LaravelAutoReg\Exceptions;

use Throwable;

/**
 * Exceptions generated when reading from the cache.
 */
class CacheExistsButCouldNotBeReadException extends LaravelAutoRegException
{
    /**
     * Thrown when a cache file could not be read.
     *
     * @param string         $path The cache file.
     * @param Throwable|null $e    The original exception.
     * @return self
     */
    public static function errorReadingCacheFile(string $path, ?Throwable $e = null): self
    {
        $message = "Laravel Auto-Reg: There was an error when reading from cache file \"$path\"";
        return $e
            ? new self($message, $e->getCode(), $e)
            : new self($message);
    }
}
