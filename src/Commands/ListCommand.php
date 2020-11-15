<?php

namespace CodeDistortion\LaravelAutoReg\Commands;

use CodeDistortion\LaravelAutoReg\Actions\TableGenerator;
use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\RendersTablesTrait;
use Illuminate\Console\Command;

/**
 * Lists the files and classes this package resolves.
 */
class ListCommand extends Command
{
    use RendersTablesTrait;

    /** @var string The name and signature of the console command. */
    protected $signature = 'auto-reg:list
        {--source=* : Only show files from these Source/s }
        {--app=* : Only show files for these App/s }
        {--type=* : Only show files of these Type/s }
        {--group-by=default : Split the files into multiple tables (app/type/none/default) }
        {--no-example : Don\'t show the example column }
    ';

    /** @var string The console command description. */
    protected $description = 'List the files and classes that Auto-Reg registers';



    /**
     * Execute the console command.
     *
     * @param Detect $detect The detection service.
     * @return void
     */
    public function handle(Detect $detect): void
    {
        $tables = (new TableGenerator($detect))
            ->limitToSource((array) $this->option('source'))
            ->limitToApp((array) $this->option('app'))
            ->limitToFileType((array) $this->option('type'))
            ->groupBy($this->resolveGroupBy())
            ->showExampleWhenAvailable(!$this->option('no-example'))
            ->generateTables();

        if ($tables->isEmpty()) {
            $this->warn('No resources were detected.');
            return;
        }

        $cached = app(Detect::class)->wasLoadedFromCache();
        $this->line('');
        $this->warn("Laravel Auto-Reg cache status: " . ($cached ? "CACHED" : "NOT CACHED"));

        $this->renderTables($tables);
    }

    /**
     * Resolve what the group-by is based on the input.
     *
     * @return string
     */
    private function resolveGroupBy(): string
    {
        $defaultGroupBy = ($this->option('type')
            ? 'none'
            : ($this->option('app')
                ? 'app'
                : 'source'
            )
        );

        // for phpstan
        $groupByOption = is_string($this->option('group-by'))
            ? $this->option('group-by')
            : null;

        $groupBy = $groupByOption == 'default' ? $defaultGroupBy : $groupByOption;

        return ['source' => 'source', 'app' => 'app', 'type' => 'type', 'none' => 'none'][$groupBy] ?? 'none';
    }
}
