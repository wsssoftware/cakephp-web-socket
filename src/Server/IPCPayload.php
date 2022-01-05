<?php
declare(strict_types=1);

namespace WebSocket\Server;

use Cake\Error\FatalErrorException;
use JetBrains\PhpStorm\Pure;

/**
 * Class IPCPayload
 *
 * Created by allancarvalho in dezembro 16, 2021
 */
class IPCPayload
{
    /**
     * @var string $controller
     */
    protected string $controller;

    /**
     * @var string $action
     */
    protected string $action;

    /**
     * Actual payload.
     *
     * @var array $payload
     */
    protected array $payload;

    /**
     * filters.
     *
     * @var array $filters
     */
    protected array $filters;

    /**
     * @param string $controller Controller name
     * @param string $action Action name
     * @param array $payload Payload datta
     * @param array $filters Filters to define how will receive the message.
     */
    public function __construct(string $controller, string $action, array $payload, array $filters = [])
    {
        $this->controller = $controller;
        $this->action = $action;
        $this->payload = $payload;
        $this->filters = $filters;
    }

    /**
     * Returns payload as json encoded string.
     *
     * @return string
     */
    public function asJson(): string
    {
        $payload = json_encode([
            'controller' => $this->controller,
            'action' => $this->action,
            'payload' => $this->payload,
            'filters' => $this->filters,
        ]);
        if ($payload === false) {
            throw new FatalErrorException('Error while encoding payload');
        }

        return $payload;
    }

    /**
     * Creates payload object from json encoded string.
     *
     * @param string $json Json to decode in a IPCPayload instance
     * @return self
     */
    public static function fromJson(string $json): IPCPayload
    {
        $data = json_decode($json, true);

        return new IPCPayload($data['controller'], $data['action'], $data['payload'], $data['filters']);
    }

    /**
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param \WebSocket\Server\Connection $connection The WebSocket connection resource to check
     * @return bool
     */
    #[Pure]
    public function isConnectionInsideFilters(Connection $connection): bool
    {
        $routeMd5 = $connection->getRouteMd5();
        if ($routeMd5 === null) {
            return false;
        }
        if (!empty($this->filters['sessionIds'])) {
            $isInSessionList = false;
            foreach ($this->filters['sessionIds'] as $sessionId) {
                if ($sessionId === $connection->getSessionId()) {
                    $isInSessionList = true;
                    break;
                }
            }
        }
        if (isset($isInSessionList) && $isInSessionList === false) {
            return false;
        }

        if (!empty($this->filters['userIds'])) {
            $isInUserList = false;
            foreach ($this->filters['userIds'] as $userId) {
                if ($userId === $connection->getUserId()) {
                    $isInUserList = true;
                    break;
                }
            }
        }
        if (isset($isInUserList) && $isInUserList === false) {
            return false;
        }

        if (!empty($this->filters['routesMd5'])) {
            $isInRouteList = false;
            $userRoute = explode('.', $routeMd5);
            foreach ($this->filters['routesMd5'] as $routeMd5) {
                $routeMd5 = explode('.', $routeMd5);
                if (
                    $userRoute[0] === $routeMd5[0] &&
                    ($userRoute[1] === $routeMd5[1] || $routeMd5[1] === 'none') &&
                    ($userRoute[2] === $routeMd5[2] || $routeMd5[2] === 'none')
                ) {
                    $isInRouteList = true;
                    break;
                }
            }
        }
        if (isset($isInRouteList) && $isInRouteList === false) {
            return false;
        }

        return true;
    }
}
