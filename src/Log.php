<?php

namespace Codercwm\Educe;

class Log{
    public static function write($path_info,$exception){
        if($exception instanceof \Exception){
            $str = $exception->getMessage().' FILE : '.$exception->getFile().' LINE : '.$exception->getLine().PHP_EOL;
            $str .= $exception->getTraceAsString();
        }else{
            if(is_string($exception)){
                $str = $exception;
            }else{
                $str = Tool::enJson($exception);
            }
        }

        $str = '['.date('Y-m-d H:i:s').'] '.$str.PHP_EOL;

        $log = $path_info['dirname'].DIRECTORY_SEPARATOR.$path_info['filename'].'.log';

        Tool::whileTry(function($arg){
            file_put_contents($arg[0],$arg[1],FILE_APPEND);
        },[$log,$str]);

    }
}