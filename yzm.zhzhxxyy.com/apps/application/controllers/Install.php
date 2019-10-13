<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Install extends CI_Controller {

	function __construct(){
	    parent::__construct();
		if(file_exists(FCPATH.'packs/install/install.lock')){
			exit('重新安装，请删除./packs/install/install.lock文件');
		}
		$ip = '/' == DIRECTORY_SEPARATOR ? $_SERVER['SERVER_ADDR'] : gethostbyname($_SERVER['SERVER_NAME']);
		//$json = get_curl('http://api.m3u8.ctcms.cn/api?ip='.$ip);
		//$arr = json_decode($json,1);
		//if(empty($arr['msg']) || $arr['msg'] == 'no') 
			//exit("授权已到期，请联系QQ：157503886 购买续费，官网地址： http://m3u8.ctcms.cn/");
	}

	public function index(){
		$this->load->view('install.html');
	}

	public function save(){
		$host = $this->input->post('host',true);
		$port = (int)$this->input->post('port',true);
		$name = $this->input->post('name',true);
		$prefix = $this->input->post('prefix',true);
		$user = $this->input->post('user',true);
		$pass = $this->input->post('pass',true);
		$admin_name = $this->input->post('admin_name',true);
		$admin_pass = $this->input->post('admin_pass',true);
		if($port == 0) $port = '';

		if(empty($host) || empty($name) || empty($user) || empty($pass) || empty($admin_name) || empty($admin_pass)){
			exit("<script>alert('数据不完整~!');history.go(-1);</script>");
		}

		//修改数据库配置
        $this->load->helper('string');
        $Encryption_Key = random_string('alnum',12);
		$driver = is_php('5.4') ? 'mysqli' : 'mysql';
	    $config=file_get_contents(FCPATH."libraries/database.php");
	    $config=preg_replace("/'CS_Sqlserver','(.*?)'/","'CS_Sqlserver','".$host."'",$config);
	    $config=preg_replace("/'CS_Sqlport','(.*?)'/","'CS_Sqlport','".$port."'",$config);
	    $config=preg_replace("/'CS_Sqlname','(.*?)'/","'CS_Sqlname','".$name."'",$config);
	    $config=preg_replace("/'CS_Sqluid','(.*?)'/","'CS_Sqluid','".$user."'",$config);
	    $config=preg_replace("/'CS_Sqlpwd','(.*?)'/","'CS_Sqlpwd','".$pass."'",$config);
	    $config=preg_replace("/'CS_Dbdriver','(.*?)'/","'CS_Dbdriver','".$driver."'",$config);
	    $config=preg_replace("/'CS_SqlPrefix','(.*?)'/","'CS_SqlPrefix','".$prefix."'",$config);
	    $config=preg_replace("/'CS_Encryption_Key','(.*?)'/","'CS_Encryption_Key','".$Encryption_Key."'",$config);
	    if(!write_file(FCPATH.'libraries/database.php', $config)){
			exit("<script>alert('./libraries/database.php文件没有修改权限');history.go(-1);</script>");
		}

		//修改后台账号密码
		$mukey = random_string('alnum',16);
		$web_path = str_replace("\\","/",str_replace(getcwd(),'',FCPATH));
	    $config2 = file_get_contents(FCPATH."libraries/config.php");
	    $config2 = preg_replace("/'Mu_Key','(.*?)'/","'Mu_Key','".$mukey."'",$config2);
	    $config2 = preg_replace("/'Admin_Name','(.*?)'/","'Admin_Name','".$admin_name."'",$config2);
	    $config2 = preg_replace("/'Admin_Code','(.*?)'/","'Admin_Code','".$admin_pass."'",$config2);
	    $config2 = preg_replace("/'Web_Path','(.*?)'/","'Web_Path','".$web_path."'",$config2);
	    if(!write_file(FCPATH.'libraries/config.php', $config2)){
			exit("<script>alert('./libraries/config.php文件没有修改权限');history.go(-1);</script>");
		}

		//数据表
		$sql="CREATE TABLE IF NOT EXISTS `".$prefix."queue` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `vid` int(11) DEFAULT '0' COMMENT '视频ID',
		  `xu` tinyint(1) DEFAULT '1' COMMENT '转码码率序号',
		  `path` varchar(255) DEFAULT '' COMMENT '源文件路径',
		  `jpg` varchar(255) DEFAULT '' COMMENT 'JPG图片保存路径',
		  `gif` varchar(255) DEFAULT '' COMMENT 'GIF图片保存路径',
		  `m3u8` varchar(255) DEFAULT '' COMMENT 'm3u8文件保存路径',
		  `duration` varchar(64) DEFAULT '' COMMENT '视频时长',
		  `sid` tinyint(1) DEFAULT '0' COMMENT '0待转码1转码中',
		  `addtime` int(10) DEFAULT '0' COMMENT '转码开始时间',
		  PRIMARY KEY (`id`),
		  KEY `vid` (`vid`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='转码队列表';
		#ctcms#
		CREATE TABLE IF NOT EXISTS `".$prefix."class` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) DEFAULT '' COMMENT '分类名称',
		  `xid` tinyint(4) DEFAULT '10' COMMENT '排序越小越前',
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='分类列表';
		#ctcms#
		CREATE TABLE IF NOT EXISTS `".$prefix."video` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `cid` int(11) DEFAULT '0',
		  `name` varchar(255) DEFAULT '' COMMENT '视频名称',
		  `pic` varchar(255) DEFAULT '' COMMENT '图片保存路径',
		  `path` varchar(255) DEFAULT '' COMMENT '原文件保存路径',
		  `m3u8` varchar(255) DEFAULT '' COMMENT 'm3u8文件保存路径',
		  `md5` varchar(40) DEFAULT '' COMMENT '文件md5',
		  `vid` varchar(64) DEFAULT '' COMMENT '自定义ID',
		  `sid` tinyint(3) DEFAULT '0' COMMENT '转码状态，0待转码，1完成，2失败，3异常',
		  `duration` varchar(64) DEFAULT '' COMMENT '时长',
		  `size` bigint(20) DEFAULT '0' COMMENT '大小',
		  `addtime` int(10) DEFAULT '0' COMMENT '上传时间',
		  PRIMARY KEY (`id`),
		  KEY `vid` (`vid`),
		  KEY `md5` (`md5`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='上传视频记录表'";
		if(is_php('5.4')){
            $mysqli = new mysqli($host,$user,$pass);
            //检查连接是否成功
            if (mysqli_connect_errno()){
				exit("<script>alert('数据库链接失败~!');history.go(-1);</script>");
            }
			if(!$mysqli->select_db($name)){
				if(!$mysqli->query("CREATE DATABASE `".$name."`")){
					exit("<script>alert('数据库表《".$name."》不存在，请手动创建~!');history.go(-1);</script>");
				}
				$mysqli->select_db($name);
			}
			$mysqli->query("SET NAMES utf8");
            //导入数据表
	        $sqlarr = explode("#ctcms#",$sql);
	        for($i=0;$i<count($sqlarr);$i++){
				  if(!empty($sqlarr[$i])){
		               $mysqli->query($sqlarr[$i]);
				  }
	        }
		}else{
			$lnk=mysql_connect($host,$user,$pass);
			if(!$lnk) exit("<script>alert('数据库链接失败~!');history.go(-1);</script>");
			if(!mysql_select_db($name,$lnk)){
				if(!mysql_query("CREATE DATABASE `".$name."`")){
				    exit("<script>alert('数据库表《".$name."》不存在，请手动创建~!');history.go(-1);</script>");
				}
				mysql_select_db($name,$lnk);
			}
	        mysql_query("SET NAMES utf8", $lnk);
            //导入数据表
	        $sqlarr = explode("#ctcms#",$sql);
	        for($i=0;$i<count($sqlarr);$i++){
				  if(!empty($sqlarr[$i])){
		               mysql_query($sqlarr[$i]);
				  }
	        }
		}
	    if(!write_file(FCPATH.'packs/install/install.lock', 'ctcms')){
			exit("<script>alert('./packs/install/目录没有写入权限');history.go(-1);</script>");
		}
		exit("<script>alert('恭喜您，安装完成');window.location.href='".$web_path."admin.php';</script>");
	}
}