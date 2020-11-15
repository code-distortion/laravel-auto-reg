<?php

namespace CodeDistortion\LaravelAutoReg\Commands;

use CodeDistortion\LaravelAutoReg\Core\Detect;
use CodeDistortion\LaravelAutoReg\Support\RendersTablesTrait;
use CodeDistortion\LaravelAutoReg\Support\Settings;
use CodeDistortion\LaravelAutoReg\Support\Table;
use CodeDistortion\LaravelAutoReg\Support\Monitor;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Lists the files and classes this package resolves.
 */
class StatsCommand extends Command
{
    use RendersTablesTrait;

    /** @var string The name and signature of the console command. */
    protected $signature = 'auto-reg:stats';

    /** @var string The console command description. */
    protected $description = 'List the time it takes Auto-Reg register your resources';



    /**
     * Execute the console command.
     *
     * @param Monitor $timer The timer that was used to time registrations.
     * @return void
     */
    public function handle(Monitor $timer): void
    {
        $initTime = $timer->readTimer('resource detection');
        $extraTime = $initTime // extra time that occurs outside of "total"
            + $timer->readTimer('register ' . Settings::TYPE__ROUTE_API_FILE)
            + $timer->readTimer('register ' . Settings::TYPE__ROUTE_WEB_FILE);
        $totalTime = $timer->readTimer('total') + $extraTime;

        $times = $timer->getAllTimers()
            ->reject(fn($time, $name) => in_array($name, ['resource detection', 'total']))
            ->sortKeys()
            ->map(fn($time, $name) => [
                $name,
                number_format($time * 1000, 3) . 'ms',
                $timer->getRegCount(Str::of($name)->replaceFirst('register ', ''))
            ])
            ->values();

        $totalRegistrations = $times->pluck(2)->sum();

        $rows = collect()
            ->merge([['resource detection', number_format($initTime * 1000, 3) . 'ms', null]])
            ->merge($times)
            ->merge([['total', number_format($totalTime * 1000, 3) . 'ms', $totalRegistrations]]);

        $table = new Table(
            null,
            ['Action', 'Time taken', '# registrations'],
            $rows->all()
        );

        $cached = app(Detect::class)->wasLoadedFromCache();
        $this->line('');
        $this->warn("Laravel Auto-Reg cache status: " . ($cached ? "CACHED" : "NOT CACHED"));

        $this->renderTable($table);
    }
}
