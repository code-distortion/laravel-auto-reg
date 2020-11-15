<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\Settings;

/**
 * Picks out command-closure files.
 */
class CommandClosureFileResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__COMMAND_CLOSURE_FILE;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [null];
}
