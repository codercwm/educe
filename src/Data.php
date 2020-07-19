<?php

namespace Codercwm\Educe;


use Codercwm\Educe\Concerns\Educe;

class Data{


    private function __construct() { }

    private function __clone() { }

    /**
     * 从数据库中读取数据
     * @param Educe $educe
     * @return bool
     */
    public static function read(Educe $educe){

        //如果任务已经失败或已取消，就不再往下执行了
        if(
            $educe->cacheService->get($educe->taskInfo['task_id'].'_is_fail')
            or
            $educe->cacheService->get($educe->taskInfo['task_id'].'_is_cancel')
            or
            empty($educe->cacheService->get($educe->taskInfo['task_id']))
        ){
            return false;
        }

        $resource = $educe->resource();
        $data = [];
        foreach ($resource as $item){
            $data[] = $item;//FieldValue::get($item);
            $educe->cacheService->incrby($educe->taskInfo['task_id'].'_progress_read',1);
        }

        $dir = $educe->taskInfo['path_info']['dirname'];
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }

        (new $educe->sheetService)->write($educe,$data);

        $educe->cacheService->incrby($educe->taskInfo['task_id'].'_progress_write',count($data));
    }

}