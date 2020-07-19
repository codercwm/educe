<?php

namespace Codercwm\Educe\Services;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Codercwm\Educe\Concerns\Educe;
use Codercwm\Educe\Concerns\Sheet;

class Spout implements Sheet {
    /**
     * 把数据写入文件
     * @param Educe $educe
     * @param $data
     * @return string
     */
    public function write(Educe $educe,$data)
    {
        //文件后缀
        $writer = WriterEntityFactory::createWriter($educe->suffixType);
        //文件路径
        $writer->openToFile($educe->path);

        //写入表头
        if(method_exists($educe,'headers')){
            $writer->addRow(WriterEntityFactory::createRowFromArray($educe->headers()));
        }

        //写入数据
        foreach ($data as $datum){
            $writer->addRow(WriterEntityFactory::createRowFromArray($datum));
        }

        $writer->close();
    }
}