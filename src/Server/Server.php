<?php
declare(strict_types=1);

namespace WebSocket\Server;

use Cake\Console\ConsoleIo;
use Cake\I18n\Number;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
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
     * @var resource $icpSocket
     */
    private $icpSocket;

    /**
     *  If set, owner of the ipc socket will be changed to this value.
     *
     * @var string $ipcOwner
     */
    private string $ipcOwner = '';

    /**
     *  If set, group of the ipc socket will be changed to this value.
     *
     * @var string $ipcGroup
     */
    private string $ipcGroup = '';

    /**
     *  If set, chmod of the ipc socket will be changed to this value.
     *
     * @var int $ipcMode
     */
    private int $ipcMode = 0;

    /**
     *  Holds all connected sockets
     *
     * @var array
     */
    protected array $sockets = [];

    /**
     *  Holds the master socket
     *
     * @var resource $master
     */
    protected $master;

    /**
     * @var resource $context
     */
    protected $context = null;

    /**
     * @var array $clients
     */
    protected array $clients = [];

    /**
     * @var array $ipStorage
     */
    private array $ipStorage = [];

    /**
     * @var TimerCollection $timers
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
        $timers = $this->configuration->getTimers();
        $registryLabel = 'REGISTERING TIMERS';
        $size = 98 - strlen($registryLabel);
        $beforeSpace = (int)ceil($size / 2);
        $afterSpace = (int)floor($size / 2);
        $this->logger->info('╔══════════════════════════════════════════════════════════════════════════════════════════════════╗');
        $this->logger->info('║' . str_repeat(' ', $beforeSpace) . $registryLabel . str_repeat(' ', $afterSpace) . '║');
        $this->logger->info('╠══════════════════════════════════════════════════════════════════════════════════════════════════╣');
        if (!empty($timers)) {

            foreach ($timers as $timer) {
                /** @var \WebSocket\Server\Timer $timer */
                $timer = new $timer();
                $interval = $timer->getInterval();
                if (empty($interval)) {
                    throw new InvalidArgumentException(sprintf('Timer "%s" does not have a interval defined.', $timer::class));
                }
                $resultLabel = sprintf('REGISTERING TIMER "%s" with interval %s', $timer::class, Number::format($interval));
                $size = 98 - strlen($resultLabel);
                $beforeSpace = (int)ceil($size / 2);
                $afterSpace = (int)floor($size / 2);
                $this->logger->info('║' . str_repeat(' ', $beforeSpace) . $resultLabel . str_repeat(' ', $afterSpace) . '║');

                $this->timers->addTimer($timer);
            }
        } else {
            $resultLabel = 'NO TIMERS TO REGISTRY';
            $size = 98 - strlen($resultLabel);
            $beforeSpace = (int)ceil($size / 2);
            $afterSpace = (int)floor($size / 2);
            $this->logger->info('║' . str_repeat(' ', $beforeSpace) . $resultLabel . str_repeat(' ', $afterSpace) . '║');
        }


        $this->logger->info('╚══════════════════════════════════════════════════════════════════════════════════════════════════╝');
        $this->logger->info('');


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

        $this->logger->info('╔══════════════════════════════════════════════════════════════════════════════════════════════════╗');
        $this->logger->info('║                         WELCOME TO CAKEPHP WEBSOCKET! SERVER WAS CREATED!                        ║');
        $this->logger->info('╠══════════════════════════════════════════════════════════════════════════════════════════════════╣');
        $this->logger->info('║                                          CONFIGURATIONS                                          ║');
        $this->logger->info('╠══════════════════════════════════════════════════════════════════════════════════════════════════╣');
        foreach ($infos as $info) {
            $size = 98 - strlen($info);
            $beforeSpace = (int)ceil($size / 2);
            $afterSpace = (int)floor($size / 2);
            $this->logger->info('║' . str_repeat(' ', $beforeSpace) . $info . str_repeat(' ', $afterSpace) . '║');
        }
        $this->logger->info('╚══════════════════════════════════════════════════════════════════════════════════════════════════╝');
        $this->logger->info('');
    }

    /**
     * Main server loop.
     * Listens for connections, handles connect/disconnect, e.g.
     *
     * @return void
     */
    public function run(): void
    {
        ob_implicit_flush();
        $this->createSocket($this->configuration->getHost(), $this->configuration->getPort());
        $this->openIPCSocket();
        $this->timers = new TimerCollection();
        $this->afterCreateSummary();

        $this->configureTimers();
        while (true) {
            $this->timers->runAll($this->webSocketApplication);

            $changed_sockets = $this->sockets;
            @stream_select($changed_sockets, $write, $except, 0, 5000);
            foreach ($changed_sockets as $socket) {
                if ($socket == $this->master) {
                    if (($resource = stream_socket_accept($this->master)) === false) {
                        $this->logger->error('Socket error: ' . socket_strerror(socket_last_error($resource)));
                    } else {
                        $connection = $this->createConnection($resource);
                        $this->clients[(int)$resource] = $connection;
                        $this->sockets[] = $resource;

                        if (count($this->clients) > $this->configuration->getMaxClients()) {
                            $connection->onDisconnect();
                            continue;
                        }

                        $this->addIpToStorage($connection->getIp());
                        if ($this->checkMaxConnectionsPerIp($connection->getIp()) === false) {
                            $connection->onDisconnect();
                        }
                    }
                } else {
                    /** @var Connection $connection */
                    $connection = $this->clients[(int)$socket];
                    if (!is_object($connection)) {
                        unset($this->clients[(int)$socket]);
                        continue;
                    }

                    try {
                        $data = $this->readBuffer($socket);
                    } catch (\RuntimeException $e) {
                        $this->removeClientOnError($connection);
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
     * @param Connection $client
     * @return void
     */
    public function removeClientOnClose(Connection $client): void
    {
        $clientIp = $client->getIp();
        $clientPort = $client->getPort();
        $resource = $client->getSocket();

        $this->removeIpFromStorage($clientIp);
        unset($this->clients[(int) $resource]);
        $index = array_search($resource, $this->sockets);
        unset($this->sockets[$index], $client);

        unset($clientIp, $clientPort, $resource);
    }

    /**
     * Removes a client and all references in case of timeout/error.
     *
     * @param Connection $connection The connection object to remove.
     * @return void
     */
    public function removeClientOnError(Connection $connection): void
    {
        $this->removeClientOnClose($connection);
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
     * @return Connection
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
     * @return bool True if ip could be removed.
     */
    private function removeIpFromStorage(string $ip): bool
    {
        if (!isset($this->ipStorage[$ip])) {
            return false;
        }
        if ($this->ipStorage[$ip] === 1) {
            unset($this->ipStorage[$ip]);
            return true;
        }
        $this->ipStorage[$ip]--;

        return true;
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
        $this->master = stream_socket_server(
            $url,
            $errno,
            $err,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $this->context
        );
        if ($this->master === false) {
            throw new \RuntimeException('Error creating socket: ' . $err);
        }

        $this->sockets[] = $this->master;
    }

    /**
     * Reads from stream.
     *
     * @param $resource
     * @throws \RuntimeException
     * @return string
     */
    protected function readBuffer($resource): string
    {
        $buffer = '';
        $buffSize = 8192;
        $metadata['unread_bytes'] = 0;
        do {
            if (feof($resource)) {
                throw new \RuntimeException('Could not read from stream.');
            }
            $result = fread($resource, $buffSize);
            if ($result === false || feof($resource)) {
                throw new \RuntimeException('Could not read from stream.');
            }
            $buffer .= $result;
            $metadata = stream_get_meta_data($resource);
            $buffSize = ($metadata['unread_bytes'] > $buffSize) ? $buffSize : $metadata['unread_bytes'];
        } while ($metadata['unread_bytes'] > 0);

        return $buffer;
    }

    /**
     * Write to stream.
     *
     * @param $resource
     * @param string $string
     * @return int
     */
    public function writeBuffer($resource, string $string): int
    {
        $stringLength = strlen($string);
        if ($stringLength === 0) {
            return 0;
        }

        for ($written = 0; $written < $stringLength; $written += $fwrite) {
            $fwrite = @fwrite($resource, substr($string, $written));
            if ($fwrite === false) {
                throw new \RuntimeException('Could not write to stream.');
            }
            if ($fwrite === 0) {
                throw new \RuntimeException('Could not write to stream.');
            }
        }

        return $written;
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
            throw new \RuntimeException('Could not open ipc socket.');
        }
        if (socket_set_nonblock($this->icpSocket) === false) {
            throw new \RuntimeException('Could not set nonblock mode for ipc socket.');
        }
        if (socket_bind($this->icpSocket, self::IPC_SOCKET_PATH) === false) {
            throw new \RuntimeException('Could not bind to ipc socket.');
        }
        if ($this->ipcOwner !== '') {
            chown(self::IPC_SOCKET_PATH, $this->ipcOwner);
        }
        if ($this->ipcGroup !== '') {
            chgrp(self::IPC_SOCKET_PATH, $this->ipcGroup);
        }
        if ($this->ipcMode !== 0) {
            chmod(self::IPC_SOCKET_PATH, $this->ipcMode);
        }
    }

    /**
     * Checks IPC socket for input and processes data.
     *
     * @return void
     */
    private function handleIPC(): void
    {
        $ipcSocketPath = self::IPC_SOCKET_PATH;
        $buffer = '';
        $bytesReceived = socket_recvfrom($this->icpSocket, $buffer, 65536, 0, $ipcSocketPath);
        if ($bytesReceived === false) {
            return;
        }
        if ($bytesReceived <= 0) {
            return;
        }

        $payload = IPCPayload::fromJson($buffer);
        $this->webSocketApplication->onIPCData($payload->data);
    }

    /**
     * Sets the icpOwner value.
     *
     * @param string $owner
     * @return void
     */
    public function setIPCOwner(string $owner): void
    {
        $this->ipcOwner = $owner;
    }

    /**
     * Sets the ipcGroup value.
     *
     * @param string $group
     * @return void
     */
    public function setIPCGroup(string $group): void
    {
        $this->ipcGroup = $group;
    }

    /**
     * Sets the ipcMode value.
     *
     * @param int $mode
     * @return void
     */
    public function setIPCMode(int $mode): void
    {
        $this->ipcMode = $mode;
    }
}