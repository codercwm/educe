<?php

namespace Codercwm\Educe;

class Tool{

    public static function enJson($data){
        return json_encode($data,JSON_UNESCAPED_UNICODE);
    }

    public static function deJson($data){
        return json_decode($data,true);
    }

    /**
     * 格式化内存大小
     */
    public static function formatMemorySize($size) {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    public static function whileTry($closure,$arg){
        $check_file_try = 1;
        while (true){
            try{
                $closure($arg);
                break;
            }catch (\Exception $exception){
                sleep(1);
                $check_file_try++;
                if(30<$check_file_try){
                    break;
                }
            }
        }
    }
}