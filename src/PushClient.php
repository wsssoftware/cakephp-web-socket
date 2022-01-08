<?php
declare(strict_types=1);

namespace WebSocket;

use Cake\Error\FatalErrorException;
use Cake\Routing\Router;
use RuntimeException;
use WebSocket\Server\IPCPayload;
use WebSocket\Server\Server;

/**
 * Class PushClient
 *
 * Created by allancarvalho in dezembro 17, 2021
 */
class PushClient
{
    private const MAX_PAYLOAD_LENGTH = 65536;

    protected static PushClient $instance;

    /**
     * @return self
     */
    public static function getInstance(): PushClient
    {
        if (empty(self::$instance)) {
            self::$instance = new PushClient();
        }

        return self::$instance;
    }

    /**
     * @param string $controller Controller name
     * @param string $action Action name
     * @param array $payload Payload data
     * @param array $filters Filters to separate connections that will receive the message
     * @return bool
     */
    public function send(string $controller, string $action, array $payload, array $filters = []): bool
    {
        $filters += [
            'routes' => [],
            'sessionIds' => [],
            'userIds' => [],
        ];
        foreach ($filters as $filterName => $filter) {
            if (!is_array($filter)) {
                throw new FatalErrorException(sprintf('"%s" must to be an array.', $filterName));
            }
        }

        $filters['routesMd5'] = $this->normalizeRoutes($filters['routes']);
        unset($filters['routes']);

        $ipcPayload = new IPCPayload($controller, $action, $payload, $filters);

        return $this->sendPayloadToServer($ipcPayload);
    }

    /**
     * Check if socket is open
     *
     * @return bool
     */
    public function isSocketOpen(): bool
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new \RuntimeException('Could not open ipc socket.');
        }

        // @codingStandardsIgnoreStart
        return @socket_connect($socket, Server::IPC_SOCKET_PATH);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Pushes payload into the websocket server using a unix domain socket.
     *
     * @param \WebSocket\Server\IPCPayload $payload Payload data
     * @return bool
     */
    private function sendPayloadToServer(IPCPayload $payload): bool
    {
        $dataToSend = $payload->asJson();
        $dataLength = strlen($dataToSend);
        if ($dataLength > self::MAX_PAYLOAD_LENGTH) {
            throw new RuntimeException(
                sprintf(
                    'IPC payload exceeds max length of %d bytes. (%d bytes given.)',
                    self::MAX_PAYLOAD_LENGTH,
                    $dataLength
                )
            );
        }
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new RuntimeException('Could not open ipc socket.');
        }
        $bytesSend = socket_sendto($socket, $dataToSend, $dataLength, MSG_EOF, Server::IPC_SOCKET_PATH);
        if ($bytesSend <= 0) {
            throw new RuntimeException('Could not sent data to IPC socket.');
        }
        socket_close($socket);

        return true;
    }

    /**
     * @param array $routes Routes data to be normalized
     * @return array
     */
    protected function normalizeRoutes(array $routes): array
    {
        $params = ['controller', 'action', 'pass', 'plugin', '?'];
        $isOneRoute = false;
        foreach ($params as $param) {
            if (isset($routes[$param])) {
                $isOneRoute = true;
                break;
            }
        }
        if ($isOneRoute) {
            $routes = [$routes];
        }

        $request = Router::getRequest();
        if ($request === null && PHP_SAPI != 'cli') {
            throw new FatalErrorException('Request cannot be null');
        }
        foreach ($routes as $key => $route) {
            foreach ($route as $param => $value) {
                if (is_int($param)) {
                    if (empty($route['pass'])) {
                        $route['pass'] = [];
                    }
                    $routes[$key]['pass'][$param] = $value;
                    unset($routes[$key][$param]);
                }
            }
            $routes[$key] += [
                'ignorePass' => true,
                'ignoreQuery' => true,
            ];

            if ($request !== null) {
                $routes[$key] += [
                    'controller' => $request->getParam('controller'),
                    'action' => $request->getParam('action'),
                    'pass' => $request->getParam('pass'),
                    'prefix' => $request->getParam('prefix', false),
                    'plugin' => $request->getParam('plugin'),
                    '_matchedRoute' => $request->getParam('_matchedRoute'),
                    '?' => $request->getQuery(),
                ];
            }

            $routes[$key] = Utils::routeToMd5(
                $routes[$key],
                (bool)$routes[$key]['ignorePass'],
                (bool)$routes[$key]['ignoreQuery']
            );
        }

        return $routes;
    }
}
