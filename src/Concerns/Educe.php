<?php

namespace Codercwm\Educe\Concerns;

use Codercwm\Educe\Export\Export;
use Codercwm\Educe\Log;
use Codercwm\Educe\Store;
use Codercwm\Educe\Tool;

abstract class Educe extends Service{

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

    public $skipNum,$takeNum,$currentBatch;

    /**
     * 创建实例
     * @return static
     */
    public static function create(){
        return new static(...func_get_args());
    }

    /**
     * 获取任务信息
     * @param null $get_task_id 获取指定的一个任务
     * @param array $filter_keys
     * @return array
     */
    public function select($get_task_id=null,$filter_keys=['path_info','files_dir','fields']){

        $list = [];
        $key = md5('EduceExport_'.get_called_class());
        $all_task_id = $get_task_id?[$get_task_id]:Tool::deJson($this->cacheService()->get($key));
        if(!empty($all_task_id)){
            foreach ($all_task_id as $task_id){
                $item = Tool::deJson($this->cacheService()->get($task_id));
                if($item){
                    $item['progress_read'] = $this->cacheService()->get($item['task_id'].'_progress_read');
                    $item['progress_write'] = $this->cacheService()->get($item['task_id'].'_progress_write');
                    $item['progress_merge'] = $this->cacheService()->get($item['task_id'].'_progress_merge');
                    $item['is_normal'] = $this->cacheService()->get($item['task_id'].'_is_normal');
                    $item['completed_at'] = $this->cacheService()->get($item['task_id'].'_completed_at');
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
                    foreach ($filter_keys as $filter_key){
                        unset($item[$filter_key]);
                    }
                    $list[] = $item;
                }
            }
            array_multisort(array_column($list,'created_at'),SORT_DESC,$list);
            return $get_task_id?$list[0]:$list;
        }

        return [];
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
                return true;

            }
        }
        return false;

    }

    public function delDir($task_id=null){
        if(is_null($task_id)){
            $files_dir = $this->taskInfo['files_dir'];
        }else{
            $task_info = $this->select($task_id,[]);
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
                //throw new \Exception('手动失败');
                Store::begin($this);
            }catch (\Exception $exception){
                //报错了，把任务标记为不正常
                $this->cacheService()->set($this->taskInfo['task_id'].'_is_normal',0);
                //并记录日志
                Log::write($this->taskInfo['path_info'],$exception);
                $this->onError();
            }
        }

    }

    /**
     * 保存任务
     * @param array $task_info
     */
    public function save(array $task_info){
        $key = md5('EduceExport_'.get_called_class());
        $all_task_id = Tool::deJson($this->cacheService()->get($key));
        $all_task_id[] = $task_info['task_id'];
        $this->cacheService()->set($key,Tool::enJson(array_unique($all_task_id)));
        $this->cacheService()->set($this->taskInfo['task_id'],Tool::enJson($task_info));
        $this->taskInfo = $task_info;
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

    /**
     * 文件导出完成后事件，可以在此方法中操作上传oss，生成下载连链接等
     * @return mixed
     */
    public function onComplete(){}

    /**
     * 任务报错时触发事件，例如把文件夹删除等
     * @return mixed
     */
    public function onError(){}
}