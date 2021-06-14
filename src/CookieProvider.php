<?php

namespace kosuha606\VirtualModelProviders;

use kosuha606\VirtualModel\VirtualModelProvider;

class CookieProvider extends VirtualModelProvider
{
    public const COOKIES = 'cookies';

    public function __construct()
    {
        parent::__construct();
        $this->specifyActions([
            'type' => function () {
                return self::COOKIES;
            },

            'get' => function (string $modelClass, string $key) {
                return $_COOKIE[$key] ?? null;
            },

            'set' => function (string $modelClass, string $key, string $value, int $expires = 3600) {
                setcookie($key, $value, $expires, '/');
            },

            'unset' => function (string $modelClass, string $key) {
                setcookie($key, 0, time() - 1000);
            },
        ], true);
    }
}
