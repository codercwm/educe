<?php

namespace Codercwm\Educe\Export;

use Codercwm\Educe\Concerns\Educe;
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
            $this->educe->path = getcwd().DIRECTORY_SEPARATOR.'EduceExport'.DIRECTORY_SEPARATOR.date('Y_m_d_H_i_s'.rand(1000,9999)).'.'.SpoutType::XLSX;
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

        for($b=1;$b<=$this->educe->taskInfo['batch_count'];$b++){
            $this->educe->currentBatch = $b;
            //有batch_size表示开启了分批查询，否则batch_size会是null
            if($this->educe->taskInfo['batch_size']){
                //分页
                $this->educe->skipNum = ($b-1)*$this->educe->taskInfo['batch_size'];
                if($b==$this->educe->taskInfo['batch_count']){
                    //如果是最后一批就获取最后一批的数量
                    $this->educe->takeNum = $this->educe->taskInfo['last_batch_size'];
                }else{
                    $this->educe->takeNum = $this->educe->taskInfo['batch_size'];
                }
            }
            if(method_exists($this->educe,'queue')){
                //放入队列时把query设成空，队列执行获取数据时会再重新赋值，因为某些情况query是不能被序列化的
                $this->educe->query = null;
                $this->educe->queue();
            }else{
                $this->educe->export();
            }
        }

    }

    private function verify(){
        $all_task = $this->educe->select();
        foreach ($all_task as $task_info){
            if( $task_info['is_normal'] and (100!=$task_info['percent']) ) {
                throw new \Exception('请等待当前任务完成');
            }
        }
    }


    private function methodCall($method_name){
        if(method_exists($this->educe,$method_name)){
            return $this->educe->{$method_name}();
        }
        return null;
    }

    private function create(){

        //首先创建查询器是必须的
        $this->educe->query();

        //文件（夹）名
        $this->educe->taskInfo['path_info'] = $this->pathInfo;

        //文件存放目录
        $files_dir = $this->pathInfo['dirname'].DIRECTORY_SEPARATOR.$this->pathInfo['filename'];

        //创建目录
        if(!is_dir($files_dir)){
            $mkdir = mkdir($files_dir,0777,true);
            if(!$mkdir){
                throw new \Exception('文件夹创建失败');
            }
        }

        $this->educe->taskInfo['files_dir'] = $files_dir;

        //excel表头
        $this->educe->taskInfo['headers'] = $this->methodCall('headers');

        //字段名
        $this->educe->taskInfo['fields'] = $this->methodCall('fields');

        //每次条数，是从take方法中获取参数值
        $batch_size = null;
        if(method_exists($this->educe,'take')){
            $reflection = new \ReflectionMethod($this->educe,'take');
            $batch_size = $reflection->getParameters()[0]->getDefaultValue();
        }
        $this->educe->taskInfo['batch_size'] = $batch_size;

        //调用count方法获取总条数
        $count = $this->methodCall('count');
        $this->educe->taskInfo['count'] = $count;

        //总共分了多少批
        if($count and $batch_size){
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

        //任务创建的时间
        $this->educe->taskInfo['created_at'] = date('YmdHis');

        //调用任务保存方法把任务保存
        $this->educe->save($this->educe->taskInfo);

        $this->educe->cacheService()->set($this->educe->taskInfo['task_id'].'_is_normal',1);

        return true;
    }

    public function download(){

    }

}