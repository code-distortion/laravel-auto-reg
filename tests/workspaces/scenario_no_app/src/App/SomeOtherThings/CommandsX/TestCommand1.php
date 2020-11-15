<?php

namespace CodeDistortion\LaravelAutoReg\Tests\ScenarioNoApp\SomeOtherThings\CommandsX;

use Illuminate\Console\Command;

/**
 * A command for testing purposes.
 */
class TestCommand1 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test-command1';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('TEST COMMAND 1');
    }
}
