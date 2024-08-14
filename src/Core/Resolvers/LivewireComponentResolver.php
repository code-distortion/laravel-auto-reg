<?php

namespace CodeDistortion\LaravelAutoReg\Core\Resolvers;

use CodeDistortion\LaravelAutoReg\Core\ResolverAbstract;
use CodeDistortion\LaravelAutoReg\Support\MatchedFileDTO;
use CodeDistortion\LaravelAutoReg\Support\PathPattern;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Support\StringSupport;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Livewire;

/**
 * Picks out livewire-component-class files.
 */
class LivewireComponentResolver extends ResolverAbstract
{
    /** @var string The file type this resolvable detects. */
    public const FILE_TYPE = Settings::TYPE__LIVEWIRE_COMPONENT_CLASS;

    /** @var array<int, string|null> The classes that files must match (when not empty. null means "no class"). */
    protected array $matchClasses = [Component::class];

    /** @var boolean Is this allowed to match files that aren't in an app?. */
    protected bool $allowNullApp = false;



    /**
     * @param Collection|PathPattern[] $pathPatterns The path-patterns to match against.
     * @param boolean                  $enabled      Whether this file-type is enabled or not.
     */
    public function __construct(Collection $pathPatterns, bool $enabled)
    {
        $enabled = $enabled && class_exists(Livewire::class);
        parent::__construct($pathPatterns, $enabled);
    }



    /**
     * Add this file's meta-data to the list.
     *
     * @param MatchedFileDTO $matchedFileDTO Information about the file.
     * @return void
     */
    protected function addMetaData(MatchedFileDTO $matchedFileDTO): void
    {
        $regName = $this->buildRegName($matchedFileDTO->app, $matchedFileDTO->afterStarDir, $matchedFileDTO->name);

        $this->metaData[] = [
            'source' => $matchedFileDTO->source,
            'app' => $matchedFileDTO->app,
            'type' => static::FILE_TYPE,
            'path' => $matchedFileDTO->localPath,
            'example' => $this->buildExample($regName),
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
        $regName = $this->buildRegName($matchedFileDTO->app, $matchedFileDTO->afterStarDir, $matchedFileDTO->name);

        $this->regData[$regName] = $matchedFileDTO->fqcn;
    }



    /**
     * Prepare the name of this component to register.
     *
     * @param string|null $app          The app's name.
     * @param string|null $afterStarDir The sub-directory - including and after any "*" characters.
     * @param string|null $name         The component's name.
     * @return string
     */
    private function buildRegName(?string $app, ?string $afterStarDir, ?string $name): string
    {
        $afterStarDir = str_replace('/', '.', (string) $afterStarDir);
        $regName = $afterStarDir
            ? $app . '::' . StringSupport::changeCase($afterStarDir, 'kebab') . '.' . (string) $name
            : $app . '::' . (string) $name;

        return StringSupport::changeCase(str_replace('.', '.', $regName), 'kebab');
    }

    /**
     * Build a livewire-component-class usage example.
     *
     * @param string $regName The app the view-component is being registered in.
     * @return string
     */
    private function buildExample(string $regName): string
    {
        return "<livewire:{$regName} />";
    }
}
