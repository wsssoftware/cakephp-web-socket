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
     * @var \WebSocket\Server\ConsoleIoLogger
     */
    private ConsoleIoLogger $logger;

    /**
     *
     */
    public function __construct(ConsoleIoLogger $logger)
    {
       $this->logger = $logger;
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
     * @param \WebSocket\Server\Connection[] $connections
     */
    abstract public function loop(array $connections): void;

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
     * @param \WebSocket\Server\Connection[] $connections
     * @return void
     */
    public function run(array $connections): void
    {
        $now = round(microtime(true) * 1000);
        if ($now - $this->lastRun < $this->interval) {
            return;
        }

        $this->lastRun = $now;
        $this->loop($connections);
    }
}