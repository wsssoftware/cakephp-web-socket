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
     * @var string|null
     */
    protected ?string $wrap = null;

    /**
     * @var \Cake\Console\ConsoleIo
     */
    protected ConsoleIo $io;

    /**
     * @var bool
     */
    protected bool $messagePrefix = true;

    /**
     * @param \Cake\Console\ConsoleIo $io Cake ConsoleIo
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
     * @param int $amount amount of lines to break
     * @return void
     */
    public function lineBreak(int $amount = 1): void
    {
        for ($i = 1; $i <= $amount; $i++) {
            $this->io->out();
        }
    }

    /**
     * @param \WebSocket\Server\Connection $connection WebSocket connection instance
     * @return self
     */
    public function wrapConnection(Connection $connection): ConsoleIoLogger
    {
        $this->wrap = sprintf(
            '[client %s:%s] %s',
            $connection->getIp(),
            $connection->getPort(),
            '%s'
        );

        return $this;
    }

    /**
     * @param mixed $level Log level
     * @param string $message Log message
     * @param array $context Context of log
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->wrap !== null) {
            $message = sprintf($this->wrap, $message);
            $this->wrap = null;
        }

        if ($this->messagePrefix) {
            $message = sprintf(
                '%s [%s] %s',
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
