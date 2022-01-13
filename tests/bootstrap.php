<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for WebSocket.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */

use Cake\Console\ConsoleIo;
use Cake\Core\Plugin as CorePlugin;
use Symfony\Component\Process\Process;

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

require_once 'vendor/autoload.php';
require_once 'tests/test_app/config/bootstrap.php';

if (!getenv('DB_URL')) {
    putenv('DB_URL=sqlite:///:memory:');
}

CorePlugin::getCollection()->add(new \WebSocket\Plugin());

$console = new ConsoleIo();

$command = [
    PHP_BINARY,
     'cake.php',
    'web_socket_server',
    ];
$console->info('Trying to execute WebSocket server');

$process = new Process($command, $root . DS . 'tests' . DS . 'test_app' . DS . 'bin' . DS);
$process->start();

$console->info('WebSocket server started on pid: ' . $process->getPid());

sleep(2);
