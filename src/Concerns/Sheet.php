<?php

namespace Codercwm\Educe\Concerns;

interface Sheet
{
    /**
     * 把数据写入文件
     * @param Educe $educe
     * @param $data
     * @return string
     */
    public function write(Educe $educe,$data);
}