<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\MatchedFileDTO;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use Illuminate\Support\ServiceProvider;

/**
 * Picks out service-provider-class files.
 */
class ServiceProviderClassResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__SERVICE_PROVIDER_CLASS;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [ServiceProvider::class];



    /**
     * Add this file's resolved data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addRegData(MatchedFileDTO $matchedFileDTO): void
    {
        $this->regData[] = $matchedFileDTO->fqcn;
    }
}
