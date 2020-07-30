<?php

namespace Codercwm\Educe\Services;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Codercwm\Educe\Concerns\Educe;
use Codercwm\Educe\Concerns\Sheet;
use Codercwm\Educe\Tool;

class Spout implements Sheet {

    public $writer,$educe;

    public function __construct(Educe $educe) {
        $this->educe = $educe;
    }

    public function before() {

        //这个是导出的文件存放文件夹
        $dir = $this->educe->taskInfo['files_dir'];

        //导出的文件，以当前批次命名，
        $filename = $this->educe->currentBatch;
        //文件后缀，获取pathinfo的文件后缀
        $extension = $this->educe->taskInfo['path_info']['extension'];

        //确定文件类型
        $this->writer = WriterEntityFactory::createWriter($extension);

        //打开文件
        Tool::whileTry(function($arg){
            $this->writer->openToFile($arg);
        },$dir.DIRECTORY_SEPARATOR.$filename.'.'.$extension);

    }

    /**
     * 把数据写入文件
     * @param $datum
     */
    public function write($datum)
    {
        //写入数据
        $this->writer->addRow(WriterEntityFactory::createRowFromArray($datum));
    }

    public function after() {
        $this->writer->close();
    }

    public function merge(){
        //文件路径信息，这个是最初的path属性文件路径信息
        $path_info = $this->educe->taskInfo['path_info'];
        //这个是导出的文件存放文件夹，这个文件夹下的文件会以数字命名（1.xlsx/2.xlsx/3.xlsx...），现在要做的就是把这个文件夹下的所有文件合并成一个文件
        $dir = $this->educe->taskInfo['files_dir'];
        //新文件的路径
        $write_file = $this->educe->path;

        //把文件中的数据全部读取出来放到一个新文件，spout组件使用此方式内存占用较少

        $writer = WriterEntityFactory::createWriter($path_info['extension']);
        $writer->openToFile($write_file);

        //写入表头
        if(method_exists($this->educe,'headers')){
            $writer->addRow(WriterEntityFactory::createRowFromArray($this->educe->headers()));
        }

        for($b=1;$b<=$this->educe->taskInfo['batch_count'];$b++){
            $read_file = $dir.'/'.$b.'.'.$path_info['extension'];

            Tool::whileTry(function($arg){
                if(!is_file($arg)){
                    throw new \Exception('file not exists');
                }
            },$read_file);

            $reader = ReaderEntityFactory::createReaderFromFile($read_file);

            Tool::whileTry(function($arg)use($reader){
                $reader->open($arg);
            },$read_file);

            foreach ($reader->getSheetIterator() as $sheet_index => $sheet) {
                foreach ($sheet->getRowIterator() as $key=>$row) {
                    $writer->addRow($row);
                    //记录数据写入进度，表示已经写了多少条数据到新文件里
                    $this->educe->cacheService()->incrby($this->educe->taskInfo['task_id'].'_progress_merge',1);
                }
            }

            //关闭文件
            $reader->close();

            //删除文件
            Tool::whileTry(function($arg){
                unlink($arg);
            },$read_file);
        }
        $writer->close();

        //删除文件夹
        Tool::whileTry(function($arg){
            rmdir($arg);
        },$dir);

    }

}