<?php
declare(strict_types=1);

namespace WebSocket\Server;

use JetBrains\PhpStorm\Pure;

/**
 * Class Application
 * Created by allancarvalho in dezembro 16, 2021
 */
class WebSocketApplication
{

    /**
     * @var \WebSocket\Server\Server
     */
    private Server $server;

    /**
     * Holds client connected to the status application.
     *
     * @var Connection[] $connections
     */
    protected array $connections = [];

    /**
     * @param \WebSocket\Server\Server $server
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @var \WebSocket\Server\WebSocketApplication
     */
    protected static WebSocketApplication $instance;

    /**
     * @return \WebSocket\Server\ConsoleIoLogger
     */
    #[Pure]
    public function getLogger(): ConsoleIoLogger
    {
        return $this->server->getLogger();
    }

    /**
     * This method is triggered when a new client connects to server/application.
     *
     * @param Connection $connection
     */
    public function onConnect(Connection $connection): void
    {
        $id = $connection->getId();
        $this->connections[$id] = $connection;
    }

    /**
     * This method is triggered when a client disconnects from server/application.
     *
     * @param Connection $connection
     */
    public function onDisconnect(Connection $connection): void
    {
        $id = $connection->getId();
        unset($this->connections[$id]);
    }

    /**
     * This method is triggered when the server receive new data from a client.
     *
     * @param string $data
     * @param Connection $connection
     */
    public function onData(string $data, Connection $connection): void
    {
        $this->getLogger()->info($data);
        $connection->send(json_encode(['controller' => 'test', 'action' => 'test2', 'payload' => ['dsa', 'dsada']]));
    }

    /**
     * This method is called when server receive to for an application on the IPC socket.
     *
     * @param array $data
     */
    public function onIPCData(array $data): void
    {

    }
}