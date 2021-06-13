<?php

namespace kosuha606\VirtualModelProviders;

use kosuha606\VirtualModel\VirtualModelProvider;

class CookieProvider extends VirtualModelProvider
{
    public const COOKIES = 'cookies';

    /**
     * @return string
     */
    public function type(): string
    {
        return self::COOKIES;
    }

    /**
     * @param string $modelClass
     * @param string $key
     * @return string|null
     */
    public function get(string $modelClass, string $key): ?string
    {
        return $_COOKIE[$key] ?? null;
    }

    /**
     * @param string $modelClass
     * @param string $key
     * @param string $value
     * @param int $expires
     */
    public function set(string $modelClass, string $key, string $value, int $expires = 3600): void
    {
        setcookie($key, $value, $expires, '/');
    }

    /**
     * @param string $modelClass
     * @param string $key
     */
    public function unset(string $modelClass, string $key): void
    {
        setcookie($key, 0, time() - 1000);
    }
}
