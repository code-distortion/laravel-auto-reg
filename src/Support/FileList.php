<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use CodeDistortion\LaravelAutoReg\Exceptions\FilesystemException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Finds files within a given directory - ignoring some if desired.
 */
class FileList
{
    /** @var string Laravel's base_path(). */
    private string $laravelBaseDir;

    /** @var string The directory to look in for files. */
    private string $sourceDir;

    /** @var string|null The name of where these files came from. */
    private ?string $sourceAlias;

    /** @var array<int, string> The list of dirs, paths and namespaces to ignore. */
    private array $ignoreList;

    /** @var Collection|BasicFile[] The list of files found. */
    private Collection $pathList;



    /**
     * @param string             $laravelBaseDir Laravel's base_path().
     * @param string             $sourceDir      The directory to look in for files.
     * @param string|null        $sourceAlias    The name of where these files came from.
     * @param array<int, string> $ignoreList     The list of dirs, paths and namespaces to ignore.
     * @throws FilesystemException Thrown when there is a problem when looking for files.
     */
    public function __construct(
        string $laravelBaseDir,
        string $sourceDir,
        ?string $sourceAlias,
        array $ignoreList
    ) {
        $this->laravelBaseDir = $laravelBaseDir;
        $this->sourceDir = $sourceDir;
        $this->sourceAlias = $sourceAlias;
        $this->ignoreList = $ignoreList;

        $this->pathList = $this->findFilesWithinDir($this->sourceDir);
    }



    /**
     * Get the source name.
     *
     * @return string|null
     */
    public function getSourceAlias(): ?string
    {
        return $this->sourceAlias;
    }

    /**
     * Get the source directory used.
     *
     * @return string
     */
    public function getLocalSourceDir(): string
    {
        return $this->removeBaseDir($this->sourceDir);
    }

    /**
     * Get the list of files found.
     *
     * @return Collection|BasicFile[]
     */
    public function getPathList(): Collection
    {
        return $this->pathList;
    }



    /**
     * Find the files within a directory - removes files that need to be ignored.
     *
     * @param string $sourceDir The directory to look through.
     * @return Collection|BasicFile[]
     * @throws FilesystemException Thrown when there is a problem when looking for files.
     */
    private function findFilesWithinDir(string $sourceDir): Collection
    {
        return collect(
            FileSystem::getRecursiveFileFQCNList($sourceDir)
                ->map(
                    fn($fqcn, $path) => new BasicFile(
                        $path,
                        $this->removeBaseDir($path),
                        Str::replaceFirst($sourceDir, '', $path),
                        null, // resolved later
                        $fqcn
                    )
                )
                ->values()
                ->reject(fn(BasicFile $basicFile) => $this->ignoreFile($basicFile->localPath))
                ->reject(fn(BasicFile $basicFile) => $this->ignoreClass($basicFile->fqcn))
        );
    }

    /**
     * Remove the base-dir (Laravel's base_path()) from a path.
     *
     * @param string $path The path to alter.
     * @return string
     */
    private function removeBaseDir(string $path): string
    {
        return (mb_strpos($path, $this->laravelBaseDir) === 0)
            ? Str::replaceFirst($this->laravelBaseDir, '', $path)
            : $path;
    }

    /**
     * Decide whether to ignore the given file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    private function ignoreFile(string $path): bool
    {
        $path = Str::replaceFirst($this->laravelBaseDir, '', $path);

        // ignore a single file
        foreach ($this->ignoreList as $ignore) {
            $ignore = str_replace('\\', '/', $ignore);
            $ignore = ltrim($ignore, '/');
            $ignore = '/' . $ignore;
            if ($path == $ignore) {
                return true;
            }
        }

        // ignore a whole directory
        foreach ($this->ignoreList as $ignore) {
            $ignore = str_replace('\\', '/', $ignore);
            $ignore = trim($ignore, '/');
            $ignore = '/' . $ignore . '/';
            if (mb_strpos($path, $ignore) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decide whether to ignore the given file.
     *
     * @param string|null $fqcn The class to check.
     * @return boolean
     */
    private function ignoreClass(?string $fqcn): bool
    {
        if (!$fqcn) {
            return false;
        }

        // ignore a single class
        foreach ($this->ignoreList as $ignore) {
            $ignore = ltrim($ignore, '\\');
            if ($fqcn == '\\' . $ignore) {
                return true;
            }
        }

        // ignore a whole namespace
        foreach ($this->ignoreList as $ignore) {
            $ignore = trim($ignore, '\\');
            if (mb_strpos($fqcn, '\\' . $ignore . '\\') === 0) {
                return true;
            }
        }

        return false;
    }
}
