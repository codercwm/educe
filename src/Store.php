<?php

namespace Codercwm\Educe;

use Codercwm\Educe\Concerns\Educe;

class Store{
    /**
     * 从数据库中读取数据
     * @param Educe $educe
     * @return null
     */
    public static function begin(Educe $educe){

        set_time_limit(0);

        //如果任务为非正常状态，就不再往下执行了，已经失败或已删除都会标记为非正常状态
        if(
            empty($educe->cacheService()->get($educe->taskInfo['task_id'].'_is_normal'))
            or
            empty($educe->cacheService()->get($educe->taskInfo['task_id']))
        ){
            return false;
        }

        $educe->query();
        if(isset($educe->skipNum,$educe->takeNum)){
            //分页
            $educe->skip($educe->skipNum);
            $educe->take($educe->takeNum);
        }

        $resource = $educe->resource();

        $resource_count = count($resource);

        //记录数据读取进度，表示已经从数据库里获取了多少条数据
        $educe->cacheService()->incrby($educe->taskInfo['task_id'].'_progress_read',$resource_count);

        //实例化文件写入服务
        $sheet_service_name = $educe->sheetService();
        $sheet_service = new $sheet_service_name($educe);

        //写入文件前操作，比如定义文件类型，打开文件
        $sheet_service->before();

        //要获取的数据字段
        if(method_exists($educe,'fields')){
            $field_value = new FieldValue($educe->fields());
        }

        foreach ($resource as $datum){
            if(isset($field_value)){
                //进行返回值处理和获取
                $datum = $field_value->get($datum);
            }
            //调用文件写入服务类的写入方法，写入数据到文件
            $sheet_service->write($datum);
        }

        //写入文件后操作，比如关闭文件
        $sheet_service->after();

        //记录数据写入进度，表示已经写了多少条数据到文件里
        $educe->cacheService()->incrby($educe->taskInfo['task_id'].'_progress_write',$resource_count);

        $resource = null;

        //检查是否已完成导出然后合并文件
        if(
            (
                //文件读取进度等于count，已读取完毕
                ($educe->cacheService()->get($educe->taskInfo['task_id'].'_progress_read') >= $educe->taskInfo['count'])
                and
                //文件写入进度等于count，已写入完毕
                ($educe->cacheService()->get($educe->taskInfo['task_id'].'_progress_write') >= $educe->taskInfo['count'])
            )
            or
            //如果只有一个文件
            (1==$educe->taskInfo['batch_count'])
        ){
            //避免多进程中重复进行合并操作
            if($educe->cacheService()->setnx($educe->taskInfo['task_id'].'_progress_merge',0)){
                $sheet_service->merge();
                //走到了这一步，就说明已经完成了合并
                $educe->cacheService()->set($educe->taskInfo['task_id'].'_progress_merge',$educe->taskInfo['count']);
                $educe->cacheService()->setnx($educe->taskInfo['task_id'].'_completed_at',date('YmdHis'));
                $educe->onComplete();
            }
        }
    }

}