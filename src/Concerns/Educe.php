<?php

namespace Codercwm\Educe\Concerns;

use Codercwm\Educe\Export\Export;
use Codercwm\Educe\Log;
use Codercwm\Educe\Store;
use Codercwm\Educe\Tool;

abstract class Educe extends Service{

    /**
     * 创建导出任务
     * @return static
     */
    public static function create(){
        return new static(...func_get_args());
    }

    /**
     * 获取任务列表
     */
    public function select(){
        $list = [];
        $key = md5('EduceExport_'.get_called_class());
        $all_task_id = Tool::deJson($this->cacheService()->get($key));
        if(!empty($all_task_id)){
            foreach ($all_task_id as $task_id){
                $item = Tool::deJson($this->cacheService()->get($task_id));
                if($item){
                    $item['progress_read'] = $this->cacheService()->get($item['task_id'].'_progress_read');
                    $item['progress_write'] = $this->cacheService()->get($item['task_id'].'_progress_write');
                    $item['progress_merge'] = $this->cacheService()->get($item['task_id'].'_progress_merge');
                    $item['is_normal'] = $this->cacheService()->get($item['task_id'].'_is_normal');
                    $item['completed_at'] = $this->cacheService()->get($item['task_id'].'_completed_at');
                    $item['path'] = $item['path_info']['dirname'].DIRECTORY_SEPARATOR.$item['path_info']['basename'];
                    //计算导出进度
                    if(false==$item['completed_at']){
                        $percent = bcmul(($item['progress_read']+$item['progress_write']+$item['progress_merge']) / ($item['count']+$item['count']+$item['count']),100,0);
                        if($percent>99) $percent = 99;
                    }else{
                        $percent = 100;
                    }
                    $item['percent'] = intval($percent);
                    if($item['is_normal']){
                        $item['show_title'] = $item['path_info']['filename'];
                    }else{
                        $item['show_title'] = '任务执行失败';
                    }
                    $list[] = $item;
                }
            }
            array_multisort(array_column($list,'created_at'),SORT_DESC,$list);
        }
        return $list;
    }

    public function delTask($task_id){
        $key = md5('EduceExport_'.get_called_class());
        $all_task_id = Tool::deJson($this->cacheService()->get($key));
        if(!empty($all_task_id)){
            $found_key = array_search($task_id,$all_task_id);
            if(false!==$found_key){
                //首先把文件夹删除
                $this->delDir($task_id);
                //再把任务信息删除
                $this->cacheService()->del($task_id);
                unset($all_task_id[$found_key]);
                $this->cacheService()->del($task_id.'_progress_read');
                $this->cacheService()->del($task_id.'_progress_write');
                $this->cacheService()->del($task_id.'_progress_merge');
                $this->cacheService()->del($task_id.'_is_normal');
                $this->cacheService()->del($task_id.'_completed_at');
                $this->cacheService()->set($key,Tool::enJson(array_unique($all_task_id)));
            }
        }
        return true;
    }

    public function delDir($task_id=null){
        if(is_null($task_id)){
            $files_dir = $this->taskInfo['files_dir'];
        }else{
            $task_info = Tool::deJson($this->cacheService()->get($task_id));
            $files_dir = $task_info['files_dir'];
        }

        if(is_dir($files_dir)){
            $handler_del = opendir($files_dir);
            while (($file = readdir($handler_del)) !== false) {
                if ($file != "." && $file != "..") {
                    $del_file = $files_dir . "/" . $file;
                    if(is_file($del_file)){
                        //删除文件
                        @unlink($del_file);
                    }
                }
            }
            @closedir($files_dir);
            @rmdir($files_dir);
        }
    }

    public $skipNum,$takeNum,$currentBatch;

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
     * 创建任务或导出数据
     * @return string
     */
    public function export(){
        //创建任务
        if(empty($this->taskInfo)){
            $export = new Export($this);
            $export->creation();
            return '正在生成数据';
        }else{
            //导出数据
            try{
                Store::begin($this);
            }catch (\Exception $exception){
                //报错了，把任务标记为不正常
                $this->cacheService()->set($this->taskInfo['task_id'].'_is_normal',0);
                //并记录日志
                Log::write($this->taskInfo['path_info'],$exception);
                $this->delDir();
            }
        }

    }

    /**
     * 保存到任务列表
     */
    public function save(){
        $key = md5('EduceExport_'.get_called_class());
        $all_task_id = Tool::deJson($this->cacheService()->get($key));
        $all_task_id[] = $this->taskInfo['task_id'];
        $this->cacheService()->set($key,Tool::enJson(array_unique($all_task_id)));
        $this->cacheService()->set($this->taskInfo['task_id'],Tool::enJson($this->taskInfo));
    }

    /**
     * 创建查询器
     * 在此方法中把查询器赋值给 $this->query 属性
     */
    abstract public function query();

    /**
     * 执行查询器获取数据
     * @return array
     */
    abstract public function resource():array;
}