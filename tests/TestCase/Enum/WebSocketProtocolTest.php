<?php
declare(strict_types=1);

namespace WebSocket\Test\TestCase\Enum;

use Cake\TestSuite\TestCase;
use WebSocket\Enum\WebSocketProtocol;

class WebSocketProtocolTest extends TestCase
{
    public function testWs(): void
    {
        $ws = WebSocketProtocol::WS;
        $this->assertStringContainsString($ws->getLabel(), 'WebSocket protocol non encrypted');
        $this->assertStringContainsString($ws->getProtocol(), 'ws');
    }

    public function testWss(): void
    {
        $wss = WebSocketProtocol::WSS;
        $this->assertStringContainsString($wss->getLabel(), 'WebSocket protocol encrypted');
        $this->assertStringContainsString($wss->getProtocol(), 'wss');
    }
}
