<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2019/12/11
 * Time: 下午3:20
 */

class Worker
{

    //MasterID
    protected static $master_pid  = 0;
    //所有子进程
    protected static $worker_pids = array();
    //master与worker之间通信管道
    protected static $channels = array();

    public static function init()
    {
        self::$master_pid = posix_getpid();
        self::installSignal();
        #self::for_one_worker();
        self::createWorkers();
        #self::run();
    }

    protected static function run()
    {
        print_r(self::$channels);
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
            if(!extension_loaded('proctitle') && function_exists('setproctitle'))
            {
                setproctitle($title);
            }
            // >=php 5.5
            elseif (function_exists('cli_set_process_title'))
            {
                @cli_set_process_title($title);
            }
        }
    }

    protected static function createChannel()
    {
        // 建立进程间通信通道，目前是用unix域套接字
        $channel = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if(false === $channel)
        {
            return false;
        }

        #非阻塞模式
        stream_set_blocking($channel[0], 0);
        stream_set_blocking($channel[1], 0);
        return $channel;
    }

    protected static function createWorkers()
    {
        for($i=0;$i<2;$i++)
        {
            #echo $i.PHP_EOL;
            self::for_one_worker($i);
        }
    }
    protected static function for_one_worker($worker_id)
    {
        if(!($channel = self::createChannel()))
        {
            exit('Create Channel Fail!');
        }

        $pid = pcntl_fork();

        //主进程记录子进程pid,当前上线文为Master
        if($pid > 0)
        {
            fclose($channel[1]);
            self::$channels[$pid] = $channel[0];
            self::$worker_pids[$worker_id] = $pid;
            #unset($channel);

            //test
            fwrite(self::$channels[$pid], "TEST PID: $pid\n");
            sleep(1);
            echo (fgets(self::$channels[$pid]));  //这里不知道为什么收不到消息，日他妈的！
            exit(0);
            return $pid;
        }
        //子进程运行,当前上下文为Worker
        elseif($pid == 0)
        {
            //test
            fclose($channel[0]);
            fwrite($channel[1],"Message From Worker!\n");
            sleep(1);
            echo fgets($channel[1]);
            #file_put_contents('../logs/wechat_bot.log',date('Y-m-d H:i:s').'WorkerPid['.posix_getpid().']'.PHP_EOL,FILE_APPEND);
            #self::set_process_title(sprin  tf('PHPServerd Pid[%s]',$pid));
            exit(0);
        }
        //出错退出,-1
        else
        {
            exit('fork worker fail!');
        }
    }
}

Worker::init();