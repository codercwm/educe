<?php

namespace Codercwm\Educe\Export;

use Codercwm\Educe\Concerns\Educe;
use Codercwm\Educe\Data;
use Box\Spout\Common\Type as SpoutType;

class Export{

    /**
     * @var Educe
     */
    private $educe;

    /**
     * 文件路径信息
     * @var array|string|string[]
     */
    private $pathInfo = [];

    public function __construct(Educe $educe){
        $this->educe = $educe;

        //获取文件路径
        if(is_null($this->educe->path)){
            $this->educe->path = './EduceExport/'.date('Y_m_d_H_i_s'.rand(1000,9999)).'.'.SpoutType::XLSX;
        }

        $this->pathInfo = pathinfo($this->educe->path);

        //确定文件类型
        if(is_null($this->educe->suffixType)){
            $this->educe->suffixType = $this->pathInfo['extension'];
        }

        //生成任务id
        $this->educe->taskInfo['task_id'] = 'EduceExport'.uniqid();


    }

    public function creation(){
        $this->verify();
        $this->create();

        $batch_size = $this->educe->taskInfo['batch_size'];
        for($b=1;$b<=$this->educe->taskInfo['batch_count'];$b++){
            $this->educe->path = $this->pathInfo['dirname'].'/'.$this->pathInfo['filename'].'_'.$b.'.'.$this->pathInfo['extension'];
            if(method_exists($this->educe,'skip')){
                $this->educe->skip(($b-1)*$batch_size);
                $this->educe->take($batch_size);
            }
            Data::read($this->educe);
        }


        //开始导出
        /*if($this->educe->async){
            //开启异步

        }else{
            return Data::read($this->educe);
        }*/
    }

    private function verify(){
        //判断是否有未完成的
        /*$task_list = Task::all(Id::cid());
        foreach ($task_list as $task){
            if(
                (0==$task['is_fail'])&&
                (0==$task['is_cancel'])&&
                (Info::get('model')==get_class($this->educe))
            ){
                if(
                    $task['percent']!='100%'
                ) {
                    $this->exception('请等待当前任务完成');
                    return;
                }
            }
        }*/
    }


    private function methodValue($method_name){
        if(method_exists($this->educe,$method_name)){
            return $this->educe->{$method_name}();
        }
        return null;
    }

    private function create(){



        $this->educe->query();

        //文件（夹）名
        $this->educe->taskInfo['path_info'] = $this->pathInfo;

        //excel表头
        $this->educe->taskInfo['headers'] = $this->methodValue('headers');

        //字段名
        $this->educe->taskInfo['fields'] = $this->methodValue('fields');

        //每次条数，是从take方法中获取参数值
        $batch_size = null;
        if(method_exists($this->educe,'take')){
            $reflection = new \ReflectionMethod($this->educe,'take');
            $batch_size = $reflection->getParameters()[0]->getDefaultValue();
        }
        $this->educe->taskInfo['batch_size'] = $batch_size;

        //总条数
        $count = $this->methodValue('count');
        $this->educe->taskInfo['count'] = $count;

        //总共分了多少批
        if($count && $batch_size){
            $this->educe->taskInfo['batch_count'] = intval(ceil($count/$batch_size));
        }else{
            $this->educe->taskInfo['batch_count'] = 1;
        }

        //坑：因为数据库会被插入数据，所以读取出来的数量可能会大于一开始时统计的数量
        //计算最后一批有多少条
        if(is_null($count) or is_null($batch_size)){
            $last_batch_size = null;
        }else{
            $last_batch_size = intval($count%$batch_size);
        }
        $this->educe->taskInfo['last_batch_size'] = $last_batch_size?$last_batch_size:$batch_size;

        //任务开始的时间戳
        $this->educe->taskInfo['timestamp'] = time();

        //任务过期的时间戳
        $this->educe->taskInfo['expire_timestamp'] = $this->educe->taskInfo['timestamp']+$this->educe->expire;

        //调用任务保存方法把任务保存
        $this->educe->save();

        $this->educe->cacheService->incrby($this->educe->taskInfo['task_id'].'_progress_read',0);
        $this->educe->cacheService->incrby($this->educe->taskInfo['task_id'].'_progress_write',0);
        $this->educe->cacheService->incrby($this->educe->taskInfo['task_id'].'_progress_merge',0);

        return true;
    }

    public function download(){

    }

}