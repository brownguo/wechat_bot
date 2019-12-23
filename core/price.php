<?php
/**
 * Created by PhpStorm.
 * User: guoyexuan
 * Date: 2019/12/23
 * Time: 上午11:50
 */

include_once "../plugins/requests.php";
date_default_timezone_set('Asia/Shanghai');

class price
{
    protected static $nice_dn = '%E9%83%AD%E7%83%A8%E7%82%AB%E7%9A%84iPhone';
    protected static $nice_dt = 'iPhone10%2C6';
    protected static $nice_current_status;
    protected static $nice_want_buy_status; //求购价格
    protected static $nice_sale_status; //预售价格
    protected static $buy_price = 1199;
    protected static $nice_seid = '6f7cc1ddafcdc899f7b042a3ec760000';
    protected static $nice_smdt = '20190110114823f5d8008f11fcf9de269ed98cb6ca42fb01eeaa7f9ea88930';
    protected static $nice_did = '9911d52ea8665c2be1975e051c63ca69';
    protected static $nice_goods_detail;

    public static function start()
    {
        while(true)
        {
            self::get_price_by_niceapp();
            sleep(rand(5,10));
        }
    }
    protected static function get_subtraction()
    {
        $time = explode (" ", microtime () );
        $time = $time [1] . ($time [0] * 1000);
        $time2 = explode ( ".", $time );
        $time = $time2 [0];
        return $time;
    }
    public static function get_price_by_niceapp()
    {
        $headers = array(
            "Host:api.oneniceapp.com",
            "Accept: */*",
            "Content-Type:application/json; charset=utf-8",
            "Connection:keep-alive",
            "User-Agent:KKShopping/5.4.32 (iPhone X; iOS 13.2.3; Scale/3.00)",
            "Content-Length:123",
            "Accept-Language:zh-Hans-CN;q=1, en-CN;q=0.9, zh-Hant-CN;q=0.8",
            "Accept-Encoding:gzip, deflate, br",

        );

        $data = 'nice-sign-v1://d3fde8f8939ae991c98e5635b8f71d8e:e0caf70b753bbea4/{"id":"252897","token":"KpmZBVLLoySWokS4iiXXPlvPb3gdnQ3q"}';

        $detail_url = sprintf('https://115.182.19.27/Product/detail?a_x=-0.025253&a_y=-0.855850&a_z=-0.518097&abroad=no&amap_latitude=40.033808&amap_longitude=116.366137&appv=5.4.32.20&ch=AppStore_5.4.32.20&did=%s&dn=%s&dt=%s&g_x=0.127480&g_y=-0.164997&g_z=-0.186533&geoacc=10&im=81D6A92E-AF0E-47FC-9BFD-0FAFE5E3EE97&la=cn&latitude=40.032434&lm=weixin&longitude=116.359918&lp=-1.000000&n_bssid=&n_dns=10.226.1.1&n_ssid=&net=0-0-wifi&osn=iOS&osv=13.2.3&seid=%s&sh=812.000000&sm_dt=%s&src=sneaker_search&sw=375.000000&token=KpmZBVLLoySWokS4iiXXPlvPb3gdnQ3q&tpid=goods_detail&ts=%s',
            self::$nice_did,
            self::$nice_smdt,
            self::$nice_seid,
            self::$nice_dn,
            self::$nice_dt,
            self::get_subtraction());

        $detail_response = requests::post($detail_url,$data,$headers);
        $detail_response = json_decode($detail_response,true);
        if($detail_response['code'] == 0)
        {
            self::$nice_goods_detail = $detail_response['data'];
        }

        $url = sprintf('https://115.182.19.27/Sneakerpurchase/priceInfosV1?a_x=-0.025253&a_y=-0.855850&a_z=-0.518097&abroad=no&amap_latitude=40.033808&amap_longitude=116.366137&appv=5.4.32.20&ch=AppStore_5.4.32.20&did=%s&dn=%s&dt=%s&g_x=0.127480&g_y=-0.164997&g_z=-0.186533&geoacc=10&im=81D6A92E-AF0E-47FC-9BFD-0FAFE5E3EE97&la=cn&latitude=40.032434&lm=weixin&longitude=116.359918&lp=-1.000000&n_bssid=&n_dns=10.226.1.1&n_ssid=&net=0-0-wifi&osn=iOS&osv=13.2.3&seid=%s&sh=812.000000&sm_dt=%s&src=sneaker_search&sw=375.000000&token=KpmZBVLLoySWokS4iiXXPlvPb3gdnQ3q&tpid=goods_detail&ts=%s',
            self::$nice_did,
            self::$nice_smdt,
            self::$nice_seid,
            self::$nice_dn,
            self::$nice_dt,
            self::get_subtraction());


        $response = requests::post($url,$data,$headers);
        $response = json_decode($response,true);
        if($response['code'] == 0)
        {

            #file_put_contents('./list.log',print_r($response['data'],true));
            #当前价格
            self::$nice_current_status = $response['data']['tab_list'][0]['list'];
            #预售价格
            self::$nice_sale_status      = $response['data']['tab_list'][1]['list'];
            #求购价格
            self::$nice_want_buy_status  = $response['data']['tab_list'][2]['list'];

            self::display_ui();
        }
    }

    public static function display_ui()
    {
        $display_str = "-----------------------------PRICE STATUS BY NICEAPP---------------\n";
        $display_str .= sprintf("商品名称:%s\n发售日期:%s\n货号:%s\n当前时间:%s\n",
            "\033[36m".self::$nice_goods_detail['name']."\e[0m",
            self::$nice_goods_detail['release_time'],
            self::$nice_goods_detail['sku'],
            "\033[35m".date('Y-m-d H:i:s',time())."\e[0m");

        $display_str .=
            "尺码" . str_pad('',    16 - strlen('尺码')) .
            "买入价格" . str_pad('', 20 - strlen('买入价格')) .
            "当前价格" . str_pad('', 20 - strlen('当前价格')) .
            "预售价格" . str_pad('', 20 - strlen('预售价格')) .
            "盈亏" . str_pad('', 20 - strlen('盈亏')) .
            "\n";

        $display_str .= "-------------------------------------------------------------------\n";

        foreach (self::$nice_current_status as $key=>$val)
        {
            if($val['type'] == 'new' && count($val['icon']) == 0)
            {
                $display_str .= str_pad($val['size'], 14).
                    str_pad(self::$buy_price, 16).
                    str_pad($val['price'], 16).
                    str_pad(@self::$nice_sale_status[$key]['price'], 16).
                    str_pad(sprintf("\033[31m%s\e[0m",$val['price'] - 1199), 16). "\n";
            }
        }
        self::replace_echo($display_str,null);
    }

    public function replace_echo($message, $force_clear_lines = NULL)
    {
        static $last_lines = 0;

        if(!is_null($force_clear_lines))
        {
            $last_lines = $force_clear_lines;
        }

        // 获取终端宽度
        $toss = $status = null;
        $term_width = exec('tput cols', $toss, $status);
        if($status || empty($term_width))
        {
            $term_width = 64; // Arbitrary fall-back term width.
        }

        $line_count = 0;
        foreach(explode("\n", $message) as $line)
        {
            $line_count += count(str_split($line, $term_width));
        }

        // Erasure MAGIC: Clear as many lines as the last output had.
        for($i = 0; $i < $last_lines; $i++)
        {
            // Return to the beginning of the line
            echo "\r";
            // Erase to the end of the line
            echo "\033[K";
            // Move cursor Up a line
            echo "\033[1A";
            // Return to the beginning of the line
            echo "\r";
            // Erase to the end of the line
            echo "\033[K";
            // Return to the beginning of the line
            echo "\r";
            // Can be consolodated into
            // echo "\r\033[K\033[1A\r\033[K\r";
        }

        $last_lines = $line_count;

        echo $message."\n";
    }
}

price::start();