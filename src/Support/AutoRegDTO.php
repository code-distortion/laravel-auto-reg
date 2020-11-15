<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Throwable;

/**
 * A holding place for the settings and data that this package resolves.
 *
 * The contents of this cache can be cached.
 */
class AutoRegDTO
{
    /** @var string The project's base directory (ie. Laravel's base_path()). */
    public string $laravelBaseDir;

    /** @var mixed[] The config data to use. */
    public array $configData;

    /** @var boolean Is the meta-data needed? */
    private bool $needMeta;

    /** @var mixed[] The resolved data used to register the resources. */
    public array $resolved = [];

    /**
     * The resolved meta information about the detected resources.
     *
     * @var array<string, array<int, array<string, string|null>>>
     */
    public array $meta = [];



    /**
     * @param string  $laravelBaseDir The project's base directory.
     * @param mixed[] $configData     The config data to use.
     * @param boolean $needMeta       Should the meta data be generated / loaded from cache?.
     */
    public function __construct(string $laravelBaseDir, array $configData, bool $needMeta)
    {
        $this->laravelBaseDir = $laravelBaseDir;
        $this->configData = $configData;
        $this->needMeta = $needMeta;
    }



    /**
     * Fetch a value from the config.
     *
     * @param string $key     The thing to get.
     * @param mixed  $default The fall-back value.
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        return Arr::get($this->configData, $key, $default);
    }



    /**
     * Check if the meta-data is needed.
     *
     * @return boolean
     */
    public function getNeedMeta(): bool
    {
        return $this->needMeta;
    }

    /**
     * Retrieve all the meta information about the detected resources.
     *
     * @return Collection|string[]
     */
    public function getAllMeta(): Collection
    {
        return collect($this->meta);
    }



    /**
     * Retrieve the full list of resolved data.
     *
     * @return mixed[]
     */
    public function getAllResolved(): array
    {
        return $this->resolved;
    }

    /**
     * Retrieve a list of resolved data.
     *
     * @param string $type The type of resolved data to get.
     * @return mixed[]
     */
    public function getResolved(string $type): array
    {
        return $this->resolved[$type] ?? [];
    }

    /**
     * Set a list of resolved data.
     *
     * @param string                                 $type The type of resolved data to set.
     * @param mixed[]                                $data The data to set.
     * @param array<int, array<string, string|null>> $meta The meta information to add.
     * @return void
     */
    public function setResolved(string $type, array $data, array $meta): void
    {
        $this->resolved[$type] = $data;
        $this->meta[$type] = $meta;
    }



    /**
     * Collate the "main" data stored in this object ready for caching.
     *
     * @return mixed[]
     */
    public function cacheableMainData(): array
    {
        return [
            'cacheDataVersion' => Settings::CACHE_DATA_VERSION,
            'laravelBaseDir' => $this->laravelBaseDir,
            'configData' => $this->configData,
            'resolved' => $this->resolved,
        ];
    }

    /**
     * Collate the "meta" data stored in this object ready for caching.
     *
     * @return mixed[]
     */
    public function cacheableMetaData(): array
    {
        return [
            'cacheDataVersion' => Settings::CACHE_DATA_VERSION,
            'meta' => $this->meta,
        ];
    }



    /**
     * Check whether the cached "main" data is valid.
     *
     * @param array<string, mixed>|null $mainCacheData The cached "main" data.
     * @return boolean
     */
    public function isMainContentOk(?array $mainCacheData): bool
    {
        try {
            if (!is_array($mainCacheData)) {
                return false;
            }

            if ($mainCacheData['cacheDataVersion'] !== Settings::CACHE_DATA_VERSION) {
                return false;
            }

            if ($mainCacheData['laravelBaseDir'] !== $this->laravelBaseDir) {
                return false;
            }

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Check whether the cached "meta" data is valid.
     *
     * @param array<string, mixed>|null $metaCacheData The cached "meta" data.
     * @return boolean
     */
    public function isMetaContentOk(?array $metaCacheData): bool
    {
        try {
            if (!$this->needMeta) {
                return true;
            }

            if (!is_array($metaCacheData)) {
                return false;
            }

            if ($metaCacheData['cacheDataVersion'] !== Settings::CACHE_DATA_VERSION) {
                return false;
            }

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }



    /**
     * Hydrate this object with data retrieved from cache.
     *
     * @param array<string, mixed>      $mainCacheData The cached "main" data.
     * @param array<string, mixed>|null $metaCacheData The cached "meta" data.
     * @return void
     */
    public function hydrateFromCache(array $mainCacheData, ?array $metaCacheData): void
    {
        $this->laravelBaseDir = $mainCacheData['laravelBaseDir'];
        $this->configData = $mainCacheData['configData'];
        $this->resolved = $mainCacheData['resolved'];

        if (($this->needMeta) && (is_array($metaCacheData))) {
            $this->meta = $metaCacheData['meta'];
        } else {
            $this->meta = (array) array_combine(
                array_keys($this->resolved),
                array_fill(0, count($this->resolved), [])
            );
        }
    }
}
