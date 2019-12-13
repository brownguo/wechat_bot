<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2019/12/11
 * Time: 下午3:20
 */

class Worker
{

    protected static $master_pid  = 0;
    protected static $worker_pids = array();

    public static function init()
    {
        self::$master_pid = posix_getpid();
        self::installSignal();
        #self::for_one_worker();
        self::createWorkers();
        self::run();
    }

    protected static function run()
    {
        print_r(self::$worker_pids);
        while(true)
        {
            pcntl_signal_dispatch();
        }
    }
    protected static function installSignal()
    {
        pcntl_signal(SIGINT,array('Worker','signalHandler'),false);
        pcntl_signal(SIGUSR1,array('Worker','signalHandler'),false);
        pcntl_signal(SIGUSR2,array('Worker','signalHandler'),false);
    }

    public static function signalHandler($signal)
    {
        switch ($signal)
        {
            case SIGINT:
                echo sprintf("[%s] process,killed .. \n",getmypid());
                exit(0);
                break;
            case SIGUSR1:
                echo 'status'.PHP_EOL;
                break;
            case SIGUSR2:
                echo 'debug'.PHP_EOL;
                break;
        }
    }

    protected static function set_process_title($title)
    {
        if (!empty($title))
        {
            // 需要扩展
            if(extension_loaded('proctitle') && function_exists('setproctitle'))
            {
                @setproctitle($title);
            }
            // >=php 5.5
            elseif (function_exists('cli_set_process_title'))
            {
                @cli_set_process_title($title);
            }
        }
    }

    protected static function createWorkers()
    {
        for($i=1;$i<=5;$i++)
        {
            #echo $i.PHP_EOL;
            self::for_one_worker($i);
        }
    }
    protected static function for_one_worker($worker_id)
    {
        $pid = pcntl_fork();

        //主进程记录子进程pid
        if($pid > 0)
        {
            self::set_process_title(sprintf('PHPServerd Pid[%s]',$pid));
            self::$worker_pids[$worker_id] = $pid;
        }
        //子进程运行
        elseif($pid == 0)
        {
            exit(0);
        }
        //出错退出
        else
        {
            exit('fork worker fail!');
        }
    }
}

Worker::init();