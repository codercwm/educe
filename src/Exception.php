<?php

namespace Codercwm\Educe;

use Exception as RootException;

class Exception extends RootException{

    public function __construct($message = "", $del = false)
    {
        if($del){
            //一旦报错就从缓存清除这个任务
            Progress::del();
        }

        parent::__construct($message);
    }
}