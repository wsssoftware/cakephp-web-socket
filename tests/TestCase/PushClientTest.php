<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase;

use Cake\Error\FatalErrorException;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use RuntimeException;
use WebSocket\PushClient;

class PushClientTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (Router::getRequest() === null) {
            Router::setRequest(new ServerRequest(['base' => '', 'url' => '', 'webroot' => '/']));
        }
    }

    /**
     * @return void
     */
    public function testSend(): void
    {
        $ipcPayload = PushClient::getInstance()->send('Pages', 'index', ['payloadTest' => [1, 2, 3]]);
        $this->assertEquals('Pages', $ipcPayload->getController());
        $this->assertEquals('index', $ipcPayload->getAction());
    }

    /**
     * @return void
     */
    public function testSendWithRoutes(): void
    {
        $ipcPayloadWithPassAndQuery = PushClient::getInstance()->send(
            'Pages',
            'index',
            ['payloadTest' => [1, 2, 3]],
            [
                'routes' => [
                    'controller' => 'Pages',
                    'action' => 'index', 10,
                    'ignorePass' => false,
                    'ignoreQuery' => false,
                ],
            ]
        );
        $routeMd5 = Hash::get($ipcPayloadWithPassAndQuery->getFilters(), 'routesMd5.0');
        if (empty($routeMd5)) {
            $this->fail();
        }
        $this->assertEquals(
            '182ab85ff454b8faddee1224fde02738.2a30f5f3b7d1a97cb6132480b992d984.d751713988987e9331980363e24189ce',
            $routeMd5
        );

        $ipcPayloadWithoutPassAndQuery = PushClient::getInstance()->send(
            'Pages',
            'index',
            ['payloadTest' => [1, 2, 3]],
            [
                'routes' => [
                    'controller' => 'Pages',
                    'action' => 'index', 10,
                ],
            ]
        );
        $routeMd5 = Hash::get($ipcPayloadWithoutPassAndQuery->getFilters(), 'routesMd5.0');
        if (empty($routeMd5)) {
            $this->fail();
        }
        $this->assertEquals(
            '182ab85ff454b8faddee1224fde02738.none.none',
            $routeMd5
        );
    }

    /**
     * @return void
     */
    public function testSendWithInvalidFilter(): void
    {
        $this->expectException(FatalErrorException::class);
        PushClient::getInstance()->send('Pages', 'index', ['test'], ['routes' => 'invalid']);
    }

    /**
     * @return void
     */
    public function testSendWithLargePayload(): void
    {
        $socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new RuntimeException('Could not open ipc socket.');
        }
        /** @var int $maxPayloadLength */
        $maxPayloadLength = socket_get_option($socket, \SOL_SOCKET, \SO_SNDBUF);

        $payload = ['huge_payload' => str_repeat('a', $maxPayloadLength * 2)];
        $this->expectException(RuntimeException::class);
        PushClient::getInstance()->send('Pages', 'index', $payload);
    }

    /**
     * @return void
     */
    public function testIsOpen(): void
    {
        $open = PushClient::getInstance()->isSocketOpen();
        $this->assertTrue($open);
    }

    /**
     * @return void
     */
    public function testGetInstance(): void
    {
        $instance = PushClient::getInstance();
        $this->assertInstanceOf(PushClient::class, $instance);
    }
}
