<?php
declare(strict_types=1);

namespace WebSocket\Server;

/**
 * Class TimerCollection
 *
 * Created by allancarvalho in dezembro 16, 2021
 */
class TimerCollection
{
    /**
     * @var \WebSocket\Server\Timer[] $timers
     */
    private array $timers;

    /**
     * @param array $timers Timers that compound this instance
     */
    public function __construct(array $timers = [])
    {
        $this->timers = $timers;
    }

    /**
     * Adds a timer.
     *
     * @param \WebSocket\Server\Timer $timer A timer instance
     * @return void
     */
    public function addTimer(Timer $timer): void
    {
        $this->timers[] = $timer;
    }

    /**
     * Executes/runs all timers.
     *
     * @param \WebSocket\Server\Connection[] $connections connection to put in timers call
     * @return void
     */
    public function runAll(array $connections): void
    {
        foreach ($this->timers as $timer) {
            $timer->run($connections);
        }
    }
}
