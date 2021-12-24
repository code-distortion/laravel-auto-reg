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
use Illuminate\View\Component;

/**
 * Picks out view-component-class files.
 */
class ViewComponentResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__VIEW_COMPONENT_CLASS;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [Component::class];

    /** @var boolean Is this allowed to match files that aren't in an app?. */
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
//        $pathPattern->setPickFullPathRegex("/^(.+{$dirBeforeStar})\/((?:[^\/]*\/)+)?(.*\.[^\.\/]*)$/");

        $pathPattern->setPickAppRegex("/^(.+)\/{$dir}/");
        $pathPattern->setPickNameRegex(null);
    }



    /**
     * Add this file's meta-data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addMetaData(MatchedFileDTO $matchedFileDTO): void
    {
        $this->metaData[] = [
            'source' => $matchedFileDTO->source,
            'app' => $matchedFileDTO->app,
            'type' => static::FILE_TYPE,
            'path' => $matchedFileDTO->localPath,
            'example' => $this->buildExamples($matchedFileDTO->app, $matchedFileDTO->matched)->implode("\n"),
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
        $this->regData[$matchedFileDTO->app] = [$this->alterApp($matchedFileDTO->app), $matchedFileDTO->fqcn];
    }



    /**
     * Tweak the App's name, ready for registering.
     *
     * @param string|null $app The original app name.
     * @return string
     */
    private function alterApp(?string $app): string
    {
        return StringSupport::changeCase(str_replace('_', '-', (string) $app), 'kebab');
    }

    /**
     * Build usage examples of all of the given view-component files.
     *
     * @param string|null            $app      The app the view-component is being registered in.
     * @param Collection|BasicFile[] $allFiles The view-component files.
     * @return Collection|string[]
     */
    private function buildExamples(?string $app, Collection $allFiles): Collection
    {
        return collect(
            $allFiles->map(
                fn(BasicFile $basicFile) => $this->buildExample($app, (string) $basicFile->starsAndAfterPath)
            )
                ->sort()
                ->values()
        );
    }

    /**
     * Build a view-component-class usage example.
     *
     * @param string|null $app  The app the view-component is being registered in.
     * @param string      $path The class to generate an example for.
     * @return string
     */
    private function buildExample(?string $app, string $path): string
    {
        $pathParts = pathinfo($path);
        $path = Str::of(
            ($pathParts['dirname'] != '.')
                ? "{$pathParts['dirname']}/{$pathParts['filename']}"
                : $pathParts['filename']
        );

        return "<x-{$this->alterApp($app)}::{$this->buildName($path)} />";
    }

    /**
     * Build a blade template's name.
     *
     * @param string $path The path to the blade template (inside the view directory).
     * @return string
     */
    private function buildName(string $path): string
    {
        return mb_strtolower(StringSupport::changeCase(str_replace('/', '.', $path), 'kebab'));
    }
}
