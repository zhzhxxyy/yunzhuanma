<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tongbu extends CI_Controller{
	function __construct(){
	    parent::__construct();
	    $this->load->database();
	}

	//同步
	public function index(){
		$id =intval($this->input->get_post('id'));
		if($id < 1) exit('参数错误');
		$exist = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$id)->row();
      
		if(!$exist) exit('视频不存在');
	    $da   = (array)$exist;	
        $da['duration'] =formattime($da['duration'],1);
        echo get_api('add',$da);die;
      //发送api入库请求
		$add['code'] = 0;
		$add['vid'] = $exist->id;
		$add['cid'] = $exist->cid;
		$add['name'] = $exist->name;
		$add['size'] = formatsize($exist->size);
		$add['duration'] = formattime($exist->duration,1);
		$add['gifurl'] = m3u8_link($exist->pic,'gif'); //$add['m3u8url'] = m3u8_link($exist->m3u8);
		$add['gifurl'] = m3u8_link($exist->pic,'gif');
		$add['jpgurl'] = m3u8_link($exist->pic,'jpg');
        $add['realpath'] = '/video/m3u8/'.date('Y');
		for($i=1;$i<Jpg_Num;$i++){
			$n = $i+1;
			$add['jpgurl_'.$n] = m3u8_link($picpath,'jpg',$n);
		}
		$add['playurl'] = 'http://'.Web_Url.links('play/'.$id);
		echo get_api('add',$add);
	}
}