<?php 


if( !defined('BASEPATH') ) 
{
    exit( 'No direct script access allowed' );
}


class User extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        if( !defined('IS_ADMIN') ) 
        {
            show_404();
            exit();
        }

        return NULL;
    }

    public function Login($cid = 0)
    {
        $user_name = get_cookie("user_name");
        $user_login = get_cookie("user_login");
        $login = false;
        if( !empty($user_name) && !empty($user_login) ) 
        {
            if( md5(Admin_Name . Admin_Code . "zhw") == $user_login && $user_name == Admin_Name ) 
            {
                $login = true;
            }

        }

        if( $cid == 0 && $login == false ) 
        {
            header("Location:" . site_url("login/index"));
            exit();
        }

        return $login;
    }

}



