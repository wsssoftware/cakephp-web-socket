<?php
declare(strict_types=1);

namespace WebSocket\Server;

use Cake\I18n\FrozenTime;
use Cake\I18n\Number;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use JetBrains\PhpStorm\Pure;
use WebSocket\WebSocketController\WebSocketControllerFactory;

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
     * @var bool
     */
    protected bool $wasIdentified = false;

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
     * @throws \ReflectionException
     */
    public function onData(string $data, Connection $connection): void
    {
        $data = $connection->decodeData($data);
        if (!empty($data['initialPayload'])) {
            $this->processInitialPayload($data, $connection);
            return;
        }
        if ($this->wasIdentified === false) {
            $this->getLogger()->wrapConnection($connection)->warning('User trying to interact with server before identify. Disconnecting!');
            $connection->close(1008);
            return;
        }
        $plugin = Hash::get($data, 'plugin');
        $controller = Hash::get($data, 'controller');
        $action = Hash::get($data, 'action');
        $payload = Hash::get($data, 'payload');
        if ($plugin === null || $controller === null || $action === null || $payload === null) {
            $this->getLogger()->wrapConnection($connection)->warning('Ignoring message of connect due wrong format.');
            return;
        }
        $resultPayload = WebSocketControllerFactory::getInstance()->invoke($this->server, $this->getLogger(), $plugin, $controller, $action, $payload);
        if (is_array($resultPayload)) {
            $connection->sendPayload($controller, $action, $resultPayload);
        }
    }

    /**
     * This method is called when server receive to for an application on the IPC socket.
     *
     * @param \WebSocket\Server\IPCPayload $ipcPayload
     */
    public function onIPCData(IPCPayload $ipcPayload): void
    {
        $connections = $this->server->getConnections();
        $sent = 0;
        foreach ($connections as $connection) {
            if ($ipcPayload->isConnectionInsideFilters($connection)) {
                $connection->sendPayload(
                    $ipcPayload->getController(),
                    $ipcPayload->getAction(),
                    $ipcPayload->getPayload()
                );
                $sent++;
            }
        }
        $this->server->getLogger()->info(sprintf(
            'New push received and was sent to controller "%s" action "%s" with %s filter(s) to %s of %s connections.',
            $ipcPayload->getController(),
            $ipcPayload->getAction(),
            Number::format(count($ipcPayload->getFilters())),
            Number::format($sent),
            Number::format(count($connections))
        ));
    }

    /**
     * @param array $data
     * @param \WebSocket\Server\Connection $connection
     */
    protected function processInitialPayload(array $data, Connection $connection): void
    {
        $this->getLogger()->wrapConnection($connection)->info('Trying to set a identity for this connection...');
        $payload = urldecode($data['initialPayload']);
        $payload = Security::decrypt($payload, Security::getSalt());
        if ($payload === null) {
            $this->getLogger()->wrapConnection($connection)->warning('Wrong/missing identify payload intent. Performing disconnect...');
            $connection->close(1008);
        }
        $payload = json_decode($payload, true);
        if (empty($payload['sessionId']) || empty($payload['userId']) || empty($payload['routeMd5']) || empty($payload['expires'])) {
            $this->getLogger()->wrapConnection($connection)->warning('Missing data identify payload intent. Performing disconnect...');
            $connection->close(1008);
        }
        $expires = FrozenTime::parse($payload['expires']);
        if ($expires->isPast()) {
            $this->getLogger()->wrapConnection($connection)->warning('Identify payload intent is expired. Performing disconnect...');
            $connection->close(1008);
        }
        $connection->setSessionId($payload['sessionId']);
        $connection->setUserId($payload['userId']);
        $connection->setRouteMd5($payload['routeMd5']);
        $this->wasIdentified = true;
        $this->getLogger()->wrapConnection($connection)->info(sprintf('Identity setted! SessionId: %s | UserId: %s | RouteMd5: %s', $connection->getSessionId(), $connection->getUserId(), $connection->getRouteMd5()));
    }
}