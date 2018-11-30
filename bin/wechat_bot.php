<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/11/29
 * Time: 6:04 PM
 */
define('SERVER_BASE', realpath(__dir__ . '/..') . '/');

date_default_timezone_set('Asia/Shanghai');

class wechat_bot
{
    protected static $args;
    protected static $request_url;

    //uuid
    protected static $uuid;

    public static function _init()
    {
        self::$args = configs::args();
        self::$request_url = configs::request_url();

        self::get_uuid();
    }

    public static function get_uuid()
    {
        $url = self::$request_url['get_uuid_url'];

        $args = array(
            'appid' =>self::$args['appid'],
            'fun'   =>'new',
            'lang'  =>self::$args['lang'],
            '_'     =>date::getTime(),
        );

        $res = requests::post($url,$args);

        preg_match('/window.QRLogin.code = (.*?); window.QRLogin.uuid = "(.*?)";/si',$res,$uuid_arr);

        if($uuid_arr[1] == 200)
        {
            logger::info('get uuid success');
            self::$uuid = $uuid_arr[2];
        }
    }
}

$LoadableModules = array('config','plugins');

spl_autoload_register(function($name){
    global $LoadableModules;
    foreach ($LoadableModules as $module)
    {
        $filename =  SERVER_BASE.$module.'/'.$name . '.php';
        if (file_exists($filename))
            require_once $filename;
    }
});wechat_bot::_init();