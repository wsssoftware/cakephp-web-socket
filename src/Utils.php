<?php
declare(strict_types=1);

namespace WebSocket;

use Cake\Error\FatalErrorException;
use Cake\Routing\Router;
use Cake\Utility\Hash;

/**
 * Class Utils
 * Created by allancarvalho in dezembro 16, 2021
 */
class Utils
{
    /**
     * @param array $route Route data
     * @param bool $ignorePass Must ignore pass data or not
     * @param bool $ignoreQuery Must ignore query data or not
     * @return string
     */
    public static function routeToMd5(array $route, bool $ignorePass = false, bool $ignoreQuery = false): string
    {
        $request = Router::getRequest();
        if ($request === null && PHP_SAPI !== 'cli') {
            throw new FatalErrorException('Request cannot be null');
        }
        if (empty($route['controller'])) {
            $route['controller'] = !empty($request) ? $request->getParam('controller') : 'empty';
        }
        if (empty($route['action'])) {
            $route['action'] = !empty($request) ? $request->getParam('action') : 'empty';
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
        $md5Items[] = md5(strval(json_encode($base)));
        $md5Items[] = $ignorePass ? 'none' : md5(strval(json_encode($route['pass'])));
        $md5Items[] = $ignoreQuery ? 'none' : md5(strval(json_encode($route['?'])));

        return implode('.', $md5Items);
    }
}
