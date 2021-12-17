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
     * @var int|null $interval
     */
    private ?int $interval = null;

    /**
     * @var float $lastRun
     */
    private float $lastRun = 0;

    /**
     *
     */
    public function __construct()
    {
       $this->initialize();
    }

    /**
     * method called after constructor used to set interval
     */
    abstract public function initialize(): void;

    /**
     * @param int $interval
     */
    public function setInterval(int $interval): void
    {
        $this->interval = $interval;
    }

    /**
     * @param \WebSocket\Server\WebSocketApplication $webSocketApplication
     */
    abstract public function loop(WebSocketApplication $webSocketApplication): void;

    /**
     * @return ?int
     */
    public function getInterval(): ?int
    {
        return $this->interval;
    }

    /**
     * Executes the timer if interval has passed.
     *
     * @param \WebSocket\Server\WebSocketApplication $webSocketApplication
     * @return void
     */
    public function run(WebSocketApplication $webSocketApplication): void
    {
        $now = round(microtime(true) * 1000);
        if ($now - $this->lastRun < $this->interval) {
            return;
        }

        $this->lastRun = $now;
        $this->loop($webSocketApplication);
    }
}