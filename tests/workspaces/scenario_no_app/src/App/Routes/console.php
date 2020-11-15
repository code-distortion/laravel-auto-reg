<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Console\ClosureCommand;

Artisan::command('test:test-closure-command-1', function () {
    $this->line('TEST CLOSURE COMMAND 1');
});
