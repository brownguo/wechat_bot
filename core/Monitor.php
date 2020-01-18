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
require_once __DIR__ . '/../plugins/vendor/autoload.php';

class Monitor
{

    protected static $client;

    protected static $configs = array(
        'url'       =>  'https://api.nike.com/product_feed/threads/v2/?',
        'filter'    =>  array(
            'marketplace(CN)','language(zh-Hans)','upcoming(true)',
            'channelId(010794e5-35fe-4e32-aaff-cd2c74f89d61)',
            'exclusiveAccess(true,false)'
        ),
        'fields'    =>array(
            'active','id','lastFetchTime','productInfo',
        ),
        'sort'      =>'effectiveStartSellDateAsc',
        'count'     =>2,
        'anchor'    =>0,
        'publishType' => array(
            'FLOW'  => '先到先得(FLOW)',
            'LEO'   => '限量(LEO)',
            'DAN'   => '抽签(DAN)',
        ),
    );

    public static function _init()
    {
        static::$client = new GuzzleHttp\Client();
    }

    public static function getConfigs()
    {
        return static::$configs;
    }


    public static function handle_url_params()
    {
        $configs    = static::getConfigs();
        $url = $configs['url'].sprintf('anchor=%s&count=%s&',$configs['anchor'],$configs['count']);
        foreach ($configs['filter'] as $filter)
        {
            $url .= sprintf('filter=%s&',$filter);
        }
        foreach ($configs['fields'] as $fields)
        {
            $url .= sprintf('fields=%s&',$fields);
        }
        $url .= sprintf('soft=%s',$configs['sort']);
        return $url;
    }

    public static function get_product_feed()
    {
        $url        = static::handle_url_params();
        $headers    = array(
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36',
        );
        $response   = static::$client->request('GET',$url,['headers'=>$headers])->getBody();
        $response   = json_decode($response,true);

        foreach ($response['objects'] as $products)
        {
            $productInfo[] = array(
                'id'            =>$products['id'],
                'lastFetchTime' => strtotime($products['lastFetchTime']),
                'productInfo'   =>$products['productInfo'],
            );
        }
        return $productInfo;
    }
}
Monitor::_init();
Monitor::get_product_feed();

exit();

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