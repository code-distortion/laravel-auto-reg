<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\MatchedFileDTO;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Support\StringSupport;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Picks out config files.
 */
class ConfigFileResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__CONFIG_FILE;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [null];

    /** @var boolean Include the path in the config name. */
    private bool $useAppName = true;

    /** @var boolean Is this allowd to match files that aren't in an app? */
    protected bool $allowNullApp = true;



    /**
     * Set the include-path setting.
     *
     * @param boolean $useAppName Whether or not to include the path in the config name.
     * @return $this
     */
    public function setUseAppName(bool $useAppName): self
    {
        $this->useAppName = $useAppName;
        $this->allowNullApp = !$useAppName;
        return $this;
    }



    /**
     * Add this file's meta data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addMetaData(MatchedFileDTO $matchedFileDTO): void
    {
        $configName = $this->buildPathName($matchedFileDTO->app, $matchedFileDTO->name);

        $this->metaData[] = [
            'source' => $matchedFileDTO->source,
            'app' => $matchedFileDTO->app,
            'type' => static::FILE_TYPE,
            'path' => $matchedFileDTO->localPath,
            'example' => $this->buildExample($matchedFileDTO->matched->first()->path, $configName),
        ];
    }

    /**
     * Add this file's registration data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addRegData(MatchedFileDTO $matchedFileDTO): void
    {
        $configName = $this->buildPathName($matchedFileDTO->app, $matchedFileDTO->name);

        $this->regData[$configName] = ltrim($matchedFileDTO->localPath, '/');
    }



    /**
     * Prepare the config path.
     *
     * @param string|null $app  The app's name.
     * @param string|null $name The config's name.
     * @return string
     */
    private function buildPathName(?string $app, ?string $name): string
    {
        return $this->useAppName
            ? StringSupport::changeCase((string) $app, $this->pathCaseType) . '::' . (string) $name
            : (string) $name;
    }

    /**
     * Build a config usage example.
     *
     * @param string $path       The file to generate an example for.
     * @param string $configName The name the config file is referred to as.
     * @return string|null
     */
    private function buildExample(string $path, string $configName): ?string
    {
        try {
            $configData = require($path);
            if (is_array($configData)) {
                $key = collect(Arr::dot($configData))->keys()->first();
                return "config('{$configName}.{$key}');";
            }
        } catch (Throwable $e) {
        }
        return null;
    }
}
