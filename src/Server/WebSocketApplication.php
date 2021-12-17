<?php
declare(strict_types=1);

namespace WebSocket\Server;

use Cake\I18n\FrozenTime;
use Cake\Utility\Security;
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
     * @param Connection $connection
     */
    public function onData(string $data, Connection $connection): void
    {
        $data = $connection->decodeData($data);
        if (!empty($data['initialPayload'])) {
            $this->processInitialPayload($data, $connection);
        }
        $this->getLogger()->info('dsdsa');
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

    /**
     * @param array $data
     * @param \WebSocket\Server\Connection $connection
     */
    protected function processInitialPayload(array $data, Connection $connection): void
    {
        $payload = urldecode($data['initialPayload']);
        $payload = Security::decrypt($payload, Security::getSalt());
        if ($payload === null) {
            $this->getLogger()->warning('Wrong/missing identify payload intent. Performing disconnect...');
            $connection->close(1008);
        }
        $payload = json_decode($payload, true);
        if (empty($payload['sessionId']) || empty($payload['userId']) || empty($payload['routeMd5']) || empty($payload['expires'])) {
            $this->getLogger()->warning('Missing data identify payload intent. Performing disconnect...');
            $connection->close(1008);
        }
        $expires = FrozenTime::parse($payload['expires']);
        if ($expires->isPast()) {
            $this->getLogger()->warning('Identify payload intent is expired. Performing disconnect...');
            $connection->close(1008);
        }
        $connection->setSessionId($payload['sessionId']);
        $connection->setUserId($payload['userId']);
        $connection->setRouteMd5($payload['routeMd5']);
    }
}