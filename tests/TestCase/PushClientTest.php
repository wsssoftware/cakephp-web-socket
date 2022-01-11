<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase;

use Cake\TestSuite\TestCase;
use WebSocket\PushClient;

class PushClientTest extends TestCase
{
    /**
     * @return void
     */
    public function testSend(): void
    {
        $ipcPayload = PushClient::getInstance()->send('Pages', 'index', ['dsa']);
        $this->assertEquals('Pages', $ipcPayload->getController());
        $this->assertEquals('index', $ipcPayload->getAction());
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
