<?php

namespace CodeDistortion\LaravelAutoReg\Core;

use CodeDistortion\LaravelAutoReg\Support\BasicFile;
use CodeDistortion\LaravelAutoReg\Support\FileList;
use CodeDistortion\LaravelAutoReg\Support\MatchedFileDTO;
use CodeDistortion\LaravelAutoReg\Support\PathPattern;
use Illuminate\Support\Collection;

/**
 * Picks out a certain type of file from the list of files in the file-system.
 */
abstract class ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = '';

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [null];

    /** @var boolean Is more than one match allowed per app? */
    protected bool $canHaveMultipleMatchesPerApp = true;

    /** @var boolean Is this allowd to match files that aren't in an app? */
    protected bool $allowNullApp = true;



    /** @var boolean Whether this resolvable is enabled or not. */
    private bool $enabled;

    /** @var Collection|PathPattern[] The path-patterns to use when picking out files. */
    private Collection $pathPatterns;

    /** @var string The case-type to use in the config/translator/view/view-component path. */
    protected string $pathCaseType = 'snake';



    /** @var array<int, array<string, string|null>> The resolved meta information about the detected resources. */
    protected array $metaData = [];

    /** @var mixed[] The resolved data used to register the resources. */
    protected array $regData = [];



    /**
     * @param Collection|PathPattern[] $pathPatterns The path-patterns to match against.
     * @param boolean                  $enabled      Whether this file-type is enabled or not.
     */
    public function __construct(Collection $pathPatterns, bool $enabled)
    {
        $this->setPathPatterns($pathPatterns);
        $this->enabled = $enabled;
    }



    /**
     * Store the PathPatterns to use.
     *
     * @param Collection|PathPattern[] $pathPatterns The path-patterns to match against.
     * @return void
     */
    private function setPathPatterns(Collection $pathPatterns): void
    {
        $pathPatterns
            ->each(fn(PathPattern $pathPattern) => $pathPattern->setMatchClasses($this->matchClasses))
            ->each(fn(PathPattern $pathPattern) => $this->customisePathPatterns($pathPattern));
        $this->pathPatterns = $pathPatterns;
    }

    /**
     * Customise the regexes in a PathPattern to match things for this file-type.
     *
     * @param PathPattern $pathPattern The PathPattern to update.
     * @return void
     */
    protected function customisePathPatterns(PathPattern $pathPattern): void
    {
        // to be overridden…
    }



    /**
     * Set the case-type to use in the config/translator/view/view-component path.
     *
     * @param string $pathCaseType The case type to use - "snake", "kebab", "camel" or "pascal".
     * @return $this
     */
    public function setPathCaseType(string $pathCaseType): self
    {
        $this->pathCaseType = $pathCaseType;
        return $this;
    }



    /**
     * Get the file-type.
     *
     * @return string
     */
    public function getType(): string
    {
        return static::FILE_TYPE;
    }

    /**
     * Get the meta-data about the detected files.
     *
     * @return array<int, array<string, string|null>>
     */
    public function getMetaData(): array
    {
        return $this->metaData;
    }

    /**
     * Get the detected files' resolved data.
     *
     * @return mixed[]
     */
    public function getRegData(): array
    {
        return $this->regData;
    }



    /**
     * Look through the files system and pick the relevant files.
     *
     * @param Collection|FileList[] $allFilesList The list of files => FQCN's to look through - grouped by
     *                                            source-dir.
     * @param string[]              $detectedApps The list of apps that have already been identified.
     *                                            (if the app can't be identified for this type of file, these
     *                                            will be checked against to help resolve it).
     * @param boolean               $needMeta     Should the meta data be generated?.
     * @return void
     */
    public function resolve(Collection $allFilesList, array $detectedApps, bool $needMeta): void
    {
        $this->metaData = $this->regData = [];

        if (!$this->enabled) {
            return;
        }

        sort($detectedApps); // make sure the more specific ones are checked against first.

        $matchedFileDTOs = $this->reduceToOneMatchPerApp(
            $this->pickBestAppForMatches(
                $this->pickFilesThatMatchPatterns($allFilesList, $detectedApps)
            )
        );

        $matchedFileDTOs->each(fn(MatchedFileDTO $matchedFileDTO) => $this->addRegData($matchedFileDTO));
        if ($needMeta) {
            $matchedFileDTOs->each(fn(MatchedFileDTO $matchedFileDTO) => $this->addMetaData($matchedFileDTO));
        }
    }

    /**
     * Look through the list of all files and pick ones that match the search-patterns.
     *
     * @param Collection|FileList[] $allFilesList The list of files => FQCN's to look through - grouped by
     *                                            source-dir.
     * @param string[]              $detectedApps The list of apps that have already been identified.
     *                                            (if the app can't be identified for this type of file, these
     *                                            will be checked against to help resolve it).
     * @return Collection|MatchedFileDTO[]
     */
    private function pickFilesThatMatchPatterns(Collection $allFilesList, array $detectedApps): Collection
    {
        $matched = collect();
        foreach ($this->pathPatterns as $pathPattern) {
            foreach ($allFilesList as $fileList) {

                /** @var FileList $fileList */

                $matchedFiles = $pathPattern->pickRelevantFiles(
                    $fileList->getLocalSourceDir(),
                    $fileList->getPathList()
                );
                // add the wildcard path in
                $matchedFiles->each(
                    fn(BasicFile $basicFile) => $basicFile->starsAndAfterPath = $pathPattern->pickStarsAndAfterPath(
                        $basicFile->appPath
                    )
                );

                $groupedByCondensedPath = $pathPattern->groupByCondensedPath($matchedFiles);
                $condensedPaths = $pathPattern->condenseFiles($matchedFiles);

                $matched[] = $condensedPaths->map(
                    function (BasicFile $basicFile) use (
                        $fileList,
                        $pathPattern,
                        $groupedByCondensedPath
                        // $detectedApps
                    ) {

                        $app = $pathPattern->pickAppName($basicFile->appPath);
                        // $app = $pathPattern->pickAppName($basicFile->appPath)
                        //    ?? $pathPattern->pickFallbackApp($detectedApps, $basicFile->appPath);

                        if ((!$this->allowNullApp) && (is_null($app))) {
                            return null;
                        }

                        return new MatchedFileDTO(
                            $fileList->getSourceAlias() ?? $fileList->getLocalSourceDir(),
                            $app,
                            $pathPattern->pickAfterStarDir($basicFile->appPath),
                            $pathPattern->pickName($basicFile->appPath),
                            $basicFile->path,
                            $basicFile->localPath,
                            $basicFile->fqcn,
                            $groupedByCondensedPath[$basicFile->localPath]
                        );
                    }
                )
                ->filter(); // remove any that weren't included because their $app was null
            }
        }
        return $matched->flatten(1);
    }

    /**
     * Pick the files to use - when several patterns matched the same path, pick the most relevant one.
     *
     * @param Collection|MatchedFileDTO[] $matched Info about each file that was matched by a pattern.
     * @return Collection|MatchedFileDTO[]
     */
    private function pickBestAppForMatches(Collection $matched): Collection
    {
        // group the apps per path
        $appsPerPath = [];
        foreach ($matched as $matchedFileDTO) {
            /** @var MatchedFileDTO $matchedFileDTO */
            $appsPerPath[$matchedFileDTO->path][] = $matchedFileDTO->app;
        }

        // pick the most relevant app per path
        $allowedAppPerPath = collect($appsPerPath)->mapWithKeys(function ($apps, $name) {
            asort($apps); // pick the app with the shortest name
            return [$name => reset($apps)];
        });

        // remove the files that aren't allowed
        return $matched
            ->filter(
                fn(MatchedFileDTO $matchedFileDTO) => $matchedFileDTO->app == $allowedAppPerPath[$matchedFileDTO->path]
            )
            ->values();
    }

    /**
     * If only one path is allowed per-app, pick one when more than one is found.
     *
     * @param Collection|MatchedFileDTO[] $matched Info about each file that was matched by a pattern.
     * @return Collection|MatchedFileDTO[]
     */
    private function reduceToOneMatchPerApp(Collection $matched): Collection
    {
        if ($this->canHaveMultipleMatchesPerApp) {
            return $matched;
        }

        // pick the first one for each app
        $pickedApps = [];
        return $matched->filter(function (MatchedFileDTO $matchedFileDTO) use (&$pickedApps) {
            $alreadyUsed = isset($pickedApps[$matchedFileDTO->app]);
            $pickedApps[$matchedFileDTO->app] = true;
            return !$alreadyUsed;
        });
    }



    /**
     * Add this file's meta data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addMetaData(MatchedFileDTO $matchedFileDTO): void
    {
        // may be overridden…

        $this->metaData[] = [
            'source' => $matchedFileDTO->source,
            'app' => $matchedFileDTO->app,
            'type' => static::FILE_TYPE,
            'path' => $matchedFileDTO->localPath,
            'example' => null,
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
        // may be overridden…

        $this->regData[] = ltrim($matchedFileDTO->localPath, '/');
    }
}
