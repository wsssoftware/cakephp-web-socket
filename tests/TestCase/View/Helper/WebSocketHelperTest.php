<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use WebSocket\ConfigurationReader;
use WebSocket\Enum\WebSocketProtocol;
use WebSocket\View\Helper\WebSocketHelper;

/**
 * WebSocket\View\Helper\WebSocketHelper Test Case
 */
class WebSocketHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \WebSocket\View\Helper\WebSocketHelper
     */
    protected $WebSocket;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $view = new View();
        Router::reload();

        $this->loadRoutes();
        $this->WebSocket = new WebSocketHelper($view);

        if (Router::getRequest() === null && $view->getRequest() !== null) {
            Router::setRequest($view->getRequest());
        }
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->WebSocket);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testWs(): void
    {
        /** @var string $out */
        $out = $this->WebSocket->connect();
        $this->assertStringContainsString('ws://127.0.0.1:8000', $out);
    }

    /**
     * @return void
     */
    public function testWss(): void
    {
        Configure::write('WebSocket.webSocketProtocol', WebSocketProtocol::WSS);
        ConfigurationReader::reloadInstance();
        /** @var string $out */
        $out = $this->WebSocket->connect();
        $this->assertStringContainsString('wss://127.0.0.1:8000', $out);
    }

    /**
     * @return void
     */
    public function testProxy(): void
    {
        Configure::write('WebSocket.proxy', 'proxyTest');
        Configure::write('WebSocket.forceProxy', true);
        ConfigurationReader::reloadInstance();
        /** @var string $out */
        $out = $this->WebSocket->connect();
        $this->assertStringContainsString('ws://127.0.0.1/proxyTest', $out);
    }
}
