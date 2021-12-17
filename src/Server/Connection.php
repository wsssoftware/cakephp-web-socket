<?php
declare(strict_types=1);

namespace WebSocket\Server;

use RuntimeException;
use WebSocket\Server\DataHandler\DataHandler;
use WebSocket\Server\DataHandler\Hybi10DataHandler;

/**
 * Class Connection
 * Created by allancarvalho in dezembro 16, 2021
 */
class Connection
{

    /**
     * @var bool
     */
    public bool $waitingForData = false;

    /**
     * @var Server $server
     */
    private Server $server;

    /**
     * @var resource $socket
     */
    private $socket;

    /**
     * @var bool $handshakeDone
     */
    private bool $handshakeDone = false;

    /**
     * @var string $ip
     */
    private string $ip;

    /**
     * @var int $port
     */
    private int $port;

    /**
     * @var string $dataBuffer
     */
    private string $dataBuffer = '';

    /**
     * @var \WebSocket\Server\DataHandler\DataHandler
     */
    protected DataHandler $dataHandler;

    /**
     * @var string $id
     */
    private string $id;
    /**
     * @var string|null
     */
    private ?string $routeMd5 = null;

    /**
     * @var ?int
     */
    private ?int $userId = null;

    /**
     * @var string|null
     */
    private ?string $sessionId = null;

    /**
     * @param Server $server
     * @param resource $socket
     */
    public function __construct(Server $server, $socket)
    {
        $this->server = $server;
        $this->socket = $socket;

        // set some client-information:
        $socketName = stream_socket_get_name($socket, true);

        if (str_contains($socketName, ']:')) {
            $tmp = explode(']:', $socketName);
        } else {
            $tmp = explode(':', $socketName);
        }
        $this->ip = $tmp[0];
        $this->port = (int) $tmp[1];
        $this->id = md5($this->ip . $this->port . spl_object_hash($this));

        $this->dataHandler = new Hybi10DataHandler($this);

        $this->server->getLogger()->wrapConnection($this)->info('Connected');
    }


    /**
     * Decodes json payload received from stream.
     *
     * @param string $data
     * @return array
     *@throws \RuntimeException
     */
    public function decodeData(string $data): array
    {
        $decodedPayload = json_decode($data, true);
        if (empty($decodedPayload)) {
            throw new RuntimeException('Could not decode payload.');
        }

        return $decodedPayload;
    }

    /**
     * Encodes payload to be sent to client.
     *
     * @param array $data
     * @return string
     */
    public function encodeData(array $data): string
    {
        return json_encode($data);
    }

    /**
     * Handles the client-server handshake.
     *
     * @param string $data
     * @throws \RuntimeException
     * @return bool
     */
    private function handshake(string $data): bool
    {
        $this->server->getLogger()->wrapConnection($this)->info('Performing handshake');
        $lines = preg_split("/\r\n/", $data);

        // check for valid http-header:
        if (!preg_match('/\AGET (\S+) HTTP\/1.1\z/', $lines[0], $matches)) {
            $this->server->getLogger()->wrapConnection($this)->error('Invalid request: ' . $lines[0]);
            $this->sendHttpResponse();
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            return false;
        }

        // check for valid application:
        $path = $matches[1];
        //$applicationKey = substr($path, 1);

        // generate headers array:
        $headers = [];
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[ strtolower($matches[1])] = $matches[2];
            }
        }

        // check for supported websocket version:
        if (!isset($headers['sec-websocket-version']) || $headers['sec-websocket-version'] < 6) {
            $this->server->getLogger()->wrapConnection($this)->error('Unsupported websocket version.');
            $this->sendHttpResponse(501);
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            $this->server->removeConnection($this);
            return false;
        }

        // check origin:
        if ($this->server->getConfiguration()->isCheckOrigin() === true) {
            $origin = (isset($headers['sec-websocket-origin'])) ? $headers['sec-websocket-origin'] : '';
            $origin = (isset($headers['origin'])) ? $headers['origin'] : $origin;
            if (empty($origin)) {
                $this->server->getLogger()->wrapConnection($this)->error('No origin provided.');
                $this->sendHttpResponse(401);
                stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
                $this->server->removeConnection($this);
                return false;
            }

            if ($this->server->checkOrigin($origin) === false) {
                $this->server->getLogger()->wrapConnection($this)->error('Invalid origin provided.');
                $this->sendHttpResponse(401);
                stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
                $this->server->removeConnection($this);
                return false;
            }
        }

        // do handshake: (hybi-10)
        $secKey = $headers['sec-websocket-key'];
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: " . $secAccept . "\r\n";
        if (isset($headers['sec-websocket-protocol']) && !empty($headers['sec-websocket-protocol'])) {
            $response .= "Sec-WebSocket-Protocol: " . substr($path, 1) . "\r\n";
        }
        $response .= "\r\n";
        try {
            Buffer::write($this->socket, $response);
        } catch (RuntimeException $e) {
            $this->server->getLogger()->error(sprintf('Error while writing the buffer of message. Error message: %s', $e->getMessage()));
            return false;
        }
        $this->server->getLogger()->wrapConnection($this)->info('Handshake sent');
        $this->server->getWebSocketApplication()->onConnect($this);

        return true;
    }

    /**
     * Sends a http response to client.
     *
     * @param int $httpStatusCode
     * @throws \RuntimeException
     * @return void
     */
    public function sendHttpResponse(int $httpStatusCode = 400): void
    {
        $httpHeader = 'HTTP/1.1 ';
        $httpHeader .= match ($httpStatusCode) {
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            501 => '501 Not Implemented',
        };
        $httpHeader .= "\r\n";
        try {
            Buffer::write($this->socket, $httpHeader);
        } catch (RuntimeException $e) {
            $this->server->getLogger()->error(sprintf('Error while writing the buffer of message. Error message: %s', $e->getMessage()));
            // TODO Handle write to socket error
        }
    }

    /**
     * Triggered whenever the server receives new data from a client.
     *
     * @param string $data
     * @return void
     */
    public function onData(string $data): void
    {
        if ($this->handshakeDone) {
            $this->handle($data);
            return;
        }
        $this->handshakeDone = $this->handshake($data);
    }

    /**
     * Decodes incoming data and executes the requested action.
     *
     * @param string $data
     */
    private function handle(string $data): void
    {
        if ($this->waitingForData === true) {
            $data = $this->dataBuffer . $data;
            $this->dataBuffer = '';
            $this->waitingForData = false;
        }

        $decodedData = $this->dataHandler->decode($data);

        if (empty($decodedData)) {
            $this->waitingForData = true;
            $this->dataBuffer .= $data;
            return;
        } else {
            $this->dataBuffer = '';
            $this->waitingForData = false;
        }

        if (!isset($decodedData['type'])) {
            $this->sendHttpResponse(401);
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            $this->server->removeConnection($this);
            return;
        }

        switch ($decodedData['type']) {
            case 'text':
                $this->server->getWebSocketApplication()->onData($decodedData['payload'], $this);
                break;
            case 'binary':
                $this->close(1003);
                break;
            case 'ping':
                $this->send($decodedData['payload'], 'pong');
                $this->server->getLogger()->wrapConnection($this)->info('Ping? Pong!');
                break;
            case 'pong':
                // server currently not sending pings, so no pong should be received.
                break;
            case 'close':
                $this->close();
                $this->server->getLogger()->wrapConnection($this)->info('Disconnected');
                break;
        }
    }

    /**
     * Sends data to a client.
     *
     * @param string $payload
     * @param string $type
     * @param bool $masked
     * @return bool
     */
    public function send(string $payload, string $type = 'text', bool $masked = false): bool
    {

        try {
            $encodedData = $this->dataHandler->encode($payload, $type, $masked);
            Buffer::write($this->socket, $encodedData);
        } catch (RuntimeException $e) {
            $this->server->getLogger()->error(sprintf('Error while writing the buffer of message. Error message: %s', $e->getMessage()));
            $this->server->removeConnection($this);
            return false;
        }

        return true;
    }

    /**
     * Closes connection to a client.
     *
     * @param int $statusCode
     * @return void
     */
    public function close(int $statusCode = 1000): void
    {
        $payload = str_split(sprintf('%016b', $statusCode), 8);
        $payload[0] = chr(bindec($payload[0]));
        $payload[1] = chr(bindec($payload[1]));
        $payload = implode('', $payload);

        $payload .= match ($statusCode) {
            1000 =>  'normal closure',
            1001 => 'going away',
            1002 => 'protocol error',
            1003 => 'unknown data (opcode)',
            1004 => 'frame too large',
            1007 => 'utf8 expected',
            1008 => 'message violates server policy',
        };

        if ($this->send($payload, 'close') === false) {
            return;
        }
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
        $this->server->removeConnection($this);
    }


    /**
     * Triggered when a client closes the connection to server.
     *
     * @return void
     */
    public function onDisconnect(): void
    {
        $this->server->getLogger()->wrapConnection($this)->info('Disconnected');
        $this->close();
    }

    /**
     * Returns IP of the connected client.
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Returns the port the connection is handled on.
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    public function sendPayload(string $controller, string $action, array $payload): void
    {
        $data = [
            'controller' => $controller,
            'action' => $action,
            'payload' => $payload,
        ];
        $this->send($this->encodeData($data));
    }

    /**
     * Returns the unique connection id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the socket/resource of the connection.
     *
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @return array|null
     */
    public function getRouteMd5(): ?string
    {
        return $this->routeMd5;
    }

    /**
     * @param string|null $routeMd5
     */
    public function setRouteMd5(?string $routeMd5): void
    {
        $this->routeMd5 = $routeMd5;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * @param string|null $sessionId
     */
    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }
}