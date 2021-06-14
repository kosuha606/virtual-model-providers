<?php

namespace app\Provider;

use kosuha606\VirtualModel\VirtualModelProvider;

class TransactionProvider extends VirtualModelProvider
{
    public const TRANSACTION = 'transaction';

    public function __construct()
    {
        parent::__construct();
        $this->specifyActions([
            'type' => function() {
                return self::TRANSACTION;
            },
            'environment' => function() {
                return self::TRANSACTION;
            },
            'findOne' => function() {
                return [];
            },
            'findMany' => function() {
                return [];
            },
            'begin' => function() {
                return [];
            },
            'commit' => function() {
                return [];
            },
            'rollback' => function() {
                return [];
            },
        ], true);
    }
}
