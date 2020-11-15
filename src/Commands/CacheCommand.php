<?php

namespace CodeDistortion\LaravelAutoReg\Commands;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use Illuminate\Console\Command;

/**
 * Save the resolved files and config values to cache.
 */
class CacheCommand extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'auto-reg:cache';

    /** @var string The console command description. */
    protected $description = 'Create an Auto-Reg cache file for faster resolution of resources';



    /**
     * Execute the console command.
     *
     * @param Detect $detect The detection service.
     * @return void
     */
    public function handle(Detect $detect): void
    {
        $this->call('auto-reg:clear');

        $detect->loadFresh(true);

        if (!$detect->resourcesWereDetected()) {
            $this->warn('No resources were detected.');
        }

        $detect->saveCache();

        $this->info('Auto-Reg cached successfully!');
    }
}
