<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M3u8 extends CI_Controller {

	function __construct(){
	    parent::__construct();
		//防盗链
		if(!is_referer(1)){
			Header("HTTP/1.1 404 Not Found");
			exit('The path is not correct');
		}
	}

	//m3u8列表
	public function index($uri=''){
		$m3u8path = sys_auth(str_replace('.m3u8', '', $uri),1);
		if(empty($m3u8path)){
			Header("HTTP/1.1 404 Not Found");
			exit('File address error');
		}
		$Mu_Path = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
		$m3u8path = $m3u8path;
		if(!file_exists($Mu_Path.$m3u8path)){
			Header("HTTP/1.1 404 Not Found");
			exit('404');
		}
		//获取实际路径
		$varr = explode('/', $m3u8path);
		array_pop($varr);
		$vpath = implode('/',$varr).'/';
		$data = file_get_contents($Mu_Path.$m3u8path);
		$marr = explode("\n", $data);
		for ($i=0; $i < count($marr); $i++) { 
			if(substr($marr[$i],-3) == '.ts'){
				$yts = str_replace("\r", "", $marr[$i]);
				if(Mu_Tsext == ''){
					$xts = 'ts/'.sys_auth($vpath.$yts).'.ts';
				}else{
					$xts = 'ts/'.sys_auth($vpath.$yts).'.'.Mu_Tsext;
				}
				$data = str_replace($yts, $xts, $data);
			}
		}
		$xkey = sys_auth($vpath.'key.key');
		$data = str_replace('URI="key.key"', 'URI="key/'.$xkey.'.key"', $data);
		header('Content-type: application/vnd.apple.mpegURL');
		header('Content-disposition: attachment; filename='.$uri);
		echo $data;
	}

	//秘钥
	public function key($uri=''){
		$keypath = sys_auth(str_replace('.key', '', $uri),1);
		if(empty($keypath)){
			Header("HTTP/1.1 404 Not Found");
			exit('File address error');
		}
		$Mu_Path = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
		$key_path = $Mu_Path.$keypath;
		if(!file_exists($key_path)){
			Header("HTTP/1.1 404 Not Found");
			exit('404');
		}
		$data = file_get_contents($key_path);
		echo $data;
	}

	//ts分片
	public function ts($uri=''){
		if(Mu_Tsext == ''){
			$tspath = sys_auth(str_replace('.ts', '', $uri),1);
		}else{
			$tspath = sys_auth(str_replace('.'.Mu_Tsext, '', $uri),1);
		}
		if(empty($tspath)){
			Header("HTTP/1.1 404 Not Found");
			exit('File address error');
		}
		$Mu_Path = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
		$ts_path = $Mu_Path.$tspath;
		if(!file_exists($ts_path)){
			Header("HTTP/1.1 404 Not Found");
			exit('404');
		}
		$filesize = sprintf("%u", filesize($ts_path));
		header('Content-type: video/mp2t');
		header('Content-length: '.$filesize);
		header('Content-disposition: attachment; filename='.$uri);
		$data = file_get_contents($ts_path);
		echo $data;
	}

	//图片
	public function pic($uri=''){
		$picpath = sys_auth(str_replace(array('.jpg','.gif'), '', $uri),1);
		if(empty($picpath)){
			Header("HTTP/1.1 404 Not Found");
			exit('File address error');
		}
		$Mu_Path = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
		$pic_path = $Mu_Path.$picpath;
		if(!file_exists($pic_path)){
			Header("HTTP/1.1 404 Not Found");
			exit('404');
		}
		$image = file_get_contents($pic_path);
		if(strpos($uri, '.jpg')){
			header('Content-type: image/jpg');
		}else{
			header('Content-type: image/gif');
		}
		echo $image;
	}
}