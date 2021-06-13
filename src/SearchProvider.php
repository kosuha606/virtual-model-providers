<?php

namespace app\Provider;

use kosuha606\VirtualAdmin\Domains\Search\SearchableInterface;
use kosuha606\VirtualAdmin\Domains\Search\SearchIndexInfoDTO;
use kosuha606\VirtualAdmin\Domains\Search\SearchProviderInterface;
use kosuha606\VirtualAdmin\Domains\Search\SearchVm;
use kosuha606\VirtualModel\VirtualModelProvider;

class SearchProvider extends VirtualModelProvider implements SearchProviderInterface
{
    public function type()
    {
        return SearchVm::KEY;
    }

    public function indexInfo($caller): SearchIndexInfoDTO
    {
        // TODO: Implement indexInfo() method.
    }

    public function createIndex($caller, SearchableInterface $model)
    {
        // TODO: Implement createIndex() method.
    }

    public function batchIndex($caller, $models)
    {
        // TODO: Implement batchIndex() method.
    }

    public function removeIndex($caller, SearchableInterface $model)
    {
        // TODO: Implement removeIndex() method.
    }

    public function search($caller, $text)
    {
        // TODO: Implement search() method.
    }

    public function advancedSearch($caller, $config)
    {
        // TODO: Implement advancedSearch() method.
    }

    public function reindexAll($caller)
    {
        // TODO: Implement reindexAll() method.
    }
}