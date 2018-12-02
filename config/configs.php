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
             'get_group_url'    =>  'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxbatchgetcontact?type=ex&r=%s&lang=zh_CN&pass_ticket=%s',
             'synccheck_url'    =>  'https://%s/cgi-bin/mmwebwx-bin/synccheck?%s',
             'webwxsync_url'    =>  'https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxsync?sid=%s&skey=%s&pass_ticket=%s',
         );
    }

    public static function sync_url()
    {
        return array(
            'wx2.qq.com',
            'webpush.wx2.qq.com',
            'wx8.qq.com',
            'webpush.wx8.qq.com',
            'web2.wechat.com',
            'webpush.web2.wechat.com',
            'webpush.web.wechat.com',
            'webpush.weixin.qq.com',
            'webpush.wechat.com',
            'webpush1.wechat.com',
            'webpush2.wechat.com',
            'webpush.wx.qq.com',
        );
    }
}