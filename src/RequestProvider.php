<?php

namespace app\Provider;

use kosuha606\VirtualAdmin\Model\Request;
use kosuha606\VirtualModel\Example\MemoryModelProvider;
use function getallheaders;

class RequestProvider extends MemoryModelProvider
{
    public function type(): string
    {
        return Request::TYPE;
    }

    public function __construct()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }

        $isAjax = (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] === 'XMLHttpRequest');

        $this->memoryStorage = [
            Request::class => [
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
