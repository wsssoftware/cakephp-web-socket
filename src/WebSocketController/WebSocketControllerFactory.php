<?php
declare(strict_types=1);

namespace WebSocket\WebSocketController;

use Cake\Core\App;
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
    protected static WebSocketControllerFactory $instance;

    /**
     * @var \WebSocket\WebSocketController\WebSocketController[]
     */
    protected array $controllers = [];

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new WebSocketControllerFactory();
        }

        return self::$instance;
    }

    /**
     * @param \WebSocket\Server\Server $server WebSocket server
     * @param \WebSocket\Server\ConsoleIoLogger $logger Logger to print results on console
     * @param string|false $plugin Plugin or false
     * @param string $controller controller of request
     * @param string $action action of request
     * @param array $payload Message payload
     * @return false|array|null
     * @throws \ReflectionException
     */
    public function invoke(
        Server $server,
        ConsoleIoLogger $logger,
        string | false $plugin,
        string $controller,
        string $action,
        array $payload
    ): false | null | array {
        $className = $this->getControllerClass($plugin, $controller);
        $pluginPath = '';
        if ($plugin) {
            $pluginPath = $plugin . '.';
        }
        $fullControllerName = $pluginPath . $controller;
        if ($className === null) {
            $logger->error(sprintf('WebSocketController "%s" was not found.', $fullControllerName));

            return false;
        }

        $reflection = new ReflectionClass($className);
        if ($reflection->isAbstract()) {
            $logger->error(sprintf('WebSocketController "%s" was not found.', $fullControllerName));

            return false;
        }
        if (!empty($this->controllers[$className])) {
            $controllerClass = $this->controllers[$className];
        } else {
            $controllerClass = new $className($server, $logger);
            $this->controllers[$className] = $controllerClass;
        }
        if (
            $reflection->getParentClass() === false ||
            $reflection->getParentClass()->name !== WebSocketController::class
        ) {
            $logger->error(sprintf(
                'WebSocketController "%s" must extends from "%s".',
                $controllerClass::class,
                WebSocketController::class
            ));

            return false;
        }
        if (!$reflection->hasMethod($action)) {
            $logger->error(sprintf(
                'WebSocketController "%s::%s(array $payload)" dos not exist.',
                $controllerClass::class,
                $action,
            ));

            return false;
        }
        $method = $reflection->getMethod($action);
        if (str_starts_with($action, '_') || !$method->isPublic()) {
            $logger->error(sprintf(
                'WebSocketController "%s::%s" must to be public and without starts with "_".',
                $controllerClass::class,
                $action
            ));

            return false;
        }
        $parameters = $method->getParameters();
        if (count($parameters) !== 1 || $parameters[0]->getType()->getName() !== 'array') {
            $logger->error(sprintf(
                'WebSocketController "%s::%s" must to have only the "array" "$payload" parameter.',
                $controllerClass::class,
                $action
            ));

            return false;
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
     * @param string|false $plugin The plugin name or false
     * @param string $controller The controller name that will be called.
     * @return string|null
     */
    public function getControllerClass(string | false $plugin, string $controller): ?string
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
            str_contains($controller, '\\') ||
            str_contains($controller, '/') ||
            str_contains($controller, '.') ||
            $firstChar === strtolower($firstChar)
        ) {
            return null;
        }

        /** @var class-string<\Cake\Controller\Controller>|null */
        return App::className($pluginPath . $controller, $namespace, 'WebSocketController');
    }
}
