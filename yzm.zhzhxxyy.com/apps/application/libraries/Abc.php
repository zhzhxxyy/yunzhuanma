<?php

if( !defined('BASEPATH') ) 
{
    exit( 'No direct script access allowed' );
}


class Cookie
{
    public function __construct()
    {
        log_message('debug', 'Native Session Class Initialized');
    }

    public static function set_cookie($var, $value = "", $time = 0)
    {
        $time = (0 < $time ? $time : ($value == "" ? time() - 3600 : 0));
        $s = ($_SERVER["SERVER_PORT"] == "443" ? 1 : 0);
        $var = CT_Cookie_Prefix . $var;
        $ips = explode(":", $_SERVER["HTTP_HOST"]);
        $Domain = CT_Cookie_Domain;
        setcookie($var, sys_auth($value, "E", $var . CT_Encryption_Key), $time, Web_Path, $Domain, $s);
    }

    public static function get_cookie($var, $default = "")
    {
        $var = CT_Cookie_Prefix . $var;
        $value = (isset($_COOKIE[$var]) ? sys_auth($_COOKIE[$var], "D", $var . CT_Encryption_Key) : $default);
        $value = safe_replace($value);
        return $value;
    }

}



