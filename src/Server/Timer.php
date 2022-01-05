<?php
declare(strict_types=1);

namespace WebSocket\Server;

use Cake\I18n\Number;

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
     * @param \WebSocket\Server\ConsoleIoLogger $logger Logger to print outputs
     */
    public function __construct(ConsoleIoLogger $logger)
    {
        $this->logger = $logger;
        $this->initialize();
    }

    /**
     * method called after constructor used to set interval
     *
     * @return void
     */
    abstract public function initialize(): void;

    /**
     * @param int $interval Timer wanted interval
     * @return void
     */
    public function setInterval(int $interval): void
    {
        $this->interval = $interval;
        $resultLabel = sprintf(
            'REGISTERING TIMER "%s" with interval %sms',
            $this::class,
            Number::format($interval)
        );
        $size = 98 - strlen($resultLabel);
        $beforeSpace = (int)ceil($size / 2);
        $afterSpace = (int)floor($size / 2);
        $this->logger->info(
            '║' . str_repeat(' ', $beforeSpace) .
            $resultLabel . str_repeat(' ', $afterSpace) . '║'
        );
    }

    /**
     * @param \WebSocket\Server\Connection[] $connections Connections to use in loop
     * @return void
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
     * @param \WebSocket\Server\Connection[] $connections Connections to use in run
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
