<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use Illuminate\Support\Collection;

/**
 * Meta-data about a matched file.
 */
class MatchedFileDTO
{
    /** @var string The location or alias of where this entry came from. */
    public string $source;

    /** @var string|null The app this was found in. */
    public ?string $app;

    /** @var string|null The part of the path after the stars. */
    public ?string $afterStarDir;

    /** @var string|null The base-filename of the file. */
    public ?string $name;

    /** @var string The path to the file. */
    public string $path;

    /** @var string The path to the file - within the Laravel project. */
    public string $localPath;

    /** @var string|null The class's FQCN (when available). */
    public ?string $fqcn;

    /** @var Collection|BasicFile[] The files that were matched for this entry. */
    public Collection $matched;



    /**
     * @param string                 $source       The location or alias of where this entry came from.
     * @param string|null            $app          The app this was found in.
     * @param string|null            $afterStarDir The part of the path after the stars.
     * @param string|null            $name         The base-filename of the file.
     * @param string                 $path         The path to the file.
     * @param string                 $localPath    The path to the file - within the Laravel project.
     * @param string|null            $fqcn         The class's FQCN (when available).
     * @param Collection|BasicFile[] $matched      The files that were matched for this entry.
     */
    public function __construct(
        string $source,
        ?string $app,
        ?string $afterStarDir,
        ?string $name,
        string $path,
        string $localPath,
        ?string $fqcn,
        Collection $matched
    ) {
        $this->source = $source;
        $this->app = $app;
        $this->afterStarDir = $afterStarDir;
        $this->name = $name;
        $this->path = $path;
        $this->localPath = $localPath;
        $this->fqcn = $fqcn;
        $this->matched = $matched;
    }
}
