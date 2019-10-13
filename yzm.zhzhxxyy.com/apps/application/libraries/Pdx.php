<?php






if( !defined('BASEPATH') ) 
{
    exit( 'No direct script access allowed' );
}


class Pays_app
{
    public function __construct()
    {
        if( CT_Pay == 0 ) 
        {
            msg_url("在线支付功能已关闭~!", "javascript:history.back();");
        }

        $this->api_url = "http://";
        if( Web_Mode == 2 ) 
        {
            $this->return_url = "http://" . Web_Url . Web_Path . "index.php?d=app&c=pay&m=return_url";
            $this->notify_url = "http://" . Web_Url . Web_Path . "index.php?d=app&c=pay&m=notify_url";
        }
        else
        {
            $this->return_url = "http://" . Web_Url . Web_Path . "index.php/app/pay/return_url";
            $this->notify_url = "http://" . Web_Url . Web_Path . "index.php/app/pay/notify_url";
        }

    }

    public function to($row = array(  ), $mid = 0)
    {
        $body = ($row["cid"] == 0 ? "会员在线购买金币" : "会员在线购买Vip");
        $sign_arr = array( "pay_sid" => $row["sid"] + 1, "out_trade_no" => $row["dingdan"], "total_fee" => (double) $row["rmb"], "partner" => CT_Pay_ID, "return_url" => $this->return_url, "notify_url" => $this->notify_url );
        $sign = $this->md5_sign($sign_arr, CT_Pay_Key);
        $parameter = $sign_arr;
        $parameter["charset"] = "utf-8";
        $parameter["body"] = urlencode($body);
        $parameter["sign"] = $sign;
        $parameter["mid"] = $mid;
        return $this->api_url . "?" . $this->arr_url($parameter);
    }

    public function md5_sign($arr, $skey)
    {
        $arr_filter = array(  );
        foreach( $arr as $key => $val ) 
        {
            if( $key == "sign" || $val == "" ) 
            {
                continue;
            }

            $arr_filter[$key] = $arr[$key];
        }
        ksort($arr_filter);
        reset($arr_filter);
        $arg = "";
        foreach( $arr_filter as $key => $val ) 
        {
            $arg .= $key . "=" . urlencode($val) . "&";
        }
        $arg = substr($arg, 0, count($arg) - 2);
        if( get_magic_quotes_gpc() ) 
        {
            $arg = stripslashes($arg);
        }

        $sign = strtoupper(md5($arg . $skey));
        return $sign;
    }

}



