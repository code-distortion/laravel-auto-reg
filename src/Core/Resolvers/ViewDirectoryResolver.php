<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\BasicFile;
use CodeDistortion\LaravelAutoReg\Support\MatchedFileDTO;
use CodeDistortion\LaravelAutoReg\Support\PathPattern;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Support\StringSupport;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Picks out view directories.
 */
class ViewDirectoryResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__VIEW_DIRECTORY;

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

        $this->metaData[] = [
            'source' => $matchedFileDTO->source,
            'app' => $matchedFileDTO->app,
            'type' => static::FILE_TYPE,
            'path' => $matchedFileDTO->localPath,
            'example' => $this->buildExamples($regNamespace, $matchedFileDTO->matched)->implode("\n"),
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
     * Build usage examples of all of the given blade files.
     *
     * @param string                 $regNamespace The namespace the templates are to be registered in.
     * @param Collection|BasicFile[] $allFiles     The blade template files.
     * @return Collection|string[]
     */
    private function buildExamples(string $regNamespace, Collection $allFiles): Collection
    {
        return collect(
            $allFiles->map(
                fn(BasicFile $basicFile) => $this->buildExample($regNamespace, (string) $basicFile->starsAndAfterPath)
            )
                ->sort()
                ->values()
        );
    }

    /**
     * Build a blade-template usage example.
     *
     * @param string $regNamespace The directory's namespace.
     * @param string $wildcardPath The path to the file within the project.
     * @return string
     */
    private function buildExample(string $regNamespace, string $wildcardPath): string
    {
        // .blade.php file
        $wildcardPath = Str::of($wildcardPath);
        if ($wildcardPath->endsWith('.blade.php')) {
            $wildcardPath = $wildcardPath->replaceLast('.blade.php', '');
        // some other type of file
        } else {
            $pathParts = pathinfo($wildcardPath);
            $wildcardPath = Str::of(
                ($pathParts['dirname'] != '.')
                    ? "{$pathParts['dirname']}/{$pathParts['filename']}"
                    : $pathParts['filename']
            );
        }

        // anonymous component file
        if ($wildcardPath->startsWith('components/')) {
            $wildcardPath = $wildcardPath->replaceFirst('components/', '');
            $wildcardPath = $this->buildName($wildcardPath);
            return "<x-{$regNamespace}::{$wildcardPath} />";
        }

        // regular template to be included
        $wildcardPath = $this->buildName($wildcardPath);
//        return "view('{$regNamespace}::$path'); or @include('{$regNamespace}::$path')";
        return "view('{$regNamespace}::$wildcardPath');";
    }

    /**
     * Build a blade template's name.
     *
     * @param string $path The path to the blade template (inside the view directory).
     * @return string
     */
    private function buildName(string $path): string
    {
        return str_replace('/', '.', $path);
    }
}
