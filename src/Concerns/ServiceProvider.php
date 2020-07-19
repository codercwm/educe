<?php

namespace Codercwm\Educe\Concerns;

abstract class ServiceProvider{

    public $cacheService,$sheetService;

    public function registerCache(Cache $cache){
        $this->cacheService = $cache;
    }

    public function registerSheet(Sheet $sheet){
        $this->sheetService = $sheet;
    }
}