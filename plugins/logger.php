<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/11/29
 * Time: 5:05 PM
 */

class logger
{
    public static $log_file_path = __DIR__.'/../logs/wechat_bot.log';

    public static function info($msg)
    {
        self::add($msg,'info');
    }

    public static function error($msg)
    {
        self::add($msg,'error');
    }

    public static function warning($msg)
    {
        self::add($msg,'warning');
    }

    public static function debug($msg)
    {
        self::add($msg,'debug');
    }

    public static function add($msg,$log_type)
    {
        if(!empty($log_type))
        {
            $log_type = strtoupper($log_type);

            $msg = sprintf("%s [%s] %s".PHP_EOL,date('Y-m-d H:i:s',time()),$log_type,$msg);

            file_put_contents(self::$log_file_path,$msg, FILE_APPEND | LOCK_EX);
        }
    }
}