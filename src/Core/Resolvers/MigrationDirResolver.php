<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\PathPattern;
use CodeDistortion\LaravelAutoReg\Support\Settings;

/**
 * Picks out migration directories.
 */
class MigrationDirResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__MIGRATION_DIRECTORY;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = []; // migration class namespaces aren't detected properly



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
}
