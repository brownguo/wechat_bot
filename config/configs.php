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
             'get_uuid_url'  =>  'https://login.weixin.qq.com/jslogin',
         );
    }
}