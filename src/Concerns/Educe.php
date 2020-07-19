<?php

namespace Codercwm\Educe\Concerns;

use Codercwm\Educe\Export\Export;
use Codercwm\Educe\Services\Redis;
use Codercwm\Educe\Services\Spout;
use Codercwm\Educe\Tool;

abstract class Educe extends ServiceProvider{

    /**
     * 创建导出任务
     * @return static
     */
    public static function create(){
        $educe = new static(...func_get_args());
        $educe->registerCache(Redis::getInstance());
        $educe->registerSheet(new Spout);
        return $educe;
    }

    /**
     * 获取任务列表
     */
    public function select(){
        $list = [];
        $key = md5('EduceExport_'.get_called_class());
        $all_task_id = Tool::deJson($this->cacheService->get($key));
        foreach ($all_task_id as $task_id){
            $list[] = Tool::deJson($this->cacheService->get($task_id));
        }
        array_multisort(array_column($list,'timestamp'),SORT_DESC,$list);
        return $list;
    }

    public $config;

    /**
     * 是否异步导出
     * @var bool
     */
    public $async = true;

    /**
     * 文件路径
     * @var null
     */
    public $path = null;

    /**
     * 设置文件类型，如果不设置则获取文件后缀
     * @var null
     */
    public $suffixType = null;

    /**
     * 设置有效期
     * @var int
     */
    public $expire = 86400;

    /**
     * 存放任务信息
     * @var array
     */
    public $taskInfo = [];

    /**
     * 存放查询器
     */
    public $query;

    /**
     * 开始导出
     * @return string
     */
    public function export(){
        $export = new Export($this);
        $export->creation();
        return $this->async?'正在生成数据':'数据生成完毕';
    }

    /**
     * 保存到任务列表
     */
    public function save(){
        $key = md5('EduceExport_'.get_called_class());
        $all_task_id = Tool::deJson($this->cacheService->get($key));
        $all_task_id[] = $this->taskInfo['task_id'];
        $all_task_id = Tool::enJson(array_unique($all_task_id));
        $this->cacheService->set($key,$all_task_id);
        $this->cacheService->set($this->taskInfo['task_id'],Tool::enJson($this->taskInfo));
    }

    /**
     * 数据查询器
     * @return mixed
     */
    abstract public function query();

    /**
     * 执行获取数据
     * @return array
     */
    abstract public function resource():array;
}