<?php

namespace CodeDistortion\LaravelAutoReg\Commands;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use Illuminate\Console\Command;

/**
 * Remove the cache.
 */
class ClearCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'auto-reg:clear';

    /** @var string The console command description. */
    protected $description = 'Remove the Auto-Reg cache file';



    /**
     * Execute the console command.
     *
     * @param Detect $detect The detection service.
     * @return void
     */
    public function handle(Detect $detect): void
    {
        $detect->clearCache();
        $this->info('Auto-Reg cache cleared!');
    }
}
