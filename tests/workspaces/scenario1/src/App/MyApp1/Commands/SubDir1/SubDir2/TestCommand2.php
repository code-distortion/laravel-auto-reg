<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Scenario1App\MyApp1\Commands\SubDir1\SubDir2;

use Illuminate\Console\Command;

/**
 * A command for testing purposes.
 */
class TestCommand2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test-command2';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('TEST COMMAND 2');
    }
}
