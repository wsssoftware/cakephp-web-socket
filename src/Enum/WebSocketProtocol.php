<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols @codingStandardsIgnoreStart
declare(strict_types=1);

namespace WebSocket\Enum;

enum WebSocketProtocol
{
    case WS;
    case WSS;

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return match ($this) {
            WebSocketProtocol::WS => 'ws',
            WebSocketProtocol::WSS => 'wss',
        };
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            WebSocketProtocol::WS => __('Protocolo WebSocket {0}', __('não criptografado')),
            WebSocketProtocol::WSS => __('Protocolo WebSocket {0}', __('criptografado')),
        };
    }
}
