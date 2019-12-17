<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2019/12/11
 * Time: 下午3:20
 */

define('PID_FILE', '../logs/wechat_bot.pid');

class Worker
{
    //MasterID
    protected static $master_pid  = 0;
    //所有子进程
    protected static $worker_pids = array();
    //master与worker之间通信管道
    protected static $channels = array();

    protected static $pid_file = '../logs/wechat_bot.pid';

    protected static $worker_pid;
    protected static $worker_info = array();

    public static function init()
    {
        self::$master_pid = posix_getpid();
        file_put_contents(PID_FILE, posix_getpid());
        chmod(PID_FILE, 0644);
        self::installSignal();
        self::createWorkers();
        self::run();
    }

    protected static function run()
    {
        #print_r(self::$worker_pids);
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
                self::stop_all_worker();
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

    #停止所有进程，如果当前任务没有执行完成，ctrl+c之后子进程的会没有人接管变为1。
    public static function stop_all_worker()
    {
        $pid = @file_get_contents(PID_FILE);
        if(empty($pid))
        {
            exit("not running!\n");
        }
        else
        {
            #先把master干掉
            posix_kill($pid,SIGINT);
            foreach (self::$worker_pids as $key => $worker_pid)
            {
                posix_kill($worker_pid,SIGKILL);
            }
            unlink(PID_FILE);
            echo "Server stop success\n";
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
        for($i=1;$i<5;$i++)
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
            self::$channels[$pid] = $channel[0];
            self::$worker_pids[$worker_id] = $pid;
            #unset($channel);
            #return $pid;
        }
        //子进程运行,当前上下文为Worker
        elseif($pid == 0)
        {
            $pid = posix_getpid();
            #self::test_task($pid);
            exit(0);
        }
        //出错退出,-1
        else
        {
            exit('fork worker fail!');
        }
    }

    protected static function test_task($pid)
    {
        $i = 0;
        while($i < 100000000)
        {
            file_put_contents(sprintf('../logs/process_%s.log',$pid),date('Y-m-d H:i:s').'Line:'.$i .' WorkerPid['.$pid.']'.PHP_EOL,FILE_APPEND);
            $i++;
        }
    }


    public static function get_worker_status()
    {
        $mem    = round(memory_get_usage(true)/(1024*1024),2);
        $data   = array(
            'mem' => $mem,
        );
        $data = json_encode($data);
        #self::$worker_info[posix_getpid()] = $data;
        return $data;
    }

    public static function display_ui()
    {
        $display_str = "------------------------------PROCESS STATUS------------------------------\n";
        $display_str .= "pid" . str_pad('', 10 - strlen('pid')) .
            "memory" . str_pad('', 10 - strlen('memory')) .
            "\n";

        $display_str .= str_pad('87651', 10 + 2).
                        str_pad('2MB', 11 + 3)."\n";

        print_r(self::get_worker_status());
        echo $display_str;
    }
}

if(empty($argv[1]))
{
    echo "Usage: {status}\n";
    exit(0);
}
$cmd = $argv[1];

switch ($cmd)
{
    case 'start':
        Worker::init();
        break;
    case 'stop':
        worker::stop_all_worker();
        break;
    case 'status':
        worker::display_ui();
        break;
}