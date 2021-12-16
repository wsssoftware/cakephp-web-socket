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

    public function __construct(array $timers = [])
    {
        $this->timers = $timers;
    }

    /**
     * Adds a timer.
     *
     * @param Timer $timer
     */
    public function addTimer(Timer $timer): void
    {
        $this->timers[] = $timer;
    }

    /**
     * Executes/runs all timers.
     *
     * @param \WebSocket\Server\Server $server
     */
    public function runAll(Server $server): void
    {
        foreach ($this->timers as $timer) {
            $timer->run($server);
        }
    }
}