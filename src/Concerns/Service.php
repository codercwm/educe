<?php

namespace Codercwm\Educe\Concerns;

use Codercwm\Educe\Services\Redis;
use Codercwm\Educe\Services\Spout;

abstract class Service{

    public function cacheService():Database {
        return Redis::getInstance();
    }

    public function sheetService():string {
        return Spout::class;
    }
}