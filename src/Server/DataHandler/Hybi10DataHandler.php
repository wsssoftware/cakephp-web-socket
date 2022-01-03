<?php
declare(strict_types=1);

namespace WebSocket\Server\DataHandler;

use RuntimeException;

/**
 * Class Hybi10DataHandler
 * Created by allancarvalho in dezembro 16, 2021
 */
class Hybi10DataHandler extends DataHandler
{
    /**
     * @inheritDoc
     */
    public function encode(string $payload, string $type = 'text', bool $masked = true): string
    {
        $payloadLength = strlen($payload);

        $frameHead = [];
        $frameHead[0] = match ($type) {
            'text' => 129,
            'close' => 136,
            'ping' => 137,
            'pong' => 138,
        };

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = $masked === true ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0 (close connection if frame too big)
            if ($frameHead[2] > 127) {
                $this->connection->close(1004);
                throw new RuntimeException('Invalid payload. Could not encode frame.');
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = $masked === true ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = $masked === true ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = [];
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= $masked === true ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    /**
     * @inheritDoc
     */
    public function decode(string $data): array
    {
        $unmaskedPayload = '';
        $decodedData = [];

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = $secondByteBinary[0] === '1';
        $payloadLength = ord($data[1]) & 127;

        // close connection if unmasked frame is received:
        if ($isMasked === false) {
            $this->connection->close(1002);
        }

        $decodedData['type'] = match ($opcode) {
            1 => 'text',
            2 => 'binary',
            8 => 'close',
            9 => 'ping',
            10 => 'pong',
            default => false,
        };
        if ($decodedData['type'] === false) {
            $this->connection->close(1003);
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferred.
         */
        if (strlen($data) < $dataLength) {
            return [];
        }

        if ($isMasked === true) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }
}
