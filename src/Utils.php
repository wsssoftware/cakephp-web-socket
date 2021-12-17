<?php
declare(strict_types=1);

namespace WebSocket;

use Cake\Routing\Router;
use Cake\Utility\Hash;/**
 * Class Utils
 * Created by allancarvalho in dezembro 16, 2021
 */
class Utils
{

    /**
     * @param array $route
     * @return string
     */
    public static function routeToMd5(array $route): string
    {
        if (empty($route['controller'])) {
            $route['controller'] = Router::getRequest()->getParam('controller');
        }
        if (empty($route['action'])) {
            $route['action'] = Router::getRequest()->getParam('action');
        }
        if (empty($route['prefix'])) {
            $route['prefix'] = false;
        }
        if (empty($route['plugin'])) {
            $route['plugin'] = null;
        }
        if (empty($route['pass'])) {
            $route['pass'] = [];
        }
        ksort($route['pass']);
        if (empty($route['?'])) {
            $route['?'] = [];
        } else {
            ksort($route['?']);
        }

        $md5Items = [];
        $base = [
            Hash::get($route, 'controller', ''),
            Hash::get($route, 'action', ''),
            Hash::get($route, 'plugin', ''),
            Hash::get($route, 'prefix', ''),
        ];
        $md5Items[] = md5(json_encode($base));
        $md5Items[] = md5(json_encode($route['pass']));
        $md5Items[] = md5(json_encode($route['?']));

        return implode('.', $md5Items);
    }
}