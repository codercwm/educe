<?php

namespace Codercwm\Educe\Concerns;

interface Sheet
{
    public function __construct(Educe $educe);

    /**
     * 写入文件前操作，比如定义文件类型，打开文件
     */
    public function before();

    /**
     * 把数据写入文件
     * @param $data
     */
    public function write($data);

    /**
     * 写入文件后操作，比如关闭文件
     */
    public function after();

    /**
     * 合并文件
     */
    public function merge();
}