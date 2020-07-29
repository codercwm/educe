<?php

namespace Codercwm\Educe\Concerns;

interface Queue
{
    public function queue();

    /**
     * 跳过多少条数据
     * 在此方法中应做的操作是修改查询器并重新赋值给 $this->query 属性
     * @param int $num
     */
    public function skip(int $num);

    /**
     * 获取多少条数据
     * 在此方法中应做的操作是修改查询器并重新赋值给 $this->query 属性
     * @param int $num
     */
    public function take(int $num=500);

    /**
     * 数据计数器，返回总共有多少条数据
     * @return int
     */
    public function count():int;
}