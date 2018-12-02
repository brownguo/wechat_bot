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

    protected static $BaseRequest = array();
    protected static $DeviceID;
    protected static $FromUserName;

    protected static $MemberCount;
    protected static $ContactList = array();//联系人列表
    protected static $GroupList = array();        //群组列表
    protected static $PublicUsersList = array(); //公众号
    protected static $SpecialUsersList = array();
    protected static $Synckey = array();

    public static function _init()
    {
        self::$args = configs::args();
        self::$request_url = configs::request_url();

        self::_get_uuid();
        self::_showQRCode();
        self::_waitForLogin();
        self::_do_login();
        self::_wechat_init();
        self::_wechat_notify();
        self::_wechat_get_contact();
        self::_get_group_list();
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
            self::$DeviceID = 'e'.time();

            self::$pass_ticket = $vals[6]['value'];
            self::$wxuin = $vals[5]['value'];
            self::$wxsid = $vals[4]['value'];
            self::$skey  = $vals[3]['value'];


            self::$BaseRequest = array(
                'BaseRequest'=>array(
                    'Uin'       => self::$wxuin,
                    'Sid'       => self::$wxsid,
                    'Skey'      => self::$skey,
                    'DeviceID'  => self::$DeviceID,
                )
            );

            logger::info(print_r($vals,true));
            logger::notice('登录成功');
        }
        else
        {
            logger::notice('登录失败');
        }
    }

    public static function _wechat_init()
    {
        $url = sprintf(self::$request_url['wechat_init_url'],self::$pass_ticket,self::$skey,time());

        $args = self::$BaseRequest;

        $res = requests::post($url,json_encode($args,JSON_UNESCAPED_UNICODE));

        $res = json_decode($res,true);

        if($res['BaseResponse']['Ret'] == 0)
        {

            self::$FromUserName = $res['User']['UserName'];
            self::$Synckey      = $res['SyncKey'];
            logger::notice('微信初始化成功');
            logger::info('记录微信初始化参数'.print_R($res,true));
        }
    }

    public static function _wechat_notify()
    {
        $url  = sprintf(self::$request_url['notify_url'],self::$pass_ticket);

        $args = array(
            'BaseRequest'=>array(
                'Uin'       => self::$wxuin,
                'Sid'       => self::$wxsid,
                'Skey'      => self::$skey,
                'DeviceID'  => self::$DeviceID,
            ),
            'ClientMsgId'   =>date::getTime(),
            'Code'          =>'3',
            'FromUserName'  =>self::$FromUserName,
            'ToUserName'    =>self::$FromUserName,
        );
        $args = json_encode($args,JSON_UNESCAPED_UNICODE);

        $res = requests::post($url,$args);

        $res = json_decode($res,true);

        if($res['BaseResponse']['Ret'] == 0)
        {
            logger::notice('开启微信状态通知成功');
            logger::info('记录开启微信状态通知参数'.print_r($res,true));
        }
    }

    public static function _wechat_get_contact()
    {
        self::$DeviceID = 'e'.time();

        $url  = sprintf(self::$request_url['get_contact_url'],self::$pass_ticket,self::$skey,time());

        $args = self::$BaseRequest;

        $args['DeviceID'] = self::$DeviceID;

        $res  = requests::post($url,json_encode($args,JSON_UNESCAPED_UNICODE));

        $res  = json_decode($res,true);

        logger::info(print_r($res,true));

        if($res['BaseResponse']['Ret'] == 0)
        {
            self::$MemberCount = $res['MemberCount'];
            self::$ContactList = $res['MemberList'];

            foreach (self::$ContactList as $key=>$val)
            {
                if($val['VerifyFlag'] % 8 == 0 && $val['VerifyFlag'] !=0)
                {
                    array_push(self::$PublicUsersList,$val);
                }
                if(strpos($val['UserName'],'@@') !== false)
                {
                    array_push(self::$GroupList,$val);
                }
            }
            logger::notice(sprintf('共%s个好友,公众号:%s,群组:%s',self::$MemberCount,count(self::$PublicUsersList),
                count(self::$GroupList)));
        }
    }

    public static function _get_group_list()
    {

        $url = sprintf(self::$request_url['get_group_url'],date::getTime(),self::$pass_ticket);

        foreach (self::$GroupList as $k=>$v)
        {
            $_build_g_list[] = array(
                'UserName'=>$v['UserName'],
                'ChatRoomId'=>'',
            );
        }

        $args = array(
            'BaseRequest'=>array(
                'Uin'     =>self::$wxuin,
                'Sid'     =>self::$wxsid,
                'Skey'    =>self::$skey,
                'DeviceID'=>self::$DeviceID
            ),
            'Count'       =>count(self::$GroupList),
            'List'        =>$_build_g_list,
        );

        $args = json_encode($args,JSON_UNESCAPED_UNICODE);

        logger::info('开始记录获取群组参数'.print_r($args,true));

        $res = requests::post($url,$args);

        $res = json_decode($res,true);

        logger::info('开始记录获取群组Result'.print_r($res,true));

        if($res['BaseResponse']['Ret'] == 0)
        {
            self::$GroupList = $res['ContactList'];

            logger::notice('获取群组成功');
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