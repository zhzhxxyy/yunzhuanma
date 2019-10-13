<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ting extends CI_Controller {
	function __construct(){
	    parent::__construct();
	}

	public function index(){
		$this->load->view('ting.html');
	}

	//执行转码
	public function transcode(){
		if(substr(Video_Path,0,2) == './'){
			$Video_Path = FCPATH.substr(Video_Path,2);
		}else{
			$Video_Path = Video_Path;
		}
		$id = 0;
		$xt = php_uname();
		if(strpos($xt, 'Windows') !== false){
			$jc = shell_exec('tasklist');
		}else{
			$jc = shell_exec('ps aux | less');
		}
		//获取数据库未转码
		$this->load->database();
		$row = $this->db->query("select * from ".CS_SqlPrefix."queue order by id asc limit 1")->row();
		if($row){
			$id = $row->vid;
			$vod = $this->db->query("select id,name,m3u8,addtime from ".CS_SqlPrefix."video where id=".$id)->row();
			//判断转码是否完毕
			if(substr(Mu_Path,0,2) == './'){
				$m3u8_path = FCPATH.substr(Mu_Path,2).$vod->m3u8;
			}else{
				$m3u8_path = Mu_Path.$vod->m3u8;
			}
			if(Mu_Type == 1){
				$ext = end(explode('/', $m3u8_path));
				$m3u8_path2 = str_replace($ext, Mu_Kbps2.'/'.$ext, $m3u8_path);
				$is_m3u8 = (file_exists($m3u8_path) && file_exists($m3u8_path2));
			}else{
				$is_m3u8 = file_exists($m3u8_path);
			}
			if($is_m3u8){
				//正在转码，终止执行
				if(strpos($jc, 'ffmpeg') !== false){
					exit('0');
				}
				$m3u8_neir = file_get_contents($m3u8_path);
				if(strpos($m3u8_neir,'#EXT-X-ENDLIST') !== false){
					//转换完毕
					$this->db->update('video',array('sid'=>1),array('id'=>$id));
					//发送成功API消息
					get_api('api',array('vid'=>$vod->id,'name'=>$vod->name,'code'=>1));
				}else{
					//转码失败
					$this->db->update('video',array('sid'=>2),array('id'=>$id));
					//发送失败API消息
					get_api('api',array('vid'=>$vod->id,'name'=>$vod->name,'code'=>2));
				}
				$this->db->delete('queue',array('id'=>$row->id));//删除队列
				$id = 0;
			}else{
				//失败了，进程还在，需要杀掉进程继续下一个转码
				if(strpos($jc, 'ffmpeg') !== false && $row->addtime > 0 && $row->addtime+120 < time()){
					//杀掉进程
					if(strpos($xt, 'Windows') !== false){
						exec('taskkill /im ffmpeg.exe /f');
					}else{
						exec('ps aux | grep "ffmpeg" | cut -c 9-15 | xargs kill -9');
					}
					$this->db->update('video',array('sid'=>2),array('id'=>$id));
					$this->db->delete('queue',array('id'=>$row->id));//删除队列
					//发送失败API消息
					get_api('api',array('vid'=>$vod->id,'name'=>$vod->name,'code'=>2));
				}
			}
		}
		if($id == 0){
			//进入待转码目录
			if(Mu_Dir != '' && is_dir(Mu_Dir)){
				$dirinfo = dir_file(Mu_Dir);
				if(!empty($dirinfo['file'])){
					$filename = $dirinfo['file'];
					$Mu_Dir = $dirinfo['dir'];
					if(substr($Mu_Dir,-1) != '/') $Mu_Dir.='/';
					//文件后缀
					$file_ext = strtolower(trim(substr(strrchr($filename, '.'), 1)));
					//入库名字
					$video_name = safe_replace(str_replace('.'.$file_ext, '', iconv('gbk', 'utf-8',$filename)));
					//定义允许上传的文件扩展名
					$ext_arr = array_filter(explode('|', Up_Ext));
					//判断文件后缀
					if(in_array($file_ext, $ext_arr) == false) {
						unlink($Mu_Dir.$filename);
					}else{
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
							//判断是否存在
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
								//新增视频入库
								$this->db->insert("video",$data);
								$id = $this->db->insert_id();
								//M3U8保存目录
								$m3u8_dir = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
								//路径
								$time = time();
								$m3u8path = m3u8_dir($id,$time);
								$jpgpath = m3u8_dir($id,$time,'jpg');
								$gifpath = m3u8_dir($id,$time,'gif');
		        				$this->db->update('video',array('m3u8'=>$m3u8path,'pic'=>$jpgpath),array('id'=>$id));
								//queue
								$queue['xu'] = 1;
								$queue['vid'] = $id;
						        $queue['path'] = $data['path'];
						        $queue['jpg'] = $m3u8_dir.$jpgpath;
						        $queue['gif'] = $m3u8_dir.$gifpath;
						        $queue['m3u8'] = $m3u8_dir.$m3u8path;
						        $queue['duration'] = $format['duration'];
					        	$this->db->insert('queue',$queue);
					        	//多码率
					        	if(Mu_Type == 1){
					        		$ext = end(explode('/', $m3u8path));
					        		$queue['xu'] = 2;
									$queue['m3u8'] = $m3u8_dir.str_replace($ext, Mu_Kbps2.'/'.$ext, $m3u8path);
					        		$this->db->insert('queue',$queue);
					        	}
							}
						}
					}
				}
			}
		}
		echo $id;
	}
}
