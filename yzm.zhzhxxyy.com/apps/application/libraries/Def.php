<?php 




if( !defined('BASEPATH') ) 
{
    exit( 'No direct script access allowed' );
}


class Denglu
{
    public function __construct()
    {
        $this->redirect_uri = links("user", "open/callback");
        $this->ci =& get_instance();
    }

    public function callback($ac, $log_state = "")
    {
        $mode = $ac . "_callback";
        return $this->$mode($log_state);
    }

    public function qq_login($log_state = "")
    {
        if( Qq_Log == 0 ) 
        {
            exit( "QQ登录为关闭状态~" );
        }

        $scope = "get_user_info,add_share,list_album,add_album,upload_pic,add_topic,add_one_blog,add_weibo";
        $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=" . Qq_Appid . "&redirect_uri=" . $this->redirect_uri . "&state=" . $log_state . "&scope=" . $scope;
        header("Location:" . $login_url);
    }

    public function weixin_login($log_state = "")
    {
        if( Wx_Log == 0 ) 
        {
            exit( "微信登录为关闭状态~" );
        }

        $login_url = "https://open.weixin.qq.com/connect/qrconnect?appid=" . Wx_Appid . "&redirect_uri=" . urlencode($this->redirect_uri) . "&response_type=code&scope=snsapi_login&state=" . $log_state . "#wechat_redirect";
        header("Location:" . $login_url);
    }

    public function qq_callback($log_state = "")
    {
        $state = $this->ci->input->get_post("state", true, true);
        $code = $this->ci->input->get("code", true);
        if( empty($state) || empty($code) ) 
        {
            msg_url("登录失败，返回参数错误~!", links("user", "login"));
        }

        if( $state != $log_state ) 
        {
            msg_url("非法登录~!", links("user", "login"));
        }

        $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&" . "client_id=" . Qq_Appid . "&redirect_uri=" . urlencode($this->redirect_uri) . "&client_secret=" . Qq_Appkey . "&code=" . $code;
        $response = $this->get_url_contents($token_url);
        if( strpos($response, "callback") !== false ) 
        {
            msg_url("登入失败，没获取到access_token！", links("user", "login"));
        }

        $params = array(  );
        parse_str($response, $params);
        $data["access_token"] = $params["access_token"];
        $data["refresh_token"] = $params["refresh_token"];
        $data["expire_in"] = $params["expires_in"];
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . $data["access_token"];
        $str = $this->get_url_contents($graph_url);
        if( strpos($str, "callback") !== false ) 
        {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str = substr($str, $lpos + 1, $rpos - $lpos - 1);
        }

        $user = json_decode($str);
        if( isset($user->error) ) 
        {
            msg_url("获取openid失败！", links("user", "login"));
        }

        $qqid = $user->openid;
        $get_user_info = "https://graph.qq.com/user/get_user_info?" . "access_token=" . $data["access_token"] . "&oauth_consumer_key=" . CS_Qqid . "&openid=" . $qqid . "&format=json";
        $info = $this->get_url_contents($get_user_info);
        $arr = json_decode($info, true);
        $data["nichen"] = $arr["nickname"];
        $data["pic"] = $arr["figureurl_2"];
        $data["uid"] = $qqid;
        return $data;
    }

    public function weixin_callback($log_state = "")
    {
        $state = $this->ci->input->get_post("state", true, true);
        $code = $this->ci->input->get("code", true);
        if( empty($state) || empty($code) ) 
        {
            msg_url("登录失败，返回参数错误~!", links("user", "login"));
        }

        if( $state != $log_state ) 
        {
            msg_url("非法登录~!", links("user", "login"));
        }

        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . Wx_Appid . "&secret=" . Wx_Appkey . "&code=" . $code . "&grant_type=authorization_code";
        $json = $this->get_url_contents($url);
        $arr = json_decode($json, true);
        $token = $arr["access_token"];
        $openid = $arr["openid"];
        if( empty($openid) ) 
        {
            exit( "获取用户信息失败！" );
        }

        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $token . "&openid=" . $openid;
        $json = $this->get_url_contents($url);
        $arr = json_decode($json, true);
        $data["nichen"] = $arr["nickname"];
        $data["pic"] = $arr["headimgurl"];
        $data["uid"] = $openid;
        return $data;
    }

    public function get_url_contents($url, $post = "", $type = "get")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if( $type == "post" ) 
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        else
        {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, "cscms");
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}



