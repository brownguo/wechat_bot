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
        self::for_one_worker();
        self::run();
    }

    protected static function run()
    {
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

    protected static function for_one_worker()
    {
        $pid = pcntl_fork();
        print_r($pid);
    }
}

Worker::init();