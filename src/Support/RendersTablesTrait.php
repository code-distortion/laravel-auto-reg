<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Allow thing to be timed.
 *
 * @mixin Command
 */
trait RendersTablesTrait
{
    /**
     * Output a table.
     *
     * @param Table $table The table to show.
     * @return void
     */
    protected function renderTable(Table $table): void
    {
        $this->renderTables(collect([$table]));
    }

    /**
     * Output the tables.
     *
     * @param Collection|Table[] $tables The tables to show.
     * @return void
     */
    protected function renderTables(Collection $tables): void
    {
        $tables->each(function ($table) {

            $this->line('');

            if ($table->getName()) {
                $this->info($table->getName());
                $this->line('');
            }

            $this->table($table->getHeaders(), $table->getRows());
        });
        $this->line('');
    }
}
