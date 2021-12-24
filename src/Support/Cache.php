<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use CodeDistortion\LaravelAutoReg\Exceptions\CacheExistsButCouldNotBeReadException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem as IlluminateFS;
use Throwable;

/**
 * Load and store cache data in the filesystem.
 */
class Cache
{
    /** @var IlluminateFS Gives access to the filesystem. */
    private IlluminateFS $files;

    /** @var string The path to the "main" cache file. */
    private string $mainCachePath;

    /** @var string The path to the "meta" cache file. */
    private string $metaCachePath;

    /** @var boolean Is the meta-data needed? */
    private bool $needMeta;

    /** @var string The "main" flagpole comment to save in the file. */
    protected string $mainFlagpole = '/*
|--------------------------------------------------------------------------
| Laravel Auto-Reg cache data
|--------------------------------------------------------------------------
|
| These are the settings that Auto-Reg uses and the resources it registers.
|
*/';

    /** @var string The "meta" flagpole comment to save in the file. */
    protected string $metaFlagpole = '/*
|--------------------------------------------------------------------------
| Laravel Auto-Reg "meta" cache data
|--------------------------------------------------------------------------
|
| This is the meta information about the resources that Auto-Reg registers.
|
*/';



    /**
     * Constructor.
     *
     * @param IlluminateFS $files         Gives access to the filesystem.
     * @param string       $mainCachePath The path to the "main" cache file.
     * @param string       $metaCachePath The path to the "meta" cache file.
     * @param boolean      $needMeta      Should the meta-data be generated / loaded from cache?.
     */
    public function __construct(IlluminateFS $files, string $mainCachePath, string $metaCachePath, bool $needMeta)
    {
        $this->files = $files;
        $this->mainCachePath = $mainCachePath;
        $this->metaCachePath = $metaCachePath;
        $this->needMeta = $needMeta;
    }



    /**
     * Get the "main" cache file path.
     *
     * @return string
     */
    public function getMainCachePath(): string
    {
        return $this->mainCachePath;
    }

    /**
     * Get the "meta" cache file path.
     *
     * @return string
     */
    public function getMetaCachePath(): string
    {
        return $this->metaCachePath;
    }



    /**
     * Load from the cache if available.
     *
     * @param array<string, mixed>|null $mainCacheData The "main" data variable to update.
     * @param array<string, mixed>|null $metaCacheData The "meta" data variable to update.
     * @return boolean
     * @throws CacheExistsButCouldNotBeReadException Thrown when the cache exists but could not be read.
     */
    public function load(?array &$mainCacheData, ?array &$metaCacheData): bool
    {
        $mainCacheData = $metaCacheData = null;

        $main = $this->loadFromFileIfExists($this->mainCachePath);
        if (!is_array($main)) {
            return false;
        }

        $meta = null;
        if ($this->needMeta) {
            $meta = $this->loadFromFileIfExists($this->metaCachePath);
            if (!is_array($meta)) {
                throw CacheExistsButCouldNotBeReadException::errorReadingCacheFile($this->metaCachePath);
            }
        }

        $mainCacheData = $main;
        $metaCacheData = $meta;
        return true;
    }

    /**
     * "require" load data from the given path.
     *
     * @param string $path The path to read from.
     * @return mixed[]|null
     * @throws CacheExistsButCouldNotBeReadException Thrown when the cache exists but could not be read.
     */
    private function loadFromFileIfExists(string $path): ?array
    {
        try {
            return $this->files->getRequire($path);
        } catch (FileNotFoundException $e) {
            return null;
        } catch (Throwable $e) {
            throw CacheExistsButCouldNotBeReadException::errorReadingCacheFile($path, $e);
        }
    }

    /**
     * Save data into the cache.
     *
     * @param array<string, mixed> $mainCacheData The "main" data to save.
     * @param array<string, mixed> $metaCacheData The "meta" data to save.
     * @return void
     */
    public function save(array $mainCacheData, array $metaCacheData): void
    {
        $this->clear();

        $this->files->put(
            $this->mainCachePath,
            $this->buildCacheContent($mainCacheData, $this->mainFlagpole),
            true
        );
        $this->files->put(
            $this->metaCachePath,
            $this->buildCacheContent($metaCacheData, $this->metaFlagpole),
            true
        );
    }

    /**
     * Build the cache file content.
     *
     * @param array<string, mixed> $cacheData The data to save.
     * @param string               $flagpole  The flagpole comment to use.
     * @return string
     */
    private function buildCacheContent(array $cacheData, string $flagpole): string
    {
        return "<?php

{$flagpole}

return " . var_export($cacheData, true) . ";
";
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->files->delete($this->mainCachePath, $this->metaCachePath);
    }
}
