<?php
declare(strict_types=1);

namespace WebSocket;

/**
 * Class Utils
 *
 * Created by allancarvalho in dezembro 16, 2021
 */
class Utils
{

    /**
     * @param array $url
     * @param bool $ignorePass
     * @param bool $ignoreQuery
     * @return string
     */
    public static function urlToMd5(array $url, bool $ignorePass = true, bool $ignoreQuery = true): string
    {
        if (empty($url['prefix'])) {
            $url['prefix'] = false;
        }
        if (empty($url['pass'])) {
            $url['pass'] = [];
        }
        if (empty($url['?'])) {
            $url['?'] = [];
        } else {
            ksort($url['?']);
            $queries = [];
            foreach ($url['?'] as $key => $value) {
                $queries[] = "$key=$value";
            }
            $url['?'] = implode('###', $queries);
        }
        if ($ignorePass) {
            $url['pass'] = 'none';
        } else {
            $url['pass'] = implode('###', $url['pass']);
        }
        if ($ignoreQuery) {
            $url['?'] = 'none';
        }
        $md5 = sprintf(
            'controller::%s|action::%s|pass::%s|prefix::%s|plugin::%s|query::%s',
            $url['controller'],
            $url['action'],
            $url['pass'],
            $url['prefix'] !== false ? $url['prefix'] : 'none',
            $url['plugin'] !== null ? $url['plugin'] : 'none',
            $url['?'],
        );
        return md5($md5);
    }
}