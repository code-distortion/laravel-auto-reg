<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\MatchedFileDTO;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use Illuminate\Console\Command;

/**
 * Picks out command-class files.
 */
class CommandClassResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__COMMAND_CLASS;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [Command::class];



    /**
     * Add this file's meta data to the list.
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
            'example' => $this->buildExample((string) $matchedFileDTO->fqcn),
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
        $this->regData[] = $matchedFileDTO->fqcn;
    }



    /**
     * Build a command-class usage example.
     *
     * @param string $fqcn The class to generate an example for.
     * @return string
     */
    private function buildExample(string $fqcn): string
    {
        return "php artisan " . (new $fqcn())->getName();
    }
}
