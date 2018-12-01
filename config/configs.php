<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/11/30
 * Time: 3:31 PM
 */

class configs
{
    public static function args()
    {
        return array(

            'appid' =>      'wx782c26e4c19acffb',
            'lang'  =>      'zh_CN',
        );
    }

    public static function request_url()
    {
         return array(
             'get_uuid_url'     =>  'https://login.weixin.qq.com/jslogin',
             'get_qrcode_url'   =>  'https://login.weixin.qq.com/qrcode/%s?t=webwx',
             'wait_scan_url'    =>  'https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?uuid=%s&tip=1&_=%s',
             'wechat_init_url'  =>  'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxinit?pass_ticket=%s&skey=%s&r=%s',
             'notify_url'       =>  'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxstatusnotify?pass_ticket=%s',
             'get_contact_url'  =>  'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxgetcontact?pass_ticket=%s&skey=%s&r=%s',
         );
    }
}