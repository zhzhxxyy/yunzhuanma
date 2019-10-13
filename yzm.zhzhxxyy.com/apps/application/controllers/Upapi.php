<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Upapi extends CI_Controller {

	function __construct(){
	    parent::__construct();
		$this->load->helper('string');
		$this->load->database();
		if(Up_Api == 0) getjson('上传接口关闭');
	}

	public function index(){
		$cid = (int)$this->input->get('cid');
		$t = time();
		$key = md5($t.$cid.'qq157503886_yun');
		$data['uploadsave'] = site_url('upapi/save/'.$cid).'?key='.$key.'&t='.$t;
		$data['vlist'] = $this->db->query("select * from ".CS_SqlPrefix."class order by id asc")->result();
		$data['cid'] = $cid;
        $this->load->view('upapi.html',$data);
	}

	
	public function save($cid=0){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
		header("Cache-Control: no-store, no-cache, must-revalidate"); 
		header("Cache-Control: post-check=0, pre-check=0", false); 
		header("Pragma: no-cache"); 
		set_time_limit(0);
		$cid = (int)$cid;
		$key = $this->input->get('key');
		$t = $this->input->get('t');
		if(empty($key) || empty($t)) getjson('请求参数错误');
		if(md5($t.$cid.'qq157503886_yun') != $key) getjson('非法请求');
		if(substr(Video_Path,0,2) == './'){
			$Video_Path = FCPATH.substr(Video_Path,2);
		}else{
			$Video_Path = Video_Path;
		}
		
		$targetDir = $Video_Path.'/temp/'.date('Ymd').'/';
	
		$uploadDir = $Video_Path.'/'.date('Y').'/'.date('m').'/'.date('d').'/';
		$uploadStr = date('Y').'/'.date('m').'/'.date('d').'/';

		//判断MD5秒传
		$filemd5 = $this->input->post('md5',true);
		$filename = $this->input->post('filename',true);
		if(!empty($filemd5) && !empty($filename)){
			if(!preg_match("/[0-9A-Za-z]/",$filemd5)){
				getjson('MD5不合法');
			}elseif(Video_SrcOn == 0){
				//判断MD5是否存在
				$row = $this->db->query("select * from ".CS_SqlPrefix."video where md5='".$filemd5."'")->row();
				if($row){
					$file_ext = strtolower(trim(substr(strrchr($row->path, '.'), 1)));
					$filename = safe_replace(str_replace('.'.$file_ext,'',$filename));
					//判断标题是否存在
					$res = $this->db->query("select id from ".CS_SqlPrefix."video where name='".$filename."'")->row();
					if($res) getjson("yes");
					//复制文件
					$newname = date('YmdHis').time().'.'.$file_ext;
					if(copy($row->path, $uploadDir.$newname)){
						//video
						$data['cid'] = $cid;
						$data['name'] = $filename;
						$data['path'] = $uploadDir.$newname;
						$data['md5'] = $filemd5;
						$data['duration'] = $row->duration;
						$data['size'] = $row->size;
						$data['addtime'] = time();
						$this->db->insert("video",$data);
						$insert_id = $this->db->insert_id();
						//M3U8保存目录
						$m3u8_dir = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
						//路径
						$time = time();
						$m3u8path = m3u8_dir($insert_id,$time);
						$jpgpath = m3u8_dir($insert_id,$time,'jpg');
						$gifpath = m3u8_dir($insert_id,$time,'gif');
        				$this->db->update('video',array('m3u8'=>$m3u8path,'pic'=>$jpgpath),array('id'=>$insert_id));
						//queue
						$queue['xu'] = 1;
						$queue['vid'] = $insert_id;
				        $queue['path'] = $data['path'];
				        $queue['jpg'] = $m3u8_dir.$jpgpath;
				        $queue['gif'] = $m3u8_dir.$gifpath;
				        $queue['m3u8'] = $m3u8_dir.$m3u8path;
				        $queue['duration'] = $row->duration;
			        	$this->db->insert('queue',$queue);
			        	//多码率
			        	if(Mu_Type == 1){
			        		$ext = end(explode('/', $m3u8path));
			        		$queue['xu'] = 2;
							$queue['m3u8'] = $m3u8_dir.str_replace($ext, Mu_Kbps2.'/'.$ext, $m3u8path);
			        		$this->db->insert('queue',$queue);
			        	}
			        	//返回数据
        				$ok['name'] = $filename;
			        	$ok['m3u8'] = m3u8_link($m3u8path);
			        	$ok['pic'] = m3u8_link($jpgpath,'jpg');
			        	$ok['purl'] = 'http://'.Web_Url.links('play/'.$insert_id);
			        	$ok['size'] = formatsize($row->size);
			        	$ok['sec'] = formattime($row->duration,1);
        				$ok['msg'] = 'ok';
			        	getjson($ok,0,1);
			        }
				}
			}
			getjson('参数错误');
		}

		//定义允许上传的文件扩展名
		$ext_arr = array_filter(explode('|', Up_Ext));
		//防止外部跨站提交
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
			getjson('提交方式不正确');
		}
		//上传出错
		if (!empty($_REQUEST[ 'debug' ]) ) { 
			$random = rand(0, intval($_REQUEST[ 'debug' ]) ); 
			if ( $random === 0 ) { 
				header("HTTP/1.0 500 Internal Server Error"); exit;
			}
		}
		//创建目录
		if (!file_exists($targetDir)) { 
			creatdir($targetDir); 
		} 
		//创建目录
		if (!file_exists($uploadDir)) { 
			creatdir($uploadDir); 
		}
		//原文件名
		if(!isset($_FILES['video']['name'])){
			getjson('No FILES');
		}
		$file_name = $_FILES['video']['name'];
		//文件后缀
		$file_ext = strtolower(trim(substr(strrchr($file_name, '.'), 1)));
		$newname = iconv("UTF-8","GBK//IGNORE",$file_name);
		$video_name = safe_replace(str_replace('.'.$file_ext, '', $file_name));
		//判断是否存在
		$res = $this->db->query("select id from ".CS_SqlPrefix."video where name='".$video_name."'")->row();
		if($res){
			getjson("视频已经存在");
		}
		//判断文件后缀
		if(in_array($file_ext, $ext_arr) == false) {
			getjson("不支持的格式");
		}
		//根据文件名和会员ID生成一个唯一MD5
		$fileName = md5($file_name).'.'.$file_ext;
		$oldName = $fileName;
		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName; 
		//分片ID
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0; 
		//分片总数量
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;  
		//打开临时文件 
		if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) { 
			getjson('Failed to open output stream.'); 
		} 
		if (!empty($_FILES)) {
			
			if ($_FILES["video"]["error"] || !is_uploaded_file($_FILES["video"]["tmp_name"])) { 
				getjson('Failed to move uploaded file.'); 
			} 
			//读取二进制输入流并将其附加到临时文件
			if (!$in = @fopen($_FILES["video"]["tmp_name"], "rb")) { 
				getjson('Failed to open input stream.'); 
			} 
		} else { 
			if (!$in = @fopen("php://input", "rb")) { 
				getjson('Failed to open input stream.'); 
			}
		} 
		while ($buff = fread($in, 4096)) { 
			fwrite($out, $buff); 
		} 
		@fclose($out); 
		@fclose($in); 
		rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part"); 
		$index = 0; 
		$done = true; 
		for( $index = 0; $index < $chunks; $index++ ) { 
			if (!file_exists("{$filePath}_{$index}.part") ) { 
				$done = false; 
				break; 
			} 
		}
		if($done){ 
			$pathInfo = pathinfo($fileName); 
			$hashStr = substr(md5($pathInfo['basename']),8,5);
			$hashName = date('YmdHis').$hashStr.'.'.$pathInfo['extension']; 
			$uploadPath = $uploadDir.$hashName;
			$uploadStr .= $hashName;
			if (!$out = @fopen($uploadPath, "wb")) { 
				getjson('Failed to open output stream.'); 
			} 
			if ( flock($out, LOCK_EX) ) { 
				for( $index = 0; $index < $chunks; $index++ ) { 
					if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) { 
						break; 
					} 
					while ($buff = fread($in, 4096)) { 
						fwrite($out, $buff); 
					} 
					@fclose($in); 
					@unlink("{$filePath}_{$index}.part"); 
				} 
				flock($out, LOCK_UN); 
			} 
			@fclose($out);
			//video
			$data['cid'] = $cid;
			$data['name'] = $video_name;
			$data['path'] = $uploadPath;
			$data['md5'] = md5_file($uploadPath);
			$data['addtime'] = time();
			$this->load->library('xyz');
			$format = $this->xyz->format($uploadPath);
			if(empty($format['size']) || empty($format['duration'])){
				unlink($uploadPath);
				getjson('ffmpeg error'); 
			}
			$data['duration'] = $format['duration'];
			$data['size'] = $format['size'];
			$this->db->insert("video",$data);
			$insert_id = $this->db->insert_id();
			//M3U8保存目录
			$m3u8_dir = substr(Mu_Path,0,2) == './' ? FCPATH.substr(Mu_Path,2) : Mu_Path;
			//路径
			$time = time();
			$m3u8path = m3u8_dir($insert_id,$time);
			$jpgpath = m3u8_dir($insert_id,$time,'jpg');
			$gifpath = m3u8_dir($insert_id,$time,'gif');
			$this->db->update('video',array('m3u8'=>$m3u8path,'pic'=>$jpgpath),array('id'=>$insert_id));
			//queue
			$queue2['xu'] = 1;
			$queue['vid'] = $insert_id;
	        $queue['path'] = $uploadPath;
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

        	//返回数据
        	$ok['name'] = $video_name;
        	$ok['m3u8'] = m3u8_link($m3u8path);
        	$ok['pic'] = m3u8_link($jpgpath,'jpg');
        	$ok['purl'] = 'http://'.Web_Url.links('play/'.$insert_id);
        	$ok['size'] = formatsize($format['size']);
        	$ok['sec'] = formattime($format['duration'],1);
        	$ok['msg'] = 'ok';
        	getjson($ok,0,1);
		}
	}
}

