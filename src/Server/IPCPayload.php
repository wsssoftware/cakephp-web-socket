<?php
declare(strict_types=1);

namespace WebSocket\Server;

/**
 * Class IPCPayload
 *
 * Created by allancarvalho in dezembro 16, 2021
 */
class IPCPayload
{

    /**
     * Server command to execute or application name to pass data to.
     *
     * @var string $action
     */
    public string $action;

    /**
     * Actual payload data.
     *
     * @var array $data
     */
    public array $data;

    /**
     * @param string $action
     * @param array $data
     */
    public function __construct(string $action, array $data = [])
    {
        $this->action = $action;
        $this->data = $data;
    }

    /**
     * Returns payload as json encoded string.
     *
     * @return string
     */
    public function asJson(): string
    {
        return json_encode([
            'action' => $this->action,
            'data' => $this->data,
        ]);
    }


    /**
     * Creates payload object from json ecoded string.
     *
     * @param string $json
     * @return IPCPayload
     */
    public static function fromJson(string $json): IPCPayload
    {
        $data = json_decode($json, true);
        return new IPCPayload($data['action'], $data['data']);
    }
}