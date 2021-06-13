<?php

namespace kosuha606\VirtualModelProviders;

use kosuha606\VirtualModel\VirtualModelProvider;

class CookieProvider extends VirtualModelProvider
{
    /**
     * @return string
     */
    public function type(): string
    {
        return 'cookies';
    }

    /**
     * @param string $modelClass
     * @param string $key
     * @return null
     */
    public function get($modelClass, $key)
    {
        return $_COOKIE[$key] ?? null;
    }

    /**
     * @param string $modelClass
     * @param string $key
     * @param string $value
     * @param int $expires
     */
    public function set($modelClass, $key, $value, $expires = 3600)
    {
        setcookie($key, $value, $expires, '/');
    }

    /**
     * @param $modelClass
     * @param $key
     */
    public function unset($modelClass, $key)
    {
        setcookie($key, 0, time() - 1000);
    }
}
