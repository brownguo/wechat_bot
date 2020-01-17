<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2020/1/16
 * Time: 下午4:14
 */

use \Workerman\Worker;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../plugins/Workerman/Autoloader.php';

$ws_worker = new Worker('websocket://0.0.0.0:8080');
$ws_worker->count = 8;
// 连接建立时给对应连接设置定时器
$ws_worker->onConnect = function($connection)
{

    // 每10秒执行一次
    $time_interval = 10;
    $connect_time = time();
    // 给connection对象临时添加一个timer_id属性保存定时器id
    $connection->timer_id = Timer::add($time_interval, function()use($connection, $connect_time)
    {
        $connection->send($connect_time);
    });
};
// 连接关闭时，删除对应连接的定时器
$ws_worker->onClose = function($connection)
{
    // 删除定时器
    Timer::del($connection->timer_id);
};

// 运行worker
Worker::runAll();