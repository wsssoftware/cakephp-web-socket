<?php
declare(strict_types=1);

namespace WebSocket\WebSocketController;

use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use WebSocket\Server\ConsoleIoLogger;
use WebSocket\Server\Server;

/**
 * Class WebSocketController
 * Created by allancarvalho in dezembro 17, 2021
 */
class WebSocketController
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * @var \WebSocket\Server\Server
     */
    protected Server $server;

    /**
     * @var \WebSocket\Server\ConsoleIoLogger
     */
    protected ConsoleIoLogger $logger;

    /**
     * @param \WebSocket\Server\Server $server
     * @param \WebSocket\Server\ConsoleIoLogger $logger
     */
    public function __construct(Server $server, ConsoleIoLogger $logger)
    {
        $this->server = $server;
        $this->logger = $logger;
    }

}