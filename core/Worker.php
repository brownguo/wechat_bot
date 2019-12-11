<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2019/12/11
 * Time: 下午3:20
 */

class Worker
{
    public static function init()
    {
        self::installSignal();
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
        pcntl_signal(SIGINT,array('Worker','signalHandler'));
        pcntl_signal(SIGUSR1,array('Worker','signalHandler'));
        pcntl_signal(SIGUSR2,array('Worker','signalHandler'));
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
}

Worker::init();