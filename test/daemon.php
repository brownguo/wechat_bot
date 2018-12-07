<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/12/7
 * Time: 1:44 PM
 */

define('PID_FILE', '../logs/wechat_bot.pid');

if(empty($argv[1]))
{
    echo "Cmd Error \n";exit();
}

$cmd = $argv[1];

switch ($cmd)
{
    case 'start':
        wechat_bot::_init();
        wechat_bot::_daemon();
        break;
    case 'status':
        wechat_bot::_init();
        wechat_bot::_status();
        break;
}

class wechat_bot
{
    protected static $statusInfo = array();

    public static function _init()
    {

    }
    public static function _daemon()
    {
        umask(0);

        $pid = pcntl_fork();

        if(-1 == $pid)
        {
            exit('daemon fail!');
        }
        else if($pid > 0)
        {
            //父进程退出
            exit(0);
        }
        // 子进程成为session leader
        if(-1 == posix_setsid())
        {
            // 出错退出
            exit("daemon fail,setsid fail");
        }

        $pid2 = pcntl_fork();

        if(-1 == $pid2)
        {
            exit('daemon fail!');
        }
        else if(0 !== $pid2)
        {
            // 结束第一子进程
            exit(0);
        }

        file_put_contents(PID_FILE,posix_getpid());
        chmod(PID_FILE, 0644);
        self::$statusInfo['start_time'] = time();
    }

    public static function _status()
    {

    }
}