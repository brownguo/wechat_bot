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
    protected static $redirect_uri;

    protected static $wxuin;
    protected static $wxsid;
    protected static $pass_ticket;
    protected static $skey;

    public static function _init()
    {
        self::$args = configs::args();
        self::$request_url = configs::request_url();

        self::_get_uuid();
        self::_showQRCode();
        self::_waitForLogin();
        self::_do_login();
    }

    public static function _get_uuid()
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
            logger::info('get uuid success,uuid:'.$uuid_arr[2]);
            logger::notice('uuid获取成功');
            self::$uuid = $uuid_arr[2];
        }
        else
        {
            logger::notice('uuid获取失败');exit();
        }
    }

    public static function _showQRCode()
    {
        $url = sprintf(self::$request_url['get_qrcode_url'],self::$uuid);

        $res = requests::curl_download($url.self::$uuid,'../logs/qrcode.jpg');

        if($res)
        {
            logger::notice('QRCode获取成功');
        }
        else
        {
            logger::notice('QRCode获取失败!');exit();
        }
    }

    public static function _waitForLogin()
    {
        $url = sprintf(self::$request_url['wait_scan_url'],self::$uuid,date::getTime());

        logger::notice('正在等待扫描');

        system('open ../logs/qrcode.jpg');

        while(true)
        {
            $res = requests::get($url);
            preg_match('/window.code=(.*?);/',$res,$is_scan);
            if($is_scan[1] == 201)
            {
                //logger::notice('扫描成功,等待确认登录');
                continue;
            }
            else if($is_scan[1] == 200)
            {
                logger::notice('确认登录');
                preg_match('/window.redirect_uri="(.*?)";/',$res,$redirect_uri);
                self::$redirect_uri = $redirect_uri[1];
                break;
            }
            else if($is_scan[1] == 408)
            {
                logger::notice('登录超时');
                break;
            }
            else
            {
                logger::notice('登录异常');
            }
        }
    }

    public static function _do_login()
    {
        $res = requests::get(self::$redirect_uri);

        $p = xml_parser_create();
        xml_parse_into_struct($p, $res, $vals, $key);
        xml_parser_free($p);

        if($vals[1]['value'] == 0)
        {
            self::$pass_ticket = $vals[6]['value'];
            self::$wxuin = $vals[5]['value'];
            self::$wxsid = $vals[4]['value'];
            self::$skey  = $vals[3]['value'];
            logger::info(print_r($vals,true));
        }
        else
        {
            logger::notice('登录失败~');
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