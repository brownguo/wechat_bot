<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2018/11/29
 * Time: 11:50 AM
 */

class requests
{

    const VERSION = '1.0.1';

    protected static $ch = null;

    public static $timeout = 30;
    public static $headers = array();
    public static $http_info;
    public static $result;
    public static $out_header;

    public static function _init()
    {
        if(!is_resource(self::$ch))
        {
            self::$ch = curl_init();

            curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER,true);
            curl_setopt(self::$ch, CURLOPT_HEADER, false);
            curl_setopt(self::$ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36");
            curl_setopt(self::$ch, CURLOPT_TIMEOUT, self::$timeout);
            curl_setopt(self::$ch, CURLOPT_NOSIGNAL,true);
        }

        return self::$ch;
    }

    public static function request($url,$method,$args,$cookies=null)
    {
        $method = strtoupper($method);

        if($method == 'GET' && !empty($args))
        {
            $url = $url.(strpos($url, '?') === false ? '?' : '&').http_build_query($args);
        }

        if($method == 'POST')
        {
            curl_setopt(self::$ch, CURLOPT_POST, true);
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS,http_build_query($args));
        }

        if(strpos($url, 'https') !== false)
        {
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if(!empty($cookies))
        {
            curl_setopt(self::$ch, CURLOPT_COOKIEFILE,$cookies);
        }

        curl_setopt(self::$ch, CURLOPT_ENCODING,'gzip');
        curl_setopt(self::$ch, CURLOPT_URL, $url);
        curl_setopt(self::$ch, CURLINFO_HEADER_OUT, true);

        self::$result     = curl_exec (self::$ch);

        if(self::$result === false)
        {
            echo 'Curl error: ' . curl_error(self::$ch);
        }

        self::$http_info = curl_getinfo(self::$ch);

        curl_close(self::$ch);
    }

    public static function get($url,$args)
    {
        self::_init();
        self::request($url,'get',$args);
        return self::$result;
    }

    public static function post($url,$args)
    {
        self::_init();
        self::request($url,'post',$args);
        return self::$result;
    }

    public static function get_http_info()
    {
        return self::$http_info;
    }
}