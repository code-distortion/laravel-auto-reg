<?php

namespace CodeDistortion\LaravelAutoReg\Support;

/**
 * Basic info about a file from the first-pass that looks for files in the filesystem.
 */
class BasicFile
{
    /** @var string The full path to the file. */
    public string $path;

    /** @var string The path within the project. */
    public string $localPath;

    /** @var string The path within the app. */
    public string $appPath;

    /** @var string|null The "**" part of the path. */
    public ?string $starsAndAfterPath;

    /** @var string|null The class FQCN (if relevant). */
    public ?string $fqcn;

    /**
     * FileMetaDTO constructor.
     *
     * @param string      $path         The full path to the file.
     * @param string      $localPath    The path within the project.
     * @param string      $appPath      The path within the app.
     * @param string|null $wildcardPath The "**" part of the path.
     * @param string|null $fqcn         The class FQCN (if relevant).
     */
    public function __construct(
        string $path,
        string $localPath,
        string $appPath,
        ?string $wildcardPath,
        ?string $fqcn
    ) {
        $this->path = $path;
        $this->localPath = $localPath;
        $this->appPath = $appPath;
        $this->starsAndAfterPath = $wildcardPath;
        $this->fqcn = $fqcn;
    }
}
