<?php 






defined('BASEPATH') or exit( 'No direct script access allowed' );

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function index()
    {
        $data["backurl"] = "";
        $this->load->view("login.html", $data);
    }

    public function save()
    {
        $this->deldir();
        $adminname = $this->input->get_post("adminname", true);
        $adminpass = $this->input->get_post("adminpass", true);
        if( empty($adminname) || empty($adminpass) ) 
        {
            getjson("账号密码不能留空");
        }

        if( $adminname != Admin_Name || $adminpass != Admin_Code ) 
        {
            getjson("账号密码不正确");
        }

        set_cookie('user_name', $adminname, time() + 86400 * 30);
        set_cookie('user_login', md5($adminname . $adminpass . "zhw"), time() + 86400 * 30);
        $info["url"] = site_url("index");
        getjson($info, 0);
    }

    public function ext()
    {
        set_cookie("user_name", "", time() - 3600);
        set_cookie('user_login', time() - 3600);
        header('location:' . site_url('login'));
        exit();
    }

    public function deldir($dir = "", $sid = 0)
    {
        if( $dir == "" ) 
        {
            if( substr(Video_Path, 0, 2) == "./" ) 
            {
                $Video_Path = FCPATH . substr(Video_Path, 2);
            }
            else
            {
                $Video_Path = Video_Path;
            }

            $dir = $Video_Path . "/temp";
        }

        if( !is_dir($dir) ) 
        {
            return true;
        }

        $dh = opendir($dir);
        while( $file = readdir($dh) ) 
        {
            if( $file != "." && $file != ".." ) 
            {
                $fullpath = $dir . "/" . $file;
                if( !is_dir($fullpath) ) 
                {
                    unlink($fullpath);
                }
                else
                {
                    if( $file != date("Ymd") ) 
                    {
                        $this->deldir($fullpath, 1);
                    }

                }

            }

        }
        closedir($dh);
        if( $sid == 1 ) 
        {
            rmdir($dir);
        }

        return true;
    }

}



