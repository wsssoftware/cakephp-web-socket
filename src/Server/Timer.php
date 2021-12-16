<?php
declare(strict_types=1);

namespace WebSocket\Server;

/**
 * Class Timer
 *
 * Created by allancarvalho in dezembro 16, 2021
 */
abstract class Timer
{
    /**
     * @var int $interval
     */
    private int $interval = 5000;

    /**
     * @var float $lastRun
     */
    private float $lastRun = 0;

    /**
     * @param \WebSocket\Server\Server $server
     */
    abstract public function loop(Server $server): void;

    /**
     * @return int
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * Executes the timer if interval has passed.
     *
     * @param \WebSocket\Server\Server $server
     * @return void
     */
    public function run(Server $server): void
    {
        $now = round(microtime(true) * 1000);
        if ($now - $this->lastRun < $this->interval) {
            return;
        }

        $this->lastRun = $now;
        $this->loop($server);
    }
}