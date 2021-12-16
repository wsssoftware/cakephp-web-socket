<?php
declare(strict_types=1);

namespace WebSocket\Server;

/**
 * Class Application
 *
 * Created by allancarvalho in dezembro 16, 2021
 */
class WebSocketApplication
{

    /**
     * @var \WebSocket\Server\Server
     */
    private Server $server;

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
     * This method is triggered when a new client connects to server/application.
     *
     * @param Connection $connection
     */
    public function onConnect(Connection $connection): void
    {

    }

    /**
     * This method is triggered when a client disconnects from server/application.
     *
     * @param Connection $connection
     */
    public function onDisconnect(Connection $connection): void
    {

    }

    /**
     * This method is triggered when the server receive new data from a client.
     *
     * @param string $data
     * @param Connection $client
     */
    public function onData(string $data, Connection $client): void
    {

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