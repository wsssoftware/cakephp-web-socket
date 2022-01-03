<?php
declare(strict_types=1);

namespace WebSocket\Server\DataHandler;

use WebSocket\Server\Connection;

/**
 * Interface DataHandlerInterface
 * Created by allancarvalho in dezembro 16, 2021
 */
abstract class DataHandler
{
    protected Connection $connection;

    /**
     * @param \WebSocket\Server\Connection $connection WebSocket Connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Encode a payload data to string
     *
     * @param string $payload Data Payload
     * @param string $type Message type
     * @param bool $masked masked option
     * @return string
     */
    abstract public function encode(string $payload, string $type = 'text', bool $masked = true): string;

    /**
     * Decode a string to a payload array
     *
     * @param string $data Data Payload
     * @return array
     */
    abstract public function decode(string $data): array;
}
