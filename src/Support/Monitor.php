<?php

namespace CodeDistortion\LaravelAutoReg\Support;

use Illuminate\Support\Collection;

/**
 * Allow things to be timed.
 */
class Monitor
{
    /** @var array<string, boolean> Timers that exist but may not have started yet. */
    private array $exists = [];

    /** @var array<string, float> When the timers were started. */
    private array $start = [];

    /** @var array<string, float> When the timers were stopped. */
    private array $stop = [];

    /** @var array<string, integer> How many things were registered? */
    private array $regCount = [];

    /** @var array<string, boolean> The list of this that ran while testing. */
    private array $whatRan = [];




    /**
     * Specify that a timer exists, even if it hasn't been started yet.
     *
     * @param string $name The timer to record.
     * @return $this
     */
    public function timerExists(string $name): self
    {
        $this->exists[$name] = true;
        return $this;
    }

    /**
     * Start a timer.
     *
     * @param string $name The timer to start.
     * @return $this
     */
    public function startTimer(string $name): self
    {
        $this->start[$name] = microtime(true);
        return $this;
    }

    /**
     * Stop a timer.
     *
     * @param string $name The timer to stop.
     * @return $this
     */
    public function stopTimer(string $name): self
    {
        $this->stop[$name] = microtime(true);
        return $this;
    }

    /**
     * Read a timer's value.
     *
     * @param string $name The timer to read.
     * @return float|null
     */
    public function readTimer(string $name): ?float
    {
        if (!isset($this->start[$name])) {
            return isset($this->exists[$name]) ? 0 : null;
        }

        return ($this->stop[$name] ?? microtime(true)) - $this->start[$name];
    }

    /**
     * Get a collection of all the timers.
     *
     * @return Collection|float[]
     */
    public function getAllTimers(): Collection
    {
        $return = [];
        foreach (array_keys($this->exists) as $name) {
            $return[$name] = 0;
        }
        foreach ($this->start as $name => $start) {
            $return[$name] = ($this->stop[$name] ?? microtime(true)) - $start;
        }
        return collect($return);
    }



    /**
     * Keep count of the number of things registered.
     *
     * @param string  $name  The name of the thing being counted.
     * @param integer $count The amount to increase by.
     * @return void
     */
    public function incRegCount(string $name, int $count = 1): void
    {
        $this->regCount[$name] ??= 0;
        $this->regCount[$name] += $count;
    }

    /**
     * Get the count of registered things.
     *
     * @param string $name The name of the thing being counted.
     * @return integer
     */
    public function getRegCount(string $name): int
    {
        return $this->regCount[$name] ?? 0;
    }



    /**
     * Record that something ran.
     *
     * (Used in testing).
     *
     * @param string $name The name of the thing that ran.
     * @return $this
     */
    public function iRan(string $name): self
    {
        $this->whatRan[$name] = true;
        return $this;
    }

    /**
     * Check if something ran.
     *
     * (Used in testing).
     *
     * @param string $name The name of the thing to check.
     * @return boolean
     */
    public function didThisRun(string $name): bool
    {
        return $this->whatRan[$name] ?? false;
    }
}
