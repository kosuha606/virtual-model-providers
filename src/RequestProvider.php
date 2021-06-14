<?php

namespace app\Provider;

use kosuha606\VirtualModel\Example\MemoryModelProvider;
use function getallheaders;

class RequestProvider extends MemoryModelProvider
{
    public const REQUEST = 'request';

    /**
     * @return string
     */
    public function type(): string
    {
        return self::REQUEST;
    }

    /**
     * @param string $requestModelClass
     */
    public function __construct(string $requestModelClass)
    {
        parent::__construct();
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $isAjax = (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] === 'XMLHttpRequest');
        $this->memoryStorage = [
            $requestModelClass => [
                [
                    'headers' => $headers,
                    'pathInfo' => $requestUri,
                    'get' => $_GET ?: [],
                    'post' => $_POST ?: [],
                    'isAjax' => $isAjax,
                    'isPost' => $requestMethod === 'POST',
                ]
            ]
        ];
    }
}
