<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\BasicFile;
use CodeDistortion\LaravelAutoReg\Support\MatchedFileDTO;
use CodeDistortion\LaravelAutoReg\Support\PathPattern;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Support\StringSupport;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\TextUI\XmlConfiguration\DirectoryCollection;
use Throwable;

/**
 * Picks out translation directories.
 */
class TranslationDirResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__TRANSLATION_DIRECTORY;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [null];

    /** @var boolean Is more than one match allowed per app? */
    protected bool $canHaveMultipleMatchesPerApp = false;

    /** @var boolean Is this allowd to match files that aren't in an app? */
    protected bool $allowNullApp = false;



    /**
     * Customise the regexes in a PathPattern to match things for this file-type.
     *
     * @param PathPattern $pathPattern The PathPattern to update.
     * @return void
     */
    protected function customisePathPatterns(PathPattern $pathPattern): void
    {
        $dir = $pathPattern->getDirRegexPart();
        $dirBeforeStar = $pathPattern->getDirBeforeStarRegexPart();

        // pick everything before "*" when present.
        $pathPattern->setPickFullPathRegex("/^(.+{$dirBeforeStar})\/.*(?:\.[^\.\/]*)?$/");

        $pathPattern->setPickAppRegex("/^(.+)\/{$dir}/");
        $pathPattern->setPickNameRegex(null);
    }



    /**
     * Add this file's meta data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addMetaData(MatchedFileDTO $matchedFileDTO): void
    {
        $regNamespace = $this->buildRegNamespace($matchedFileDTO->app);

        $firstMatched = $matchedFileDTO->matched->first();
        /** @var BasicFile $firstMatched */

        $this->metaData[] = [
            'source' => $matchedFileDTO->source,
            'app' => $matchedFileDTO->app,
            'type' => static::FILE_TYPE,
            'path' => $matchedFileDTO->localPath,
            'example' => $this->buildExample($regNamespace, $firstMatched->starsAndAfterPath, $firstMatched->path),
        ];
    }

    /**
     * Add this file's resolved data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addRegData(MatchedFileDTO $matchedFileDTO): void
    {
        $regNamespace = $this->buildRegNamespace($matchedFileDTO->app);

        $this->regData[$regNamespace] = ltrim($matchedFileDTO->localPath, '/');
    }



    /**
     * Prepare the name of this component to register.
     *
     * @param string|null $app The app's name.
     * @return string
     */
    private function buildRegNamespace(?string $app): string
    {
        return StringSupport::changeCase((string) $app, $this->pathCaseType);
    }

    /**
     * Build a config usage example.
     *
     * @param string      $regNamespace The name the config file is referred to as.
     * @param string|null $wildcardPath The "**" part of the path.
     * @param string      $path         The file to generate an example for.
     * @return string|null
     */
    private function buildExample(string $regNamespace, ?string $wildcardPath, string $path): ?string
    {
        try {
            $translationData = require($path);
            if (is_array($translationData)) {
                $key = collect(Arr::dot($translationData))->keys()->first();

                $temp = Str::of((string) $wildcardPath)->explode('/');
                $temp = tap($temp)->shift()->implode('/');

                $pathParts = pathinfo($temp);
                $temp = Str::of(
                    ($pathParts['dirname'] != '.')
                        ? "{$pathParts['dirname']}/{$pathParts['filename']}"
                        : $pathParts['filename']
                );

                return "__('{$regNamespace}::{$temp}.{$key}');";
            }
        } catch (Throwable $e) {
        }
        return null;
    }
}
