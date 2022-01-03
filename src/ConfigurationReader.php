<?php
declare(strict_types=1);

namespace WebSocket;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use RuntimeException;
use WebSocket\Enum\WebSocketProtocol;
use WebSocket\Server\Timer;

/**
 * Class ConfigurationReader
 * Created by allancarvalho in dezembro 16, 2021
 */
class ConfigurationReader
{
    /**
     * @var \WebSocket\ConfigurationReader
     */
    protected static ConfigurationReader $instance;

    /**
     * @var \WebSocket\Enum\WebSocketProtocol
     */
    protected WebSocketProtocol $webSocketProtocol;

    /**
     * @var string|false
     */
    protected string|false $proxy;

    /**
     * When proxy is setted:
     * If true will use the proxy every time
     * If false will use only when protocol is WSS
     *
     * @var bool
     */
    protected bool $forceProxy;

    /**
     * @var string
     */
    protected string $host;

    /**
     * @var string
     */
    protected string $httpHost;

    /**
     * @var int
     */
    protected int $port;

    /**
     * @var int
     */
    protected int $maxClients;

    /**
     * @var int
     */
    protected int $maxConnectionsPerIp;

    /**
     * @var bool
     */
    protected bool $checkOrigin;

    /**
     * @var array
     */
    protected array $allowedOrigins;

    /**
     * @var \WebSocket\Server\Timer[]
     */
    protected array $timers;

    /**
     * Construct method
     */
    public function __construct()
    {
        $defaultHttpHost = Hash::get($_SERVER, 'HTTP_HOST', '127.0.0.1');
        $defaultWebSocketProtocol = Hash::get($_SERVER, 'REQUEST_SCHEME', '') === 'https' ?
            WebSocketProtocol::WSS : WebSocketProtocol::WS;

        $configuration = Configure::read('WebSocket', []);
        $this->webSocketProtocol = Hash::get($configuration, 'webSocketProtocol', $defaultWebSocketProtocol);
        $this->proxy = Hash::get($configuration, 'proxy', false);
        $this->forceProxy = Hash::get($configuration, 'forceProxy', false);
        $this->host = Hash::get($configuration, 'host', '127.0.0.1');
        $this->httpHost = Hash::get($configuration, 'httpHost', $defaultHttpHost);
        $this->port = Hash::get($configuration, 'port', 8000);
        $this->maxClients = Hash::get($configuration, 'maxClients', 30);
        $this->maxConnectionsPerIp = Hash::get($configuration, 'maxConnectionsPerIp', 5);
        $this->checkOrigin = Hash::get($configuration, 'checkOrigin', false);

        $allowedOrigins = Hash::get($configuration, 'allowedOrigins', []);
        $this->allowedOrigins = [];
        foreach ($allowedOrigins as $domain) {
            $domain = str_replace(['https://', 'http://', 'www.'], '', $domain);
            $domain = str_contains($domain, '/') ? substr($domain, 0, strpos($domain, '/')) : $domain;
            if (empty($domain)) {
                continue;
            }
            $this->allowedOrigins[$domain] = true;
        }

        $timers = Hash::get($configuration, 'timers', []);
        foreach ($timers as $timer) {
            if (!class_exists($timer)) {
                throw new RuntimeException(sprintf(
                    'All timers must to be a FQN of a class that extends from "%s"!',
                    Timer::class
                ));
            }
            if (!is_subclass_of($timer, Timer::class)) {
                throw new RuntimeException(sprintf('All timers must to be a extension from "%s"!', Timer::class));
            }
        }
        $this->timers = $timers;
    }

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new ConfigurationReader();
        }

        return self::$instance;
    }

    /**
     * @return \WebSocket\Enum\WebSocketProtocol
     */
    public function getWebSocketProtocol(): WebSocketProtocol
    {
        return $this->webSocketProtocol;
    }

    /**
     * @return false|string
     */
    public function getProxy(): bool|string
    {
        return $this->proxy;
    }

    /**
     * @return bool
     */
    public function isForceProxy(): mixed
    {
        return $this->forceProxy;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return int
     */
    public function getMaxClients(): int
    {
        return $this->maxClients;
    }

    /**
     * @return int
     */
    public function getMaxConnectionsPerIp(): int
    {
        return $this->maxConnectionsPerIp;
    }

    /**
     * @return bool
     */
    public function isCheckOrigin(): bool
    {
        return $this->checkOrigin;
    }

    /**
     * @return array
     */
    public function getAllowedOrigins(): array
    {
        return $this->allowedOrigins;
    }

    /**
     * @return \WebSocket\Server\Timer[]
     */
    public function getTimers(): mixed
    {
        return $this->timers;
    }
}
