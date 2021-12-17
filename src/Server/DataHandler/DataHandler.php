<?php
declare(strict_types=1);

namespace WebSocket\Server\DataHandler;

use WebSocket\Server\Connection;/**
 * Interface DataHandlerInterface
 * Created by allancarvalho in dezembro 16, 2021
 */
abstract class DataHandler
{

    protected Connection $connection;

    /**
     * @param \WebSocket\Server\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Decodes json payload received from stream.
     *
     * @param string $payload
     * @throws \RuntimeException
     * @return array
     */
    public function decodePayload(string $payload): array
    {
        $decodedPayload = json_decode($payload, true);
        if (empty($decodedPayload)) {
            throw new \RuntimeException('Could not decode payload.');
        }

        return $decodedPayload;
    }

    /**
     * Encodes payload to be sent to client.
     *
     * @param array $payload
     * @return string
     */
    public function encodePayload(array $payload): string
    {
        return json_encode($payload);
    }

    /**
     * Encode a payload data to string
     *
     * @param string $payload
     * @param string $type
     * @param bool $masked
     * @return string
     */
    abstract public function encode(string $payload, string $type = 'text', bool $masked = true): string;

    /**
     * Decode a string to a payload array
     *
     * @param string $data
     * @return array
     */
    abstract public function decode(string $data): array;
}