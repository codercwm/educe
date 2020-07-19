<?php

namespace Codercwm\Educe\Services;

use Codercwm\Educe\Concerns\Cache;

class Redis implements Cache {

    private $redis;

    private static $instance = null;

    private function __construct() {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }

    public static function getInstance() {
        if(is_null(self::$instance)){
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function setnx($key,$value){
        return $this->redis->setnx($key,$value);
    }

    public function set($key,$value){
        return $this->redis->set($key,$value);
    }

    public function get($key){
        return $this->redis->get($key);
    }

    public function incrby($key,$value){
        return $this->redis->incrby($key,$value);
    }

}