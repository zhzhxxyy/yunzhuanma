<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends CI_Controller {
	function __construct(){
	    parent::__construct();
	    $this->load->database();
	    $this->load->model('User');
	    $this->User->Login();
	}

	//后台首页
	public function index(){
		$ip = '/' == DIRECTORY_SEPARATOR ? $_SERVER['SERVER_ADDR'] : gethostbyname($_SERVER['SERVER_NAME']);
		//$json = get_curl('http://api.m3u8.ctcms.cn/api?ip='.$ip);
		//$arr = json_decode($json,1);

		//if(empty($arr['msg']) || $arr['msg'] == 'no') 
			//exit('授权已到期，请联系QQ：157503886 购买续费，官网地址： http://m3u8.ctcms.cn/');
		//判断字段
		$table = CS_SqlPrefix.'video';
		if(!$this->db->field_exists('pic',$table)){
			$this->db->query("ALTER TABLE ".$table." ADD pic varchar(255) DEFAULT '' COMMENT '图片保存路径'");
			$this->db->query("ALTER TABLE ".$table." ADD m3u8 varchar(255) DEFAULT '' COMMENT '图片保存路径'");
		}
		$data['ctcms'] = $arr;
		$data['news_api'] = 'http://m3u8.ctcms.cn/api/news';
		$this->load->view('index.html',$data);
	}

	public function main(){
		$data['ver'] = file_get_contents(FCPATH.'libraries/ver.txt');
		$this->load->view('main.html',$data);
	}

	//在线更新
	public function update(){
		$ver = file_get_contents(FCPATH.'libraries/ver.txt');
		$ip = '/' == DIRECTORY_SEPARATOR ? $_SERVER['SERVER_ADDR'] : gethostbyname($_SERVER['SERVER_NAME']);
		$json = get_curl('http://m3u8.ctcms.cn/api/update?ip='.$ip.'&ver='.$ver.'&php='.PHP_VERSION);
		$arr = json_decode($json,1);
		if($ver == $arr['ver']){
			echo '当前已经是最新版本，无需更新~!';
		}else{
			if(empty($arr['list'])) exit('更新文件列表为空');
			foreach ($arr['list'] as $k => $filename) {
				$data = get_curl('http://m3u8.ctcms.cn/api/update/data?ip='.$ip.'&ver='.$ver.'&filename='.$filename.'&php='.PHP_VERSION);
				if(!empty($data) && strpos($data, '--ctyunend--') !== false){
					write_file(FCPATH.$filename,str_replace('--ctyunend--', '', $data));
				}
			}
			//修改当前版本
			write_file(FCPATH.'libraries/ver.txt',$arr['ver']);
			echo 'ok';
		}
	}
}
