<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2019/12/19
 * Time: 11:23 PM
 */


class Redis
{

    public static $prefix = 'pws_';
    public static $link;

    public static function init()
    {
        if(!extension_loaded('redis'))
        {
            echo "Redis extension was not found ..\n";
        }
    }

    public static function set()
    {

    }
}

Redis::init();