<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Play extends CI_Controller {
	function __construct(){
	    parent::__construct();
		//防盗链
		if(!is_referer()){
			show_404();exit;
		}
	    $this->load->database();
	}

	public function index($id=''){
		if(!is_numeric($id)){
			$id = safe_replace($id);
			if(empty($id)) exit('参数错误');
			$row = $this->db->query("select * from ".CS_SqlPrefix."video where vid='".$id."'")->row();
		}else{
			if($id<1) exit('参数错误');
			$row = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$id)->row();
		}
		if(!$row) exit('该视频不存在');
		//if($row->sid == 0) exit('视频处理中...');
		$data['title'] = $row->name;
		//获取视频地址，兼容其他平台转移过来的数据
		if(!empty($row->path) && substr($row->path,-5) == '.m3u8'){
			$parr = explode('/', $row->path);
			$len = count($parr)-1;
			unset($parr[$len]);
			$path = implode('/',array_filter($parr));
			$rowpath = $path.'/index.m3u8';
			$picpath = $path.'/1.jpg';
			$data['playlink'] = m3u8_link($rowpath);
			$data['piclink'] = m3u8_link($picpath,'jpg');
		}else{
			if(empty($row->pic)){
				$vodpath = m3u8_dir($row->id,$row->addtime,'m3u8',1);
				$picpath = m3u8_dir($row->id,$row->addtime,'jpg',1);
				$data['playlink'] = m3u8_link($vodpath);
				$data['piclink'] = m3u8_link($picpath,'jpg');
			}else{
				$data['playlink'] = m3u8_link($row->m3u8);
				$data['playlink2'] = m3u8_link($row->m3u8,'m3u8',Mu_Kbps2);
				if(Mu_Type == 0) $data['playlink2'] = '';
				$data['piclink'] = m3u8_link($row->pic,'jpg');
			}
		}
		$this->load->view('play.html',$data);
	}
}
