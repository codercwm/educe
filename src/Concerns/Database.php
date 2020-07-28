<?php

namespace Codercwm\Educe\Concerns;

interface Database
{
    public function set($key,$value);

    public function get($key);

    public function del($key);

    /**
     * 设置不存在的值，设置成功返回true，失败返回false
     * 此方法必须支持并发
     * @param $key
     * @param $value
     * @return bool
     */
    public function setnx($key,$value):bool;

    /**
     * 将 key 中储存的数字加上指定的增量值
     * @param $key
     * @param $value
     */
    public function incrby($key,$value);
}