<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting extends CI_Controller {
	function __construct(){
	    parent::__construct();
	    $this->load->model('User');
	    $this->User->Login();
	}
	public function index($sid=1){

		$str = file_get_contents(FCPATH.'packs/zimu.ass');
		$zimu = end(explode('[Events]', $str));
		$data['sid'] = $sid;
		$data['zimu'] = str_replace("\r\n","",str_replace('{\b0}{\fs}{\fn}','',$zimu));
		$this->load->view('setting.html',$data);
	}
	public function save(){

		$Web_Path = $this->input->get_post('Web_Path',true);
		$Web_Url = $this->input->get_post('Web_Url',true);
		$Web_Domain = $this->input->get_post('Web_Domain',true);
		$Web_Play = $this->input->get_post('Web_Play',true);
		$Web_On = (int)$this->input->get_post('Web_On',true);
		$Admin_Name = $this->input->get_post('Admin_Name',true);
		$Admin_Code = $this->input->get_post('Admin_Code',true);

		$Web_Url = str_replace('http://','',$Web_Url);
		$Web_Domain = str_replace('http://','',$Web_Domain);
		$Web_Play = str_replace('http://','',$Web_Play);
		if(empty($Web_Url) || empty($Web_Domain)){
			getjson('抱歉，域名不能留空');
		}
		if(empty($Admin_Name) || empty($Admin_Code)){
			getjson('抱歉，登录名称或密码不能留空');
		}

		$Jpg_On = intval($this->input->get_post('Jpg_On',true));
		$Jpg_Num = intval($this->input->get_post('Jpg_Num',true));
		$Jpg_Time = intval($this->input->get_post('Jpg_Time',true));
		$Jpg_Size = $this->input->get_post('Jpg_Size',true);
		if($Jpg_Num == 0) $Jpg_Num = 1;
		if($Jpg_Num > 20) getjson('抱歉，截图最多只能20张');

		$Gif_On = intval($this->input->get_post('Gif_On',true));
		$Gif_Time = intval($this->input->get_post('Gif_Time',true));
		$Gif_Len = intval($this->input->get_post('Gif_Len',true));
		$Gif_Size = $this->input->get_post('Gif_Size',true);
		if(empty($Web_Path)) $Web_Path = '/';

		if($Jpg_On==0){
			$jpgarr = explode('x', $Jpg_Size);
			if(!empty($Jpg_Size) && (sizeof($jpgarr)!=2 || intval($jpgarr[0])<1 || intval($jpgarr[1])<1)){
				getjson('截图尺寸格式不正确');
			}
		}
		if($Gif_On==0){
			$gifarr = explode('x', $Gif_Size);
			if(!empty($Gif_Size) && (sizeof($gifarr)!=2 || intval($gifarr[0])<1 || intval($gifarr[1])<1)){
				getjson('GIF动图尺寸格式不正确');
			}
			if($Gif_Len<1) getjson('请设置GIF动图时长');
			if($Gif_Len>10) $Gif_Len = 10;
		}

		$Mu_On = intval($this->input->get_post('Mu_On',true));
		$Mq_On = intval($this->input->get_post("Mq_On", true));
		$Mp_On = intval($this->input->get_post("Mp_On", true));
		$Mu_Time = intval($this->input->get_post('Mu_Time',true));
		$Mu_Type = intval($this->input->get_post('Mu_Type',true));
		$Mu_Size = $this->input->get_post('Mu_Size',true);
		$Mu_Path = $this->input->get_post('Mu_Path',true);
		$Mu_M3u8_Name = $this->input->get_post('Mu_M3u8_Name',true);
		$Mu_Pic_Name = $this->input->get_post('Mu_Pic_Name',true);
		$Mu_Kbps = $this->input->get_post('Mu_Kbps',true);
		$Mu_Key = $this->input->get_post('Mu_Key',true);
		$Mu_Dir = $this->input->get_post('Mu_Dir',true);
		$Mu_Ffpath = $this->input->get_post('Mu_Ffpath',true);
		$Mu_PtTime = intval($this->input->get_post('Mu_PtTime',true));
		$Mu_Tsext = $this->input->get_post('Mu_Tsext',true);
		$Mu_Preset = $this->input->get_post('Mu_Preset',true);
		$Mu_Size2 = $this->input->get_post('Mu_Size2',true);
		$Mu_Kbps2 = $this->input->get_post('Mu_Kbps2',true);

		if(empty($Mu_Ffpath)) getjson('ffmpeg安装路径不能留空');
		if(empty($Mu_Path)) getjson('转码保存路径不能留空');
		$muarr = explode('x', $Mu_Size);
		if(!empty($Mu_Size) && (sizeof($muarr)!=2 || intval($muarr[0])<1 || intval($muarr[1])<1)){
			getjson('转码尺寸一格式不正确');
		}
		$muarr = explode('x', $Mu_Size2);
		if(!empty($Mu_Size2) && (sizeof($muarr)!=2 || intval($muarr[0])<1 || intval($muarr[1])<1)){
			getjson('转码尺寸二格式不正确');
		}
		if(strpos($Mu_M3u8_Name, '[id]') === false && strpos($Mu_M3u8_Name, '[md5]') === false){
			getjson('M3U8后缀格式不正确');
		}
		if(empty($Mu_Pic_Name)){
			getjson('截图后缀不能为空');
		}

		//水印设置
		$Wm_On = intval($this->input->get_post('Wm_On',true));
		$Wm_Lt = $this->input->get_post('Wm_Lt',true);
		$Wm_On2 = intval($this->input->get_post('Wm_On2',true));
		$Wm_Lt2 = $this->input->get_post('Wm_Lt2',true);
		$Wm_On3 = intval($this->input->get_post('Wm_On3',true));
		$Wm_Lt3 = $this->input->get_post('Wm_Lt3',true);
		$Wm_On4 = intval($this->input->get_post('Wm_On4',true));
		$Wm_Lt4 = $this->input->get_post('Wm_Lt4',true);

		$Wm_Zm = intval($this->input->get_post('Wm_Zm',true));
		$Wm_ZmNei = $this->input->get_post('Wm_ZmNei');
		if($Wm_Zm==1){
			if(empty($Wm_ZmNei)) getjson('字幕内容不能为空');
		}
		if(substr($Wm_ZmNei,0,9) != 'Dialogue:'){
			getjson('字幕内容格式错误');
		}
		//修改字幕内容
		$str = file_get_contents(FCPATH.'packs/zimu.ass');
		$zmarr = explode('Dialogue:',$Wm_ZmNei);
		unset($zmarr[0]);
		$Wm_ZmNei = "Dialogue:".implode("\r\n{\\b0}{\\fs}{\\fn}\r\nDialogue:",$zmarr);
		$zimu = current(explode('[Events]', $str))."[Events]\r\n".$Wm_ZmNei;
		write_file(FCPATH.'packs/zimu.ass', $zimu);

		if($Wm_On==1){
			$wmarr = explode(':', $Wm_Lt3);
			if(sizeof($wmarr)<2) getjson('左上角水印间距格式不正确');
			$Wm_Lt = intval($wmarr[0]).':'.intval($wmarr[1]);
		}
		if($Wm_On2==1){
			$wmarr = explode(':', $Wm_Lt4);
			if(sizeof($wmarr)<2) getjson('右上角水印间距格式不正确');
			$Wm_Lt2 = intval($wmarr[0]).':'.intval($wmarr[1]);
		}
		if($Wm_On3==1){
			$wmarr = explode(':', $Wm_Lt);
			if(sizeof($wmarr)<2) getjson('左下角水印间距格式不正确');
			$Wm_Lt3 = intval($wmarr[0]).':'.intval($wmarr[1]);
		}
		if($Wm_On4==1){
			$wmarr = explode(':', $Wm_Lt2);
			if(sizeof($wmarr)<2) getjson('右下角水印间距格式不正确');
			$Wm_Lt4 = intval($wmarr[0]).':'.intval($wmarr[1]);
		}
		//保存路径
		$Video_Path = $this->input->get_post('Video_Path',true);
		$Video_SrcOn = intval($this->input->get_post('Video_SrcOn',true));
		$Video_M3u8On = intval($this->input->get_post('Video_M3u8On',true));
		if(empty($Video_Path)) getjson('上传文件路径不能为空');

	
		$Web_CrossDomain = $this->input->get_post('Web_CrossDomain',true);
		$Web_CrossOn = intval($this->input->get_post('Web_CrossOn',true));
		$Web_Cross = intval($this->input->get_post('Web_Cross',true));
		$Web_M3u8On = intval($this->input->get_post('Web_M3u8On',true));

	
		$Api_On = intval($this->input->get_post('Api_On',true));
		$Api_Url = $this->input->get_post('Api_Url',true);
		$Api_Key = $this->input->get_post('Api_Key',true);
		if($Api_On == 1){
			if(empty($Api_Url) || empty($Api_Key)) getjson('同步地址和同步秘钥不能为空');
			if(substr($Api_Url,0,7) != 'http://' && substr($Api_Url,0,8) != 'https://') getjson('同步地址格式不正确');
		}
		$Up_Api = intval($this->input->get_post('Up_Api',true));
		$Up_Miao = intval($this->input->get_post('Up_Miao',true));
		$Up_Ext = $this->input->get_post('Up_Ext',true);
		if(empty($Up_Ext)) getjson('支持的上传格式不能为空');

		//m3u8域名
		if(substr($Mu_Path,0,2) !== './' && $Web_M3u8On == 0 && empty($Web_Play)){
			getjson('m3u8播放域名不能留空');
		}

	
		$strs = "<?php"."\r\n";
        $strs .= "define('Web_Url','".$Web_Url."'); //站点域名  \r\n";
        $strs .= "define('Web_Domain','".$Web_Domain."'); //转码域名  \r\n";
        $strs .= "define('Web_Play','".$Web_Play."'); //m3u8域名  \r\n";
        $strs .= "define('Web_Path','".$Web_Path."'); //站点路径  \r\n";
        $strs .= "define('Web_On',".$Web_On."); //是否开启前台，0关闭1开启  \r\n";
        $strs .= "define('Admin_Name','".$Admin_Name."');  //后台用户名  \r\n";
        $strs .= "define('Admin_Code','".$Admin_Code."');  //后台验证码  \r\n";
        $strs .= "define('Jpg_On',".$Jpg_On.");  //截图开关，0打开1关闭  \r\n";
        $strs .= "define('Jpg_Num',".$Jpg_Num.");  //截图张数  \r\n";
        $strs .= "define('Jpg_Time',".$Jpg_Time.");  //间隔秒数  \r\n";
        $strs .= "define('Jpg_Size','".$Jpg_Size."');  //截图尺寸  \r\n";
        $strs .= "define('Gif_On',".$Gif_On.");  //动图开关，0打开1关闭  \r\n";
        $strs .= "define('Gif_Time',".$Gif_Time.");  //动图开始位置  \r\n";
        $strs .= "define('Gif_Len',".$Gif_Len.");  //动图时长  \r\n";
        $strs .= "define('Gif_Size','".$Gif_Size."');  //动图尺寸  \r\n";
        $strs .= "define('Mu_On',".$Mu_On.");  //加密开关，0加密1不加密  \r\n";
		$strs .= "define('Mq_On'," . $Mq_On . ");  //秒切开关，0秒切1不秒切  \r\n";
		$strs .= "define('Mp_On'," . $Mp_On . ");  //音频处理，0复制1不复制  \r\n";
        $strs .= "define('Mu_Time',".$Mu_Time.");  //每个TS的时长  \r\n";
        $strs .= "define('Mu_Type',".$Mu_Type.");  //多码率  \r\n";
        $strs .= "define('Mu_Kbps','".$Mu_Kbps."');  //转码码率  \r\n";
        $strs .= "define('Mu_Size','".$Mu_Size."');  //缩放尺寸  \r\n";
        $strs .= "define('Mu_Kbps2','".$Mu_Kbps2."');  //转码码率2  \r\n";
        $strs .= "define('Mu_Size2','".$Mu_Size2."');  //缩放尺寸2  \r\n";
        $strs .= "define('Mu_Path','".$Mu_Path."');  //保存路径  \r\n";
        $strs .= "define('Mu_Dir','".$Mu_Dir."');  //待转码目录  \r\n";
        $strs .= "define('Mu_Key','".$Mu_Key."');  //加密密钥  \r\n";
        $strs .= "define('Mu_Tsext','".$Mu_Tsext."');  //Ts伪装后缀  \r\n";
        $strs .= "define('Mu_PtTime',".$Mu_PtTime.");  //跳过片头秒数  \r\n";
        $strs .= "define('Mu_Ffpath','".$Mu_Ffpath."');  //ffmpeg安装路径\r\n";
        $strs .= "define('Mu_M3u8_Name','".$Mu_M3u8_Name."');  //转码保存路径格式\r\n";
        $strs .= "define('Mu_Pic_Name','".$Mu_Pic_Name."'); //截图保存路径格式\r\n";
        $strs .= "define('Mu_Preset','".$Mu_Preset."'); //转码优先方式\r\n";
        $strs .= "define('Wm_On',".$Wm_On.");  //左上水印开，0关闭1开启  \r\n";
        $strs .= "define('Wm_Lt','".$Wm_Lt."');  //左上距离边界位置  \r\n";
        $strs .= "define('Wm_On2',".$Wm_On2.");  //右上水印开，0关闭1开启  \r\n";
        $strs .= "define('Wm_Lt2','".$Wm_Lt2."');  //右上距离边界位置  \r\n";
        $strs .= "define('Wm_On3',".$Wm_On3.");  //左下水印开，0关闭1开启  \r\n";
        $strs .= "define('Wm_Lt3','".$Wm_Lt3."');  //左下距离边界位置  \r\n";
        $strs .= "define('Wm_On4',".$Wm_On4.");  //右下水印开，0关闭1开启  \r\n";
        $strs .= "define('Wm_Lt4','".$Wm_Lt4."');  //右下距离边界位置  \r\n";
        $strs .= "define('Wm_Zm',".$Wm_Zm.");  //是否启用字幕。0关闭，1启用  \r\n";
        $strs .= "define('Video_Path','".$Video_Path."');  //上传文件保存路径（绝对路径）  \r\n";
        $strs .= "define('Video_SrcOn',".$Video_SrcOn.");  //保留原文件0保留1不保留  \r\n";
        $strs .= "define('Video_M3u8On',".$Video_M3u8On.");  //删除保留M3u8文件0保留1不保留  \r\n";
        $strs .= "define('Web_CrossDomain','".$Web_CrossDomain."');  //防盗链域名分号隔开  \r\n";
        $strs .= "define('Web_CrossOn',".$Web_CrossOn.");  //防盗链开关  \r\n";
        $strs .= "define('Web_Cross',".$Web_Cross.");  //防盗链直接播放开关  \r\n";
        $strs .= "define('Web_M3u8On',".$Web_M3u8On.");  //m3u8地址防盗开关  \r\n";
        $strs .= "define('Api_On',".$Api_On.");  //api同步开关0关闭1开启  \r\n";
        $strs .= "define('Api_Url','".$Api_Url."');  //api同步地址  \r\n";
        $strs .= "define('Api_Key','".$Api_Key."');  //api同步秘钥  \r\n";
        $strs .= "define('Up_Miao',".$Up_Miao.");  //秒传开关0关闭1开启\r\n";
        $strs .= "define('Up_Api',".$Up_Api.");  //外部上传开关0关闭1开启\r\n";
        $strs .= "define('Up_Ext','".$Up_Ext."');  //支持的上传格式";
        //写文件
        if (!write_file(FCPATH.'libraries/config.php', $strs)){
            getjson('抱歉，无写入权限');
        }else{
        	$info['url'] = site_url('setting/index').'?v='.rand(1000,1999);
            $info['msg'] = '恭喜您，写入成功';
        	getjson($info,0);
        }
	}
}
