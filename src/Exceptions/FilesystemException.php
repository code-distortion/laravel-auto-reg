<?php

namespace CodeDistortion\LaravelAutoReg\Exceptions;

use CodeDistortion\LaravelAutoReg\Support\Settings;
use Throwable;

/**
 * Exceptions generated when accessing the filesystem.
 */
class FilesystemException extends LaravelAutoRegException
{
    /**
     * Thrown when a source-dir does not exist.
     *
     * @param string         $dir The source-dir.
     * @param Throwable|null $e   The original exception.
     * @return self
     */
    public static function sourceDirNotFound(string $dir, ?Throwable $e = null): self
    {
        $message = "Laravel Auto-Reg: The source directory '$dir' does not exist. "
            . "Check the 'source_dir' value in configs/" . Settings::LARAVEL_CONFIG_NAME . ".php";
        return $e
            ? new self($message, $e->getCode(), $e)
            : new self($message);
    }

    /**
     * Thrown when the source-dir cannot be read from.
     *
     * @param string    $dir The directory that cannot be read from.
     * @param Throwable $e   The original exception.
     * @return self
     */
    public static function errorReadingFromLookInDir(string $dir, Throwable $e): self
    {
        $message = "Laravel Auto-Reg: There was an error when reading from the source directory '$dir'";
        return new self($message, $e->getCode(), $e);
    }
}
