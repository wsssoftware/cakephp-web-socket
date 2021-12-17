<?php
declare(strict_types=1);

namespace WebSocket\Server;

use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenTime;
use Psr\Log\AbstractLogger;
use RuntimeException;

/**
 * Class WebSocketConsoleIo
 * Created by allancarvalho in dezembro 16, 2021
 */
class ConsoleIoLogger extends AbstractLogger
{

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $io;

    /**
     * @var bool
     */
    protected bool $messagePrefix = true;

    /**
     *
     */
    public function __construct(ConsoleIo $io)
    {
        $this->io = $io;
    }

    /**
     * @return void
     */
    public function disableMessagePrefix(): void
    {
        $this->messagePrefix = false;
    }

    /**
     * @return void
     */
    public function enableMessagePrefix(): void
    {
        $this->messagePrefix = true;
    }

    /**
     * @param int $amount
     */
    public function lineBreak(int $amount = 1): void
    {
        for ($i = 1; $i <= $amount; $i++) {
            $this->io->out();
        }
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->messagePrefix) {
            $message = sprintf(
                "%s [%s] %s",
                FrozenTime::now()->format('Y-m-d H:i:s'),
                strtoupper($level),
                $message
            );
        }

        match ($level) {
            'emergency', 'warning', 'critical', 'alert' => $this->io->warning($message),
            'error' => $this->io->error($message),
            'notice', 'info' => $this->io->info($message),
            'debug' => $this->io->out($message),
            default => throw new RuntimeException(sprintf('"%s" is not in valid log level list', $level))
        };

    }
}