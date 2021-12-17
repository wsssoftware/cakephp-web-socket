<?php
declare(strict_types=1);

namespace WebSocket\WebSocketController;

use Cake\Console\ConsoleIo;
use Cake\Core\App;
use Cake\Core\Container;
use Cake\Error\FatalErrorException;
use ReflectionClass;
use WebSocket\Server\ConsoleIoLogger;
use WebSocket\Server\Server;

/**
 * Class WebSocketControllerFactory
 *
 * Created by allancarvalho in dezembro 17, 2021
 */
class WebSocketControllerFactory
{

    /**
     * @var \WebSocket\WebSocketController\WebSocketControllerFactory
     */
    protected static WebSocketControllerFactory $instance;

    /**
     * @var \WebSocket\WebSocketController\WebSocketController[]
     */
    protected array $controllers = [];

    /**
     * @return \WebSocket\WebSocketController\WebSocketControllerFactory
     */
    public static function getInstance(): WebSocketControllerFactory
    {
        if (empty(self::$instance)) {
            self::$instance = new WebSocketControllerFactory();
        }

        return self::$instance;
    }

    /**
     * @param \WebSocket\Server\Server $server
     * @param \WebSocket\Server\ConsoleIoLogger $logger
     * @param string|false $plugin
     * @param string $controller
     * @param string $action
     * @param array $payload
     * @return array|null
     * @throws \ReflectionException
     */
    public function invoke(Server $server, ConsoleIoLogger $logger, string|false $plugin, string $controller, string $action, array $payload): null|array
    {
        $className = $this->getControllerClass($plugin, $controller);
        $pluginPath = '';
        if ($plugin) {
            $pluginPath = $plugin . '.';
        }
        $fullControllerName = $pluginPath . $controller;
        if ($className === null) {
            throw new FatalErrorException(sprintf('WebSocketController "%s" was not found.', $fullControllerName));
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract()) {
            throw new FatalErrorException(sprintf('WebSocketController "%s" was not found.', $fullControllerName));
        }
        if (!empty($this->controllers[$className])) {
            $controllerClass = $this->controllers[$className];
        } else {
            $controllerClass =  new $className($server, $logger);
            $this->controllers[$className] = $controllerClass;
        }
        if ($reflection->getParentClass() === false || $reflection->getParentClass()->name !== WebSocketController::class) {
            throw new FatalErrorException(sprintf('WebSocketController "%s" must extends from "%s".', $controllerClass::class, WebSocketController::class));
        }
        if (!$reflection->hasMethod($action)) {
            throw new FatalErrorException(sprintf('WebSocketController "%s::%s(array $payload)" dos not exist.',$controllerClass::class, $action));
        }
        $method = $reflection->getMethod($action);
        if (str_starts_with($action, '_') || !$method->isPublic()) {
            throw new FatalErrorException(sprintf('WebSocketController "%s::%s" must to be public and without starts with "_".',$controllerClass::class, $action));
        }
        $parameters = $method->getParameters();
        if (count($parameters) !== 1 || $parameters[0]->getType()->getName() !== 'array') {
            throw new FatalErrorException(sprintf('WebSocketController "%s::%s" must to have only the "array" "$payload" parameter.',$controllerClass::class, $action));
        }

        $result = $controllerClass->{$action}($payload);
        if (is_array($result)) {
            return $result;
        }

        return null;
    }

    /**
     * Determine the controller class name based on current request and controller param
     *
     * @param string|false $plugin
     * @param string $controller
     * @return string|null
     */
    public function getControllerClass(string|false $plugin, string $controller): ?string
    {
        $pluginPath = '';
        $namespace = 'WebSocket\\WebSocketController';
        if ($plugin) {
            $pluginPath = $plugin . '.';
        }
        $firstChar = substr($controller, 0, 1);

        // Disallow plugin short forms, / and \\ from
        // controller names as they allow direct references to
        // be created.
        if (
            strpos($controller, '\\') !== false ||
            strpos($controller, '/') !== false ||
            strpos($controller, '.') !== false ||
            $firstChar === strtolower($firstChar)
        ) {
            throw new FatalErrorException(sprintf('WebSocketController "%s" not found.', $pluginPath . $controller));
        }

        /** @var class-string<\Cake\Controller\Controller>|null */
        return App::className($pluginPath . $controller, $namespace, 'WebSocketController');
    }
}