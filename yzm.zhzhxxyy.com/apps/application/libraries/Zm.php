<?php







if( !defined('BASEPATH') ) 
{
    exit( 'No direct script access allowed' );
}


class Pays_zfb
{
    public function __construct()
    {
        $this->partner = CT_Pay_ID;
        $this->SKey = CT_Pay_Key;
        $this->return_url = "http://" . Web_Url . links("pay", "return_url/alipay");
        $this->notify_url = "http://" . Web_Url . links("pay", "notify_url/alipay");
    }

    public function get_link($dingdan, $body, $total_fee)
    {
        $params = array( "service" => "create_direct_pay_by_user", "partner" => $this->partner, "seller_id" => $this->partner, "payment_type" => 1, "notify_url" => $this->notify_url . "/md5", "return_url" => $this->return_url . "/md5", "out_trade_no" => $dingdan, "subject" => $body, "total_fee" => $total_fee, "body" => $body, "_input_charset" => "utf-8" );
        $params["sign"] = $this->md5_sign($params);
        $params["sign_type"] = "MD5";
        $url = "https://mapi.alipay.com/gateway.do?";
        foreach( $params as $k => $v ) 
        {
            $url .= $k . "=" . urlencode($v) . "&";
        }
        $url = substr($url, 0, -1);
        header('location:' . $url);
        exit();
    }

    public function md5_sign($para)
    {
        $para_filter = array(  );
        foreach( $para as $key => $val ) 
        {
            if( $key == "sign" || $key == "sign_type" || $val == "" ) 
            {
                continue;
            }

            $para_filter[$key] = $para[$key];
        }
        ksort($para_filter);
        reset($para_filter);
        $prestr = $this->createLinkstring($para_filter) . $this->SKey;
        return md5($prestr);
    }

    public function is_sign()
    {
        $para = (isset($_POST["sign"]) ? $_POST : $_GET);
        $sign = $para["sign"];
        $mysgin = $this->md5_sign($para);
        if( $mysgin == $sign ) 
        {
            if( $para["trade_status"] == "TRADE_SUCCESS" ) 
            {
                return $para["out_trade_no"];
            }

        }

        return false;
    }

    public function getsignstr($params, $n = 0)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach( $params as $k => $v ) 
        {
            if( !empty($v) && "@" != substr($v, 0, 1) ) 
            {
                $v = $this->characet($v);
                if( $i == 0 ) 
                {
                    $stringToBeSigned .= (string) $k . "=" . (string) $v;
                }
                else
                {
                    $stringToBeSigned .= "&" . (string) $k . "=" . (string) $v;
                }

                $i++;
            }

        }
        unset($k);
        unset($v);
        return $stringToBeSigned;
    }

    public function characet($data, $targetCharset = "utf-8")
    {
        if( !empty($data) ) 
        {
            $fileType = "utf-8";
            if( strcasecmp($fileType, $targetCharset) != 0 ) 
            {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }

        }

        return $data;
    }

    public function createLinkstring($para)
    {
        $arg = "";
        while( list($key, $val) = each($para) ) 
        {
            $arg .= $key . "=" . $val . "&";
        }
        $arg = substr($arg, 0, count($arg) - 2);
        if( get_magic_quotes_gpc() ) 
        {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

}



