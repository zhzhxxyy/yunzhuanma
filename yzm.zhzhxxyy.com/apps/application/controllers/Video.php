<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Video extends CI_Controller {
	function __construct(){
	    parent::__construct();
	    $this->load->database();
	}

	//视频列表
	public function index(){
	    $this->load->model('User');
	    $this->User->Login();
		$page = intval($this->input->get_post('page',true));
		$page = $page<1?1:$page;
		$per_page = 16;
		$where = array();
		$key = $this->input->get_post('key',true);
		$sid = intval($this->input->get_post('sid'));
		$cid = intval($this->input->get_post('cid'));
		$kstime = $this->input->get_post('kstime',true);
		$jstime = $this->input->get_post('jstime',true);
		if(!empty($key)) $where['name'] = " like '%".$key."%'";
		if($sid>0) $where['sid='] = $sid-1;
		if($cid>0) $where['cid='] = $cid;
		if(!empty($kstime)) $where['addtime>'] = strtotime($kstime)-1;
		if(!empty($jstime)) $where['addtime<'] = strtotime($jstime)+86400;
		$where_str = '';
		foreach ($where as $key1 => $value) {
			if(empty($where_str)){
				$where_str .= 'where '.$key1.$value;
			}else{
				$where_str .= ' and '.$key1.$value;
			}
		}
		
		$sel_sql = "select * from ".CS_SqlPrefix."video ".$where_str." order by id desc";
		$tol_sql = "select count(*) as count from ".CS_SqlPrefix."video ".$where_str;
		$total_res = $this->db->query($tol_sql)->row();
		$total = $total_res->count;
		$totalPages = ceil($total / $per_page)?ceil($total / $per_page):1; // 总页数
        $page = ($page>$totalPages)?$totalPages:$page;
        $data['nums'] = $total;
        if($total<$per_page){
            $per_page = $total;
        }
        $sel_sql .= ' limit '. $per_page*($page-1) .','. $per_page;
        $data['video'] = $this->db->query($sel_sql)->result();
        $data['vlist'] = $this->db->query("select * from ".CS_SqlPrefix."class order by id asc")->result();
        $base_url = site_url('video')."?key=".$key."&sid=".$sid."&cid=".$cid."&kstime=".$kstime."&jstime=".$jstime."&page=";
        $data['page_data'] = page_data($total,$page,$totalPages); //获取分页类
        $data['page_list'] = admin_page($base_url,$page,$totalPages); //获取分页类
        $data['page'] = $page;
        $data['sid'] = $sid;
        $data['cid'] = $cid;
        $data['key'] = $key;
        $data['kstime'] = $kstime;
        $data['jstime'] = $jstime;
		$this->load->view('video.html',$data);
	}

	//视频详细
	public function show($id){
	    $this->load->model('User');
	    $this->User->Login();
		$id = intval($id);
		if($id<1) exit('参数不正确');
		$vod = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$id)->row();
		$data['vod'] = $vod;

		//兼容ppvod平台转移过来的数据
		if(!empty($vod->path) && substr($vod->path,-5) == '.m3u8'){
			$parr = explode('/', $vod->path);
			$len = count($parr)-1;
			unset($parr[$len]);
			$path = implode('/',array_filter($parr));
			$vodpath = $path.'/index.m3u8';
			$picpath = $path.'/1.jpg';
			$gifpath = $path.'/1.gif';
			$data['m3u8url'] = m3u8_link($vodpath);
			$data['gifurl'] = m3u8_link($gifpath,'gif');
			$picurl =  m3u8_link($picpath,'jpg');
		}else{
			if(empty($vod->pic)){
				$vodpath = m3u8_dir($vod->id,$vod->addtime,'m3u8',1);
				$picpath = m3u8_dir($vod->id,$vod->addtime,'jpg',1);
				$gifpath = m3u8_dir($vod->id,$vod->addtime,'gif',1);
				$data['m3u8url'] = m3u8_link($vodpath);
				$data['gifurl'] = m3u8_link($gifpath,'gif');
				$picurl =  m3u8_link($picpath,'jpg');
				$data['pic']['jpgurl'] = m3u8_link($picpath,'jpg');
				for($i=1;$i<Jpg_Num;$i++){
					$data['pic']['jpgurl_'.$i] = m3u8_link($picpath,'jpg',($i+1));
				}
			}else{
				$data['m3u8url'] = m3u8_link($vod->m3u8);
				if(Mu_Type == 1){
					$data['m3u8url2'] = m3u8_link($vod->m3u8,'m3u8',Mu_Kbps2);
				}
				$data['gifurl'] = m3u8_link($vod->pic,'gif');
				$data['pic']['jpgurl'] = m3u8_link($vod->pic,'jpg');
				for($i=1;$i<Jpg_Num;$i++){
					$data['pic']['jpgurl_'.$i] = m3u8_link($vod->pic,'jpg',($i+1));
				}
			}
		}
		$data['playurl'] = 'http://'.Web_Url.links('play/'.$id);
		$this->load->view('show.html',$data);
	}

	//删除视频
	public function del(){
	    $this->load->model('User');
	    $this->User->Login();
		$ids = $this->input->get_post('id');
		if(is_array($ids)){
			if(sizeof($ids)<1) getjson('请选择要删除的文件');
			foreach ($ids as $key => $value) {
				$id = intval($value);
				if($id<1) continue;
				$row = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$id)->row();
				if(!$row) continue;
				$this->db->delete('video',array('id'=>$id));
				//删除队列
				$this->db->delete('queue',array('vid'=>$id));
				//删除源文件
				unlink($row->path);
				//删除M3U8文件
				if(Video_M3u8On == 1){
					$m3u8path = empty($row->m3u8) ? m3u8_dir($row->id,$row->addtime,'m3u8',1) : $row->m3u8;
					$m3u8path = dirname($m3u8path);
					if(substr(Mu_Path,0,2) == './'){
						deldir(FCPATH.substr(Mu_Path,2).$m3u8path);
					}else{
						deldir(Mu_Path.$m3u8path);
					}
					$picpath = empty($row->pic) ? m3u8_dir($row->id,$row->addtime,'jpg',1) : $row->pic;
					$picpath = dirname($picpath);
					if(substr(Mu_Path,0,2) == './'){
						deldir(FCPATH.substr(Mu_Path,2).$picpath);
					}else{
						deldir(Mu_Path.$picpath);
					}
				}
			}
		}else{
			$id = intval($ids);
			$row = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$id)->row();
			if($row){
				$this->db->delete('video',array('id'=>$id));
				//删除队列
				$this->db->delete('queue',array('vid'=>$id));
				//删除源文件
				unlink($row->path);
				//删除M3U8文件
				if(Video_M3u8On == 1){
					$m3u8path = empty($row->m3u8) ? m3u8_dir($row->id,$row->addtime,'m3u8',1) : $row->m3u8;
					$m3u8path = dirname($m3u8path);
					if(substr(Mu_Path,0,2) == './'){
						deldir(FCPATH.substr(Mu_Path,2).$m3u8path);
					}else{
						deldir(Mu_Path.$m3u8path);
					}
					$picpath = empty($row->pic) ? m3u8_dir($row->id,$row->addtime,'jpg',1) : $row->pic;
					$picpath = dirname($picpath);
					if(substr(Mu_Path,0,2) == './'){
						deldir(FCPATH.substr(Mu_Path,2).$picpath);
					}else{
						deldir(Mu_Path.$picpath);
					}
				}
			}
		}
		$info['url'] = site_url('video')."?v=".rand(0,999);
		getjson($info,0);
	}

	//查看转码状态
	/*
	*type : 0转换百分比，1转换成功下一个，2转换失败下一个
	*id : 当前id
	*nxid : 下一个ID，0表示没有下一个
	*pro: 百分比
	*/
	public function getpro(){
		//M3U8保存目录
		$m3u8_dir = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
		$v = $this->input->get('v');
		$row = $this->db->query("select * from ".CS_SqlPrefix."queue order by id asc limit 1")->row();
		if(!$row){
			//进入待转码目录
			if($v == 'zm' && Mu_Dir != '' && is_dir(Mu_Dir)){
				if(substr(Video_Path,0,2) == './'){
					$Video_Path = FCPATH.substr(Video_Path,2);
				}else{
					$Video_Path = Video_Path;
				}
				$this->load->helper('file');
				$dirinfo = dir_file(Mu_Dir);
				if(!empty($dirinfo['file'])){
					$filename = $dirinfo['file'];
					$Mu_Dir = $dirinfo['dir'];
					if(substr($Mu_Dir,-1) != '') $Mu_Dir.='/';
					//定义允许上传的文件扩展名
					$ext_arr = array_filter(explode('|', Up_Ext));
					//判断文件后缀
					$file_ext = strtolower(trim(substr(strrchr($filename, '.'), 1)));
					if(in_array($file_ext, $ext_arr) == false) {
						unlink($Mu_Dir.$filename);
						exit('不支持的格式');
					}
					$video_name = safe_replace(str_replace('.'.$file_ext, '', iconv('gbk', 'utf-8',$filename)));
		        	$uploadStr = date('Y').'/'.date('m').'/'.date('d').'/';
		        	creatdir($Video_Path.$uploadStr); //创建目录
		        	$newname = date('YmdHis').rand(1111,9999).'.'.$file_ext;
					//video
					$data['name'] = $video_name;
					$data['path'] = $uploadStr.$newname;
					$data['addtime'] = time();
		        	//移动目录
		        	$res = rename($Mu_Dir.$filename,$Video_Path.$uploadStr.$newname);
		        	if($res){
		        		$this->load->library('xyz');
						$format = $this->xyz->format($Video_Path.$uploadStr.$newname);
						if(empty($format['size']) || empty($format['duration'])){
							rename($Video_Path.$uploadStr.$newname,$Mu_Dir.$filename);
							exit('ffmpeg error'); 
						}
						$data['duration'] = $format['duration'];
						$data['size'] = $format['size'];
						$data['md5'] = md5_file($Video_Path.$uploadStr.$newname);
						//判断歌曲是否存在
						$res = $this->db->query("select id from ".CS_SqlPrefix."video where name='".$video_name."'")->row();
						if(!$res){
							//判断分类是否存在
							$carr = explode('/',str_replace(Mu_Dir,'',$Mu_Dir));
							$cname = empty($carr[0]) ? $carr[1] : $carr[0];
							$cname = safe_replace(iconv('gbk','utf-8',$cname));
							if(!empty($cname)){
								$rec = $this->db->query("select id from ".CS_SqlPrefix."class where name='".$cname."'")->row();
								if($rec){
									$data['cid'] = $rec->id;
								}else{
									$arr2['name'] = $cname;
									$arr2['xid'] = 10;
									$this->db->insert("class",$arr2);
									$data['cid'] = $this->db->insert_id();
								}
							}
							//视频入库
							$this->db->insert("video",$data);
							$insert_id = $this->db->insert_id();
							//路径
							$time = time();
							$mpath = m3u8_dir($insert_id,$time);
							$ppath = m3u8_dir($insert_id,$time,'jpg');
							$gpath = m3u8_dir($insert_id,$time,'gif');
							$this->db->update('video',array('m3u8'=>$mpath,'pic'=>$ppath),array('id'=>$insert_id));
							//queue
							$queue['xu'] = 1;
							$queue['vid'] = $insert_id;
							$queue['jpg'] = $m3u8_dir.$ppath;
							$queue['gif'] = $m3u8_dir.$gpath;
							$queue['m3u8'] = $m3u8_dir.$mpath;
					        $queue['path'] = $Video_Path.$uploadStr.$newname;
					        $queue['duration'] = $format['duration'];
				        	$this->db->insert('queue',$queue);
				        	//多码率
				        	if(Mu_Type == 1){
				        		$ext = end(explode('/', $mpath));
				        		$queue['xu'] = 2;
								$queue['m3u8'] = $m3u8_dir.str_replace($ext, Mu_Kbps2.'/'.$ext, $mpath);
				        		$this->db->insert('queue',$queue);
				        	}
				        }
		        	}
				}
			}
			getjson('视频全部转码完毕');
		}
		//默认返回数据
		$info['type'] = 0;
		$info['name'] = $row->name;
		$info['id'] = $row->id;
		$info['xu'] = $row->xu;
		$info['vid'] = $row->vid;
		$info['pro'] = 0;
		//判断是视频是否存在
		$vod = $this->db->query("select id,name,path,size,addtime from ".CS_SqlPrefix."video where id=".$row->vid)->row();
		//异常队列,视频不存在队列
		if(!$vod){
			$this->db->delete('queue',array('id'=>$row->id));//删除队列
			getjson($info,0);
		}
		$row->name = $vod->name;
		//判断转码是否完毕
		$m3u8_path = $row->m3u8;
		if(!file_exists($m3u8_path)){
			//失败了，进程还在，需要杀掉进程继续下一个转码
			if($row->addtime > 0){
				if($row->addtime+120 < time()){
					//杀掉进程
					$xt = php_uname();
					if(strpos($xt, 'Windows') !== false){
						exec('taskkill /im ffmpeg.exe /f');
					}else{
						exec('ps aux | grep "ffmpeg" | cut -c 9-15 | xargs kill -9');
					}
					if(Mu_Type == 0 || $row->xu == 2){
						$this->db->update('video',array('sid'=>2),array('id'=>$row->vid));
					}
					$this->db->delete('queue',array('id'=>$row->id));//删除队列
					$info['type'] = 2;
					$nxrow = $this->db->query("select id,vid from ".CS_SqlPrefix."queue order by id asc limit 1")->row();
					if($nxrow){
						$nxvod = $this->db->query("select name from ".CS_SqlPrefix."video where id=".$nxrow->vid)->row();
						$info['nxid'] = $nxrow->id;
						$info['nxvid'] = $nxrow->vid;
						$info['name'] = $nxvod->name;
					}else{
						$info['nxid'] = 0;
						$info['nxvid'] = 0;
						$info['name'] = '';
					}
					//发送失败API消息
					if(Mu_Type == 0 || $row->xu == 2) get_api('api',array('vid'=>$vod->id,'name'=>$vod->name,'code'=>2));
				}
			}else{
				$info['type'] = 1;
				$info['id'] = 0;
				$info['vid'] = 0;
				$info['nxid'] = $row->id;
				$info['nxvid'] = $row->vid;
			}	
			getjson($info,0);
		}
		$m3u8_neir = file_get_contents($m3u8_path);
		if(strpos($m3u8_neir,'#EXT-X-ENDLIST')!==false){//转换完毕
			$this->db->update('video',array('sid'=>1),array('id'=>$row->vid));
			$this->db->delete('queue',array('id'=>$row->id));//删除队列
			$nxrow = $this->db->query("select id,vid from ".CS_SqlPrefix."queue order by id asc limit 1")->row();
			if($nxrow){
				$nxvod = $this->db->query("select name from ".CS_SqlPrefix."video where id=".$nxrow->vid)->row();
				$info['nxid'] = $nxrow->id;
				$info['nxvid'] = $nxrow->vid;
				$info['name'] = $nxvod->name;
			}else{
				$info['nxid'] = 0;
				$info['nxvid'] = 0;
				$info['name'] = '';
			}
			$info['type'] = 1;
			//删除源文件
			if(Video_SrcOn == 1){
				if(Mu_Type == 0 || $row->xu == 2){
					unlink($row->path);
				}
			}
			//发送转码完成API消息
			get_api('api',array('vid'=>$vod->id,'name'=>$vod->name,'code'=>1));
		}else{//转换中
			$xt = php_uname();
			if(strpos($xt, 'Windows') !== false){
				$jc = shell_exec('tasklist');
			}else{
				$jc = shell_exec('ps aux | less');
			}
			//这种情况是转码中，进程被结束了
			if(strpos($jc, 'ffmpeg') === false && $row->addtime > 0){
				if(strpos($m3u8_neir,'.ts') !== false){ //转码没完成，追加结束符
					$m3u8_neir .= "\r\n#EXT-X-ENDLIST";
					write_file($m3u8_path,$m3u8_neir);
					$type = 1;
				}else{
					$type = 2;
				}
				//改变状态
				$this->db->update('video',array('sid'=>$type),array('id'=>$row->vid));
				$this->db->delete('queue',array('id'=>$row->id));//删除队列
				$info['type'] = $type;
				$nxrow = $this->db->query("select id,vid from ".CS_SqlPrefix."queue order by id asc limit 1")->row();
				if($nxrow){
					$nxvod = $this->db->query("select name from ".CS_SqlPrefix."video where id=".$nxrow->vid)->row();
					$info['nxid'] = $nxrow->id;
					$info['nxvid'] = $nxrow->vid;
					$info['name'] = $nxvod->name;
				}else{
					$info['nxid'] = 0;
					$info['nxvid'] = 0;
					$info['name'] = '';
				}
				getjson($info,0);
			}
			$m3u8arr = explode("\n", $m3u8_neir);
			$oknum = intval($m3u8arr[count($m3u8arr)-2])+1;
			$allnum = ceil($row->duration/(Mu_Time+3));
			if($allnum<$oknum){
				$pro = 99.99;
			}else{
				$pro = round($oknum/$allnum*100,2);
			}
			$info['pro'] = $pro;
		}
       
		getjson($info,0);
	}

	//执行转码
	public function transcode(){
		$id = intval($this->input->get_post('id'));
		if($id < 1) exit('参数错误');
		$xt = php_uname();
		if(strpos($xt, 'Windows') !== false){
			$jc = shell_exec('tasklist');
		}else{
			$jc = shell_exec('ps aux | less');
		}
		if(strpos($jc, 'ffmpeg') !== false){
			exit('转码中...');
		}
		$exist = $this->db->query("select * from ".CS_SqlPrefix."queue where id=".$id)->row();
		if(!$exist) exit('no exist!');
        if(!file_exists($exist->path)){
			$this->db->update('video',array('sid'=>2),array('id'=>$exist->vid));
			$this->db->delete('queue',array('id'=>$id));//删除队列
			exit('视频源文件不存在');
		}
		//获取视频信息
		$vod = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$exist->vid)->row();
		if(!$vod){
			$this->db->delete('queue',array('id'=>$id));//删除队列
			exit('视频不存在');	
		}
		//发送api入库请求
		$add['code'] = 0;
		$add['vid'] = $vod->id;
		$add['cid'] = $vod->cid;
		$add['name'] = $vod->name;
		$add['size'] = formatsize($vod->size);
		$add['duration'] = formattime($vod->duration,1);
		//图片和M3U8地址
		$add['m3u8url'] = m3u8_link($exist->m3u8);
		$add['gifurl'] = m3u8_link($exist->gif,'gif');
		$add['jpgurl'] =  m3u8_link($exist->jpg,'jpg');
		for($i=1;$i<Jpg_Num;$i++){
			$n = $i+1;
			$add['jpgurl_'.$n] = m3u8_link($exist->jpg,'jpg',$n);
		}
		$add['playurl'] = 'http://'.Web_Url.links('play/'.$exist->vid);
		$res = get_api('add',$add);
		//纪录开始转吗时间 
		$this->db->update('queue',array('addtime'=>time()),array('id'=>$id));
		//码率2
		if($exist->xu == 2){
			define('KBPS', Mu_Kbps2);
			define('SIZE', Mu_Size2);
			$exist->jpg = '';
			$exist->gif = '';
		}
		//开始转码
		$this->load->library('xyz');
		$res = $this->xyz->transcode($exist->path,$exist->m3u8,$exist->jpg,$exist->gif);
		//把原始文件进行改回原名
		if(Video_SrcOn == 0){
			//判断多码率
			if(Mu_Type == 0 || $res == Mu_Kbps3){
				$file_ext = strtolower(trim(substr(strrchr($exist->path, '.'), 1)));
				$yname = end(explode('/',$exist->path));
				$xpath = str_replace($yname, iconv("UTF-8","GBK//IGNORE",$vod->name).'.'.$file_ext, $exist->path);
				rename($exist->path,$xpath);
			}
		}
		$exist = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$id)->row();
		if(!$exist) exit('视频不存在');
        $da   = (array)$exist;	
        $da['duration'] =formattime($da['duration'],1);
        get_api('add',$da);
		echo $res;
	}

	//同步
	public function tongbu(){
		$id = intval($this->input->get_post('id'));
		if($id < 1) exit('参数错误');
		$exist = $this->db->query("select * from ".CS_SqlPrefix."video where id=".$id)->row();
		if(!$exist) exit('视频不存在');
          $da   = (array)$exist;	
        $da['duration'] =formattime($da['duration'],1);
        $this->db->query("update mu_video set send=1 where id=".$id);
        echo get_api('add',$da);die;
		//发送api入库请求
		$add['code'] = 0;
		$add['vid'] = $exist->id;
		$add['cid'] = 2;//$exist->cid;
		$add['name'] = $exist->name;
		$add['size'] = formatsize($exist->size);
		$add['duration'] = formattime($exist->duration,1);
		$add['m3u8url'] = m3u8_link($exist->m3u8);
		$add['gifurl'] = m3u8_link($exist->pic,'gif');
		$add['jpgurl'] = m3u8_link($exist->pic,'jpg');
		for($i=1;$i<Jpg_Num;$i++){
			$n = $i+1;
			$add['jpgurl_'.$n] = m3u8_link($picpath,'jpg',$n);
		}
		$add['playurl'] = 'http://'.Web_Url.links('play/'.$id);
		echo get_api('add',$add);
	}
  public function todb(){
 
	    
     $list =  $this->db->query("select id from mu_video where send=0 order by id desc limit 0,5 ")->result();
    echo json_encode($list);
  }
  
    public function test(){
        $path="/www/wwwroot/yzm.zhzhxxyy.com/video/data/2019/09/21/2019092114540715135.mp4";
        $pic="/www/wwwroot/yzm.zhzhxxyy.com/video/m3u8/2019/09/21/d6abc5f3/vod.jpg";
        $gif="/www/wwwroot/yzm.zhzhxxyy.com/video/m3u8/2019/09/21/d6abc5f3/vod.gif";
        $m3u8="/www/wwwroot/yzm.zhzhxxyy.com/video/m3u8/2019/09/21/d6abc5f3/index.m3u8";
        $this->load->library('xyz');
        $res = $this->xyz->vodtojpg($path,$pic);
        echo $res;
        die;
    }
}