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

    protected static $sync_url;

    protected static $wxuin;
    protected static $wxsid;
    protected static $pass_ticket;
    protected static $skey;

    protected static $BaseRequest = array();
    protected static $DeviceID;
    protected static $FromUserName;
    protected static $User;

    protected static $GroupMemeberList = array();
    protected static $MemberCount;
    protected static $ContactList = array();//联系人列表
    protected static $GroupList = array();        //群组列表
    protected static $PublicUsersList = array(); //公众号
    protected static $SpecialUsersList = array();
    protected static $Synckey = array();
    protected static $syncHost;

    protected static $no_format_synckey;
    protected static $SyncCheckKey;

    protected static $lastCheckTimes;

    public static function _init()
    {
        self::$args = configs::args();
        self::$request_url = configs::request_url();
        self::$sync_url = configs::sync_url();

        self::_get_uuid();
        self::_showQRCode();
        self::_waitForLogin();
        self::_do_login();
        self::_wechat_init();
        self::_wechat_notify();
        self::_wechat_get_contact();
        self::_get_group_list();
        self::_testing_synccheck();
        self::_listenMsgMode();
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
            logger::info('QRCode获取成功');
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
        $res = requests::get(self::$redirect_uri,null,true,false);

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

            logger::info('登录成功,'.json_encode(self::$BaseRequest));
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

        $res = requests::post($url,json_encode($args,JSON_UNESCAPED_UNICODE),false,false);

        $res = json_decode($res,true);

        if($res['BaseResponse']['Ret'] == 0)
        {
            self::$FromUserName = $res['User']['UserName'];

            self::$no_format_synckey = $res['SyncKey'];

            self::$User = $res['User'];

            self::$Synckey = self::_format_synckey($res['SyncKey']['List']);

            logger::notice('微信初始化成功');
            logger::info('微信初始化成功');
        }
        else
        {
            logger::notice('微信初始化失败');
            logger::info(sprintf('微信初始化失败res:%s,Args:%s',print_r($res,true),print_r($args,true)));
            exit();
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
        }
        else
        {
            logger::notice('开启微信状态通知失败');
            logger::info('记录开启微信状态通知失败参数'.print_r($res,true));
        }
    }

    public static function _wechat_get_contact()
    {
        self::$DeviceID = 'e'.time();

        $url  = sprintf(self::$request_url['get_contact_url'],self::$pass_ticket,self::$skey,time());

        $args = self::$BaseRequest;

        $args['DeviceID'] = self::$DeviceID;

        $res  = requests::post($url,json_encode($args,JSON_UNESCAPED_UNICODE),false,false);

        $res  = json_decode($res,true);

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
            logger::info('好友列表获取成功');
            logger::notice(sprintf('共%s个好友,公众号:%s,群组:%s',self::$MemberCount,count(self::$PublicUsersList),
                count(self::$GroupList)));
        }
        else
        {
            logger::info('好友列表获取失败'.print_r($res,true));
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

        $res = requests::post($url,$args,false,false);

        $res = json_decode($res,true);

        if($res['BaseResponse']['Ret'] == 0)
        {
            foreach ($res['ContactList'] as $k=>$memberList)
            {
                foreach ($memberList['MemberList'] as $member)
                {
                    array_push(self::$GroupMemeberList,$member);
                }
            }

            self::$GroupList = $res['ContactList'];
            logger::notice('获取群组成功');
        }
        else
        {
            logger::info('开始记录获取群组失败参数'.print_r($args,true));
            logger::info('开始记录获取群组失败Result'.print_r($res,true));
        }
    }

    public static function _testing_synccheck()
    {
        logger::notice('正在进行同步线路测试');

        $url = self::$sync_url;

        foreach ($url as $idx=>$domain)
        {
            self::$syncHost = $domain;

            $result = self::_synccheck();

            if($result['retcode'] == 0)
            {
                self::$syncHost = $domain;
                logger::info(sprintf('线路：%s 请求成功',$domain));
                logger::notice(sprintf('线路：%s 请求成功',$domain));
                break;
            }
            else
            {
                logger::info(sprintf('线路：%s 请求失败',$domain));
                logger::notice(sprintf('线路：%s 请求失败,retcode:%s',$domain,$result['retcode']),'error');
            }
        }
    }

    public static function _synccheck()
    {
        $call_func_info = debug_backtrace();

        logger::info(sprintf('方法%s在%s行调用了%s',$call_func_info[1]['function'],
            $call_func_info[1]['line'],$call_func_info[0]['function']));

        $args = array(
            'r'         =>date::getTime(),
            'skey'      =>self::$skey,
            'sid'       =>self::$wxsid,
            'uin'       =>self::$wxuin,
            'deviceid'  =>self::$DeviceID,
            'synckey'   =>self::$Synckey,
            '_'         =>date::getTime(),
        );

        $url = sprintf(self::$request_url['synccheck_url'],self::$syncHost,http_build_query($args));

        $res = requests::get($url,null,false,true);

        preg_match('/window.synccheck={retcode:"(.*)",selector:"(.*)"}/si',$res,$sync_code);

        return array('retcode'=>$sync_code[1],'selector'=>$sync_code[2]);
    }

    public static function _listenMsgMode()
    {
        logger::notice('进入消息监听模式');

        while(true)
        {
            self::$lastCheckTimes = time();

            $res = self::_synccheck();

            if($res['retcode'] == '1100')
            {
                logger::notice('你在手机上退出了微信! See u !');exit(0);
            }
            else if($res['retcode'] == '1101')
            {
                logger::notice('你在其他地方登录了微信,See u !');exit();
            }
            else if($res['retcode'] == '0')
            {
                if($res['selector'] == '2')
                {
                    $r = self::_webwxsync();
                    self::_handleMsg($r);
                }
                else if($res['selector'] == '6')
                {
                    logger::notice('红包来啦~');
                    $r = self::_webwxsync();
                }
                else if($res['selector'] == '7')
                {
                    logger::notice('在手机上使用微信~');
                    $r = self::_webwxsync();
                }
                else if($res['selector'] == '0')
                {
                    sleep(1);
                }
            }

            if((time() - self::$lastCheckTimes) <= 20)
            {
                sleep(time() - self::$lastCheckTimes);
            }
        }
    }

    public static function _webwxsync()
    {
        $url = sprintf(self::$request_url['webwxsync_url'],self::$wxsid,self::$skey,self::$pass_ticket);

        $args = array(
            'BaseRequest'=>array(
                'Uin'     =>self::$wxuin,
                'Sid'     =>self::$wxsid,
                'Skey'    =>self::$skey,
                'DeviceID'=>self::$DeviceID
            ),
            'SyncKey'     =>self::$no_format_synckey,
            'rr'          =>~(int)time(),
        );

        $res = requests::post($url,json_encode($args,JSON_UNESCAPED_UNICODE),false,false);

        $res = json_decode($res,true);

        if($res['BaseResponse']['Ret'] == 0)
        {
            logger::info('_webwxsync请求成功,synckey:'.json_encode($res['SyncKey'],JSON_UNESCAPED_UNICODE));

            self::$no_format_synckey = $res['SyncKey'];
            self::$Synckey = self::_format_synckey($res['SyncKey']['List']);
            self::$SyncCheckKey = $res['SyncCheckKey'];
            return $res;
        }
        else
        {
            logger::notice('_webwxsync请求失败');
            logger::info('_webwxsync请求失败,记录日志:%s,_webwxsync请求失败args:%s',print_r($res,true),print_r($args));
            return false;
        }
    }

    public static function _format_synckey($synckey_arr)
    {
        $call_func_info = debug_backtrace();

        logger::info(sprintf('方法%s在%s行调用了%s',$call_func_info[1]['function'],
            $call_func_info[1]['line'],$call_func_info[0]['function']));

        $SyncKey_value = '';
        foreach ($synckey_arr as $k=>$v)
        {
            $SyncKey_value.=$v['Key']."_".$v['Val']."|";
        }
        $SyncKey_value = trim($SyncKey_value,"|");
        return $SyncKey_value;
    }

    public static function _handleMsg($res)
    {
        foreach ($res['AddMsgList'] as $k=>$msg)
        {
            $msgType = $msg['MsgType'];
            $content = $msg['Content'];
            $msgid   = $msg['MsgId'];
            $name    = self::_getUserRemarkName($msg['FromUserName']);

            if($msgType == 1)
            {
                self::_showMsg($msg);
                logger::notice(sprintf('收到来自[%s]的消息,内容为:%s',$name,$content));
            }
            else if($msgType == 3)  //图片
            {
                logger::notice(sprintf('收到来自[%s]的图片消息,图片一会儿在开发',$name));
            }
            else if($msgType == 34) //语音
            {
                logger::notice(sprintf('收到来自[%s]的语音消息,语音一会儿在开发',$name));
            }
            else if($msgType == 42) //名片
            {
                logger::notice(sprintf('收到来自[%s]的名片消息,名片一会儿在开发',$name));
            }
            else if($msgType == 47) //动画表情
            {
                logger::notice(sprintf('收到来自[%s]的动画消息,动画一会儿在开发',$name));
            }
            else if($msgType == 49) //链接
            {
                logger::notice(sprintf('收到来自[%s]的链接消息,链接一会儿在开发',$name));
            }
            else if($msgType == 51)
            {
                logger::notice(sprintf('收到来自[%s]的链接消息,链接2一会儿在开发',$name));
            }
            else if($msgType == 62) //小视频
            {
                logger::notice(sprintf('收到来自[%s]的小视频消息,链接2一会儿在开发',$name));
            }
            else if($msgType == 10002)  //撤回了消息
            {
                logger::notice(sprintf('收到来自[%s]的撤回消息,链接2一会儿在开发',$name));
            }
            else
            {
                logger::notice(sprintf('收到来自[%s]的红包消息,链接2一会儿在开发',$name));
                //表情,链接,红包
            }
        }
    }

    public static function _showMsg($msg)
    {
        $srcName = self::_getUserRemarkName($msg['FromUserName']);

        $dstName = self::_getUserRemarkName($msg['ToUserName']);

        $content = $msg['Content'];
        $msg_id  = $msg['MsgId'];

        if($msg['ToUserName'] == 'filehelper')
        {
            $dstName = '文件传输助手';
        }
        //群消息
        if(strpos($msg['FromUserName'],'@@') !== false)
        {
            $member_id = explode(':<br/>',$content);
            $srcName = self::_getUserRemarkName($member_id[0]);
            $dstName = 'GROUP';
        }
        echo $srcName.'|'.$dstName.'|'.$content.'|'.$msg_id.PHP_EOL;
    }
    public static function _getUserRemarkName($user_id)
    {
        if ($user_id == self::$User['UserName'])
        {
            return self::$User['NickName']; //自己
        }
        if(strpos($user_id,'@@') !== false)
        {
            $name = self::_getGroupName($user_id);
        }
        else
        {
            foreach (self::$PublicUsersList as $k=>$member)
            {
                if($member['UserName'] == $user_id)
                {
                    if(!empty($member['RemarkName']))
                        $name = $member['RemarkName'];
                    else
                        $name = $member['NickName'];
                }
            }

            foreach (self::$ContactList as $k=>$member)
            {
                if($member['UserName'] == $user_id)
                {
                    if(!empty($member['RemarkName']))
                        $name = $member['RemarkName'];
                    else
                        $name = $member['NickName'];
                }
            }

            foreach (self::$GroupMemeberList as $k=>$member)
            {

                if($member['UserName'] == $user_id)
                {
                    if(!empty($member['DisplayName']))
                    {
                        $name = $member['DisplayName'];
                    }
                    else
                    {
                        $member['NickName'];
                    }
                }
            }
        }
        return $name;
    }

    public static function _getGroupName($group_id)
    {
        $name = '未知群';

        foreach (self::$GroupList as $k=>$member)
        {
            if($member['UserName'] == $group_id)
            {
                $name = $member['NickName'];
            }
        }
        if($name == '未知群')
        {
            $group_list = self::_getNameById($group_id);
            foreach ($group_list as $k=>$group)
            {
                array_push(self::$GroupList,$group);
                if($group['UserName'] == $group_id)
                {
                    $name       = $group['NickName'];
                    $MemberList = $group['MemberList'];

                    foreach ($MemberList as $key=>$member)
                    {
                        array_push(self::$GroupMemeberList,$member);
                    }
                }
            }
        }
        return $name;
    }

    public static function _getNameById($id)
    {
        $url = sprintf(self::$request_url['get_group_url'],date::getTime(),self::$pass_ticket);

        $_build_g_list[] = array(
            'UserName'=>$id,
            'ChatRoomId'=>'',
        );
        $args = array(
            'BaseRequest'=>array(
                'Uin'     =>self::$wxuin,
                'Sid'     =>self::$wxsid,
                'Skey'    =>self::$skey,
                'DeviceID'=>self::$DeviceID
            ),
            'Count'       =>count($_build_g_list),
            'List'        =>$_build_g_list
        );

        $args = json_encode($args,JSON_UNESCAPED_UNICODE);

        $res = requests::post($url,$args,false,false);

        $res = json_decode($res,true);

        if($res['BaseResponse']['Ret'] == 0)
        {
            return $res['ContactList'];
        }
        else
        {
            logger::info('获取群组失败了Args:%sRes:%s',print_r($args,true),print_r($res));
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