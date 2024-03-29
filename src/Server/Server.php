<?php
declare(strict_types=1);

namespace WebSocket\Server;

use Cake\Console\ConsoleIo;
use Cake\I18n\Number;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use RuntimeException;
use WebSocket\ConfigurationReader;

/**
 * Class Server
 * Created by allancarvalho in dezembro 16, 2021
 * inspired in bloatless/php-websocket
 *
 * @link https://github.com/bloatless/php-websocket
 */
class Server
{
    public const IPC_SOCKET_PATH = '/tmp/cake-php-wss.sock';

    /**
     * @var \WebSocket\ConfigurationReader
     */
    public ConfigurationReader $configuration;

    /**
     * @var \Socket|false $icpSocket
     */
    private \Socket|false $icpSocket;

    /**
     *  Holds all connected sockets
     *
     * @var resource[]
     */
    protected array $sockets = [];

    /**
     *  Holds the master socket
     *
     * @var resource $masterConnection
     */
    protected $masterConnection;

    /**
     * @var resource $context
     */
    protected $context = null;

    /**
     * @var \WebSocket\Server\Connection[] $connections
     */
    protected array $connections = [];

    /**
     * @var array $ipStorage
     */
    private array $ipStorage = [];

    /**
     * @var \WebSocket\Server\TimerCollection $timers
     */
    private TimerCollection $timers;

    /**
     * @var \WebSocket\Server\ConsoleIoLogger
     */
    private ConsoleIoLogger $logger;

    /**
     * @var \WebSocket\Server\WebSocketApplication
     */
    private WebSocketApplication $webSocketApplication;

    /**
     * @param \Cake\Console\ConsoleIo $io
     * @param \WebSocket\ConfigurationReader $configuration
     */
    #[Pure]
    public function __construct(
        ConsoleIo $io,
        ConfigurationReader $configuration,
    ) {
        $this->configuration = $configuration;
        $this->logger = new ConsoleIoLogger($io);
        $this->webSocketApplication = new WebSocketApplication($this);
    }

    /**
     * @return \WebSocket\Server\ConsoleIoLogger
     */
    public function getLogger(): ConsoleIoLogger
    {
        return $this->logger;
    }

    /**
     * @return \WebSocket\Server\Connection[]
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * @return \WebSocket\Server\WebSocketApplication
     */
    public function getWebSocketApplication(): WebSocketApplication
    {
        return $this->webSocketApplication;
    }

    /**
     * @return \WebSocket\ConfigurationReader
     */
    public function getConfiguration(): ConfigurationReader
    {
        return $this->configuration;
    }

    /**
     * @return void
     */
    protected function configureTimers(): void
    {
        $this->logger->disableMessagePrefix();
        $timers = $this->configuration->getTimers();
        $registryLabel = 'REGISTERING TIMERS';
        $size = 98 - strlen($registryLabel);
        $beforeSpace = (int)ceil($size / 2);
        $afterSpace = (int)floor($size / 2);
        $this->logger->info('╔' . str_repeat('═', 98) . '╗');
        $this->logger->info(
            '║' . str_repeat(' ', $beforeSpace) .
            $registryLabel . str_repeat(' ', $afterSpace) . '║'
        );
        $this->logger->info('╠' . str_repeat('═', 98) . '╣');
        if (!empty($timers)) {
            foreach ($timers as $timer) {
                /** @var \WebSocket\Server\Timer $timer */
                $timer = new $timer($this->logger);
                $interval = $timer->getInterval();
                if (empty($interval)) {
                    throw new InvalidArgumentException(
                        sprintf('Timer "%s" does not have a interval defined.', $timer::class)
                    );
                }

                $this->timers->addTimer($timer);
            }
        } else {
            $resultLabel = 'NO TIMERS TO REGISTRY';
            $size = 98 - strlen($resultLabel);
            $beforeSpace = (int)ceil($size / 2);
            $afterSpace = (int)floor($size / 2);
            $this->logger->info(
                '║' . str_repeat(' ', $beforeSpace) .
                $resultLabel . str_repeat(' ', $afterSpace) . '║'
            );
        }

        $this->logger->info('╚' . str_repeat('═', 98) . '╝');
        $this->logger->lineBreak();

        $this->logger->enableMessagePrefix();
    }

    /**
     * @return void
     */
    protected function afterCreateSummary(): void
    {
        $infos = [
            sprintf('HOST: %s', $this->configuration->getHost()),
            sprintf('PORT: %s', $this->configuration->getPort()),
            sprintf('SOCKET PATH: %s', self::IPC_SOCKET_PATH),
            sprintf('MAX CLIENTS: %s', Number::format($this->configuration->getMaxClients())),
            sprintf('MAX CONNECTION PER IP: %s', Number::format($this->configuration->getMaxConnectionsPerIp())),
            sprintf('CHECK ORIGINS: %s', $this->configuration->isCheckOrigin() ? 'YES' : 'NO'),
        ];

        $this->logger->disableMessagePrefix();
        $this->logger->info('╔' . str_repeat('═', 98) . '╗');
        $this->logger->info(
            '║' . str_repeat(' ', 25) . 'WELCOME TO CAKEPHP WEBSOCKET! SERVER WAS CREATED!' .
            str_repeat(' ', 24) . '║'
        );
        $this->logger->info('╠' . str_repeat('═', 98) . '╣');
        $this->logger->info(
            '║' . str_repeat(' ', 42) . 'CONFIGURATIONS' . str_repeat(' ', 42) . '║'
        );
        $this->logger->info('╠' . str_repeat('═', 98) . '╣');
        foreach ($infos as $info) {
            $size = 98 - strlen($info);
            $beforeSpace = (int)ceil($size / 2);
            $afterSpace = (int)floor($size / 2);
            $this->logger->info(
                '║' . str_repeat(' ', $beforeSpace) . $info . str_repeat(' ', $afterSpace) . '║'
            );
        }
        $this->logger->info('╚' . str_repeat('═', 98) . '╝');
        $this->logger->lineBreak();
        $this->logger->enableMessagePrefix();
    }

    /**
     * Main server loop.
     * Listens for connections, handles connect/disconnect, e.g.
     *
     * @param bool $isTest If this run is test or not
     * @return void
     * @throws \ReflectionException
     */
    public function run(bool $isTest = false): void
    {
        $mustContinue = true;
        ob_implicit_flush();
        if (!$isTest) {
            $this->createSocket($this->configuration->getHost(), $this->configuration->getPort());
            $this->openIPCSocket();
        }
        $this->timers = new TimerCollection();
        $this->afterCreateSummary();

        $this->configureTimers();
        $write = null;
        $except = null;
        while ($mustContinue) {
            $this->timers->runAll($this->getConnections());
            if ($isTest) {
                $this->logger->debug('command with test option finished');
                $mustContinue = false;
                continue;
            }

            $changed_sockets = $this->sockets;
            // @codingStandardsIgnoreStart
            @stream_select($changed_sockets, $write, $except, 0, 5000);
            // @codingStandardsIgnoreEnd
            foreach ($changed_sockets as $socket) {
                if ($socket === $this->masterConnection) {
                    $resource = stream_socket_accept($this->masterConnection);
                    if ($resource === false) {
                        $this->logger->error('Socket error: Unknown');
                    } else {
                        $connection = $this->createConnection($resource);
                        $this->connections[(int)$resource] = $connection;
                        $this->sockets[] = $resource;

                        if (count($this->connections) > $this->configuration->getMaxClients()) {
                            $connection->onDisconnect();
                            continue;
                        }

                        $this->addIpToStorage($connection->getIp());
                        if ($this->checkMaxConnectionsPerIp($connection->getIp()) === false) {
                            $connection->onDisconnect();
                        }
                    }
                } else {
                    $connection = $this->connections[(int)$socket];
                    if (!is_object($connection)) {
                        unset($this->connections[(int)$socket]);
                        continue;
                    }

                    try {
                        $data = Buffer::read($socket);
                    } catch (RuntimeException $e) {
                        $this->logger->error(sprintf(
                            'Error while reading the buffer of message. Error message: %s',
                            $e->getMessage()
                        ));
                        $this->removeConnection($connection);
                        continue;
                    }
                    $bytes = strlen($data);
                    if ($bytes === 0) {
                        $connection->onDisconnect();
                        continue;
                    }

                    $connection->onData($data);
                }
            }

            $this->handleIPC();
        }
    }

    /**
     * Removes a client from client storage.
     *
     * @param \WebSocket\Server\Connection $connection WebSocket connection resource
     * @return void
     */
    public function removeConnection(Connection $connection): void
    {
        $clientIp = $connection->getIp();
        $clientPort = $connection->getPort();
        $resource = $connection->getSocket();
        $this->removeIpFromStorage($clientIp);
        unset($this->connections[(int)$resource]);
        $index = array_search($resource, $this->sockets);
        if ($index === false) {
            return;
        }

        unset($this->sockets[$index], $connection);
        unset($clientIp, $clientPort, $resource);
    }

    /**
     * Checks if the submitted origin (part of websocket handshake) is allowed
     * to connect. Allowed origins can be set at server startup.
     *
     * @param string $domain The origin-domain from websocket handshake.
     * @return bool If domain is allowed to connect method returns true.
     */
    public function checkOrigin(string $domain): bool
    {
        $domain = str_replace('http://', '', $domain);
        $domain = str_replace('https://', '', $domain);
        $domain = str_replace('www.', '', $domain);
        $domain = str_replace('/', '', $domain);

        return isset($this->configuration->getAllowedOrigins()[$domain]);
    }

    /**
     * Creates a connection from a socket resource`
     *
     * @param resource $resource A socket resource
     * @return \WebSocket\Server\Connection
     */
    protected function createConnection($resource): Connection
    {
        return new Connection($this, $resource);
    }

    /**
     * Adds a new ip to ip storage.
     *
     * @param string $ip An ip address.
     * @return void
     */
    private function addIpToStorage(string $ip): void
    {
        if (isset($this->ipStorage[$ip])) {
            $this->ipStorage[$ip]++;
        } else {
            $this->ipStorage[$ip] = 1;
        }
    }

    /**
     * Removes an ip from ip storage.
     *
     * @param string $ip An ip address.
     * @return void
     */
    private function removeIpFromStorage(string $ip): void
    {
        unset($this->ipStorage[$ip]);
    }

    /**
     * Checks if an ip has reached the maximum connection limit.
     *
     * @param string $ip An ip address.
     * @return bool False if ip has reached max. connection limit. True if connection is allowed.
     */
    #[Pure]
    private function checkMaxConnectionsPerIp(string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }
        if (!isset($this->ipStorage[$ip])) {
            return true;
        }

        return !($this->ipStorage[$ip] > $this->configuration->getMaxConnectionsPerIp());
    }

    /**
     * Create a socket on given host/port
     *
     * @param string $host The host/bind address to use
     * @param int $port The actual port to bind on
     * @throws \RuntimeException
     * @return void
     */
    private function createSocket(string $host, int $port): void
    {
        $protocol = 'tcp://';
        $url = $protocol . $host . ':' . $port;
        $this->context = stream_context_create();
        // @codingStandardsIgnoreStart
        $socket = @stream_socket_server(
            $url,
            $errno,
            $err,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $this->context
        );
        // @codingStandardsIgnoreEnd
        if ($socket === false) {
            throw new RuntimeException('Error creating socket: ' . $err);
        }
        $this->masterConnection = $socket;

        $this->sockets[] = $this->masterConnection;
    }

    /**
     * Opens a Unix-Domain-Socket to listen for inputs from other applications.
     *
     * @throws \RuntimeException
     * @return void
     */
    private function openIPCSocket(): void
    {
        if (file_exists(self::IPC_SOCKET_PATH)) {
            unlink(self::IPC_SOCKET_PATH);
        }
        $this->icpSocket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($this->icpSocket === false) {
            throw new RuntimeException('Could not open ipc socket.');
        }
        if (socket_set_nonblock($this->icpSocket) === false) {
            throw new RuntimeException('Could not set nonblock mode for ipc socket.');
        }
        if (socket_bind($this->icpSocket, self::IPC_SOCKET_PATH) === false) {
            throw new RuntimeException('Could not bind to ipc socket.');
        }
    }

    /**
     * Checks IPC socket for input and processes data.
     *
     * @return void
     */
    private function handleIPC(): void
    {
        if ($this->icpSocket === false) {
            return;
        }
        $ipcSocketPath = self::IPC_SOCKET_PATH;
        $buffer = '';
        $bytesReceived = socket_recvfrom($this->icpSocket, $buffer, 65536, 0, $ipcSocketPath);
        if ($bytesReceived === false) {
            return;
        }
        if ($bytesReceived <= 0) {
            return;
        }

        $ipcPayload = IPCPayload::fromJson($buffer);
        $this->webSocketApplication->onIPCData($ipcPayload);
    }
}
