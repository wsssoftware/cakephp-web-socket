<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for WebSocket.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */

use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Core\Configure;
use Cake\Core\Plugin as CorePlugin;
use Cake\Datasource\ConnectionManager;

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

require_once 'vendor/cakephp/cakephp/src/basics.php';
require_once 'vendor/autoload.php';

define('ROOT', $root . DS . 'tests' . DS . 'test_app' . DS);
define('APP', ROOT . 'src' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('CONFIG', ROOT . DS . 'config' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CORE_PATH', $root . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);

Configure::write('debug', true);
Configure::write('App', [
    'base' => '',
    'namespace' => 'TestApp',
    'encoding' => 'UTF-8',
    'paths' => [
        'plugins' => [ROOT . 'Plugin' . DS],
    ],
]);
Configure::write('Cache', [
    '_cake_core_' => [
        'className' => FileEngine::class,
        'prefix' => 'myapp_cake_core_',
        'path' => CACHE . 'persistent' . DS,
        'serialize' => true,
        'duration' => '+1 years',
        'url' => env('CACHE_CAKECORE_URL', null),
    ],
]);
$cacheConfig = Configure::consume('Cache');
if (is_array($cacheConfig)) {
    Cache::setConfig($cacheConfig);
}
\Cake\Utility\Security::setSalt('DJSANJdsaHj13888!*u7e891728e8u1OSDJAO');

if (!getenv('DB_URL')) {
    putenv('DB_URL=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', ['url' => getenv('DB_URL')]);

CorePlugin::getCollection()->add(new \WebSocket\Plugin());
