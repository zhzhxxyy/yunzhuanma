<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
class Xyz {
    function __construct() {
        set_time_limit(0);
        if(substr(Mu_Ffpath,0,2) == './'){
            $Mu_Ffpath = FCPATH.substr(Mu_Ffpath,2);
        }else{
            $Mu_Ffpath = Mu_Ffpath;
        }
        if(substr($Mu_Ffpath,-1) != '/') $Mu_Ffpath .= '/';
        $xt = php_uname();
        if(strpos($xt, 'Windows') !== false){
        	$this->zmpath = 'packs/zimu.ass';
        	$this->sypath = 'packs/logo';
            $this->ffmpeg = $Mu_Ffpath.'ffmpeg.exe';
            $this->ffprobe = $Mu_Ffpath.'ffprobe.exe';
        }else{
        	$this->zmpath = FCPATH.'packs/zimu.ass';
        	$this->sypath = FCPATH.'packs/logo';
            $this->ffmpeg = $Mu_Ffpath.'ffmpeg';
            $this->ffprobe = $Mu_Ffpath.'ffprobe';
        }
        $ip = '/' == DIRECTORY_SEPARATOR ? $_SERVER['SERVER_ADDR'] : gethostbyname($_SERVER['SERVER_NAME']);

    }

    public function transcode($src_path,$m3u8_path='',$jpg_path='',$gif_path='') {
		$src_path = str_replace("//","/",str_replace("\\","/",$src_path));
		$m3u8_path = str_replace("//","/",str_replace("\\","/",$m3u8_path));
        $obj_path = dirname($m3u8_path);
        mkdirss($obj_path);
        $m3u8_time = Mu_Time;       //ts时长
		if( Mp_On == 0 ) 
        {
         $mp = '-c:a copy';
        }
        else
        {
          $mp= '-c:a aac';
        }
        //jpg + gif
        if(!empty($jpg_path)) $jpg = $this->vodtojpg($src_path,$jpg_path);
        if(!empty($gif_path)) $gif = $this->vodtogif($src_path,$gif_path);
        //获取格式命令
        $format = $this->format($src_path);
        //字幕+水印
        $watermark = $this->watermark_zm();
        //加密
        $aes = $this->m3u8aes($obj_path);
        //缩放
		$Mu_Size = defined('SIZE') ? SIZE : Mu_Size;
        $change = !empty($Mu_Size) ? '-s '.$Mu_Size : '';
        $Mu_Kbps = defined('KBPS') ? (int)KBPS : (int)Mu_Kbps;
        $bit_rate = (int)$format['bit_rate'];
        $kbps = '';
        if($bit_rate > 0){
        	$bit = $bit_rate / 1000;
        	if($Mu_Kbps > 0 && $bit > $Mu_Kbps){
                $kbps = '-b:v '.$Mu_Kbps.'k';
        	}
        }
        //跳过片头
        $pttime = Mu_PtTime == 0 ? '' : '-ss '.formattime(Mu_PtTime,1);
        //速度
        $prearr = array('ultrafast','superfast','veryfast','faster','fast','medium','slow','slower');
        $Mu_Preset = defined('Mu_Preset') ? Mu_Preset : 'fast';
        if(!in_array($Mu_Preset, $prearr) || $Mu_Preset == 'medium') $Mu_Preset = '';
        $preset = !empty($Mu_Preset) ? '-preset:v '.$Mu_Preset : '';
        //执行转换
        $extarr = explode(',', $format['ext']);
		if(in_array('mp4', $extarr) && empty($watermark)) 
		if($format['audio']=='aac' && $format['video'] == 'h264')
		if ( Mq_On == 0 ){
			$make_command = $this->ffmpeg.' '.$pttime.' -y -i '.$src_path.' '.$change.' '.$kbps.' '.$preset.' -codec copy -vbsf h264_mp4toannexb -hls_time '.$m3u8_time.' '. $aes . ' -hls_segment_filename ' . $obj_path . '/%04d.ts -hls_list_size 0 ' . $m3u8_path;
            //$make_command = $this->ffmpeg.' '.$pttime.' -y -i '.$src_path.' '.$change.' '.$kbps.' '.$preset.' -codec copy -vbsf h264_mp4toannexb -map 0 -f segment -segment_list '.$m3u8_path.' -segment_time '.$m3u8_time.' '.$obj_path.'/%04d.ts'; //此代码为原来无修改代码，如果上面那行代码导致里部分转码不完整可以使用 // 进行注销，把本行的 // 删除后保存即可
		}else{
            $make_command = $this->ffmpeg.' '.$pttime.' -y -i '.$src_path.' '.$watermark.' '.$change.' '.$kbps.' '.$preset.' -hls_time '.$m3u8_time.' '.$mp.' '.$aes.' -hls_segment_filename '.$obj_path.'/%04d.ts -hls_list_size 0 '.$m3u8_path;
        }else{
            $make_command = $this->ffmpeg.' '.$pttime.' -y -i '.$src_path.' '.$watermark.' '.$change.' '.$kbps.' '.$preset.' -hls_time '.$m3u8_time.' '.$mp.' '.$aes.' -hls_segment_filename '.$obj_path.'/%04d.ts -hls_list_size 0 '.$m3u8_path;
        }else{
            $make_command = $this->ffmpeg.' '.$pttime.' -y -i '.$src_path.' '.$watermark.' '.$change.' '.$kbps.' '.$preset.' -c:v libx264 -strict -2 -f hls -hls_list_size 0 -hls_time '.$m3u8_time.' '.$mp.' '.$aes.' -hls_segment_filename '.$obj_path.'/%04d.ts '.$m3u8_path;
		//}else{
          //$make_command = $this->ffmpeg.' '.$pttime.' -y -i '.$src_path.' '.$watermark.' '.$change.' '.$kbps.' '.$preset.' -c:v libx264 -c:a aac -strict -2 -f hls -hls_list_size 0 -hls_time '.$m3u8_time.' '.$aes.' -hls_segment_filename '.$obj_path.'/%04d.ts '.$m3u8_path; //此代码为原来无修改代码，如果上面那行代码导致里部分转码不完整可以使用 // 进行注销，把本行的 // 删除后保存即可
        }
        $result = exec($make_command,$arr,$log);
        if($log == 0){
            return defined('KBPS') ? KBPS : 'm3u8ok';
        }else{
            return '';
        }
    }
    //获取视频格式信息
    public function format($src_path){
		$src_path = str_replace("//","/",str_replace("\\","/",$src_path));
        $arr = array(
            'video' => '',
            'audio' => '',
            'duration' => 0,
            'width' => 0,
            'height' => 0,
            'dis_ratio' => '',
            'size' => 0,
            'bit_rate' => 0
        );
        if(empty($src_path)) return $arr;
        $format_command = $this->ffprobe.' -v quiet -print_format json -show_format -show_streams '.$src_path;
        $format = shell_exec($format_command);
        $json = json_decode($format);
        $audio = '';$video = '';
        foreach($json->streams as $row){
            if($row->codec_type=='video'){
                $arr['video'] = $row->codec_name;
                $arr['duration'] = $row->duration;
                $arr['width'] = $row->width;
                $arr['height'] = $row->height;
                $arr['dis_ratio']= $row->display_aspect_ratio;
            }
            if($row->codec_type=='audio'){
                $arr['audio'] = $row->codec_name;
            }
        }
		if(empty($arr['duration'])) $arr['duration'] = $json->format->duration;
        $arr['size'] = $json->format->size;
        $arr['bit_rate'] = $json->format->bit_rate;
        $arr['ext'] = $json->format->format_name;
        return $arr;
    }
    //视频截图JPG
    function vodtojpg($src_path,$obj_path){
        if(Jpg_On==1) return 'no';
        mkdirss(dirname($obj_path));
		$Jpg_Size = Jpg_Size;
        $size = !empty($Jpg_Size)?'-s '.Jpg_Size:'';
        $filename = end(explode('/', $obj_path));
        if(strpos($filename,'[xu]') !== false){
            $jpg_path = str_replace('[xu]', '1', $obj_path);
        }else{
            $jpg_path = $obj_path;
        }
        $jpg_command = $this->ffmpeg.' -y -i '.$src_path.' -y -f image2 -ss '.Jpg_Time.' '.$size.' -t 0.001 '.$jpg_path;
        $jpg = exec($jpg_command,$arr,$log);
        //多张图
        if(Jpg_Num > 1){
            for($i=1;$i<Jpg_Num;$i++){
				$n = $i+1;
                if(strpos($obj_path, '[xu]') === false){
                    $jpg_path2 = str_replace($filename, $n.'_'.$filename, $obj_path);
                }else{
                    $jpg_path2 = str_replace('[xu]', $n, $obj_path);
                }
                $jpg_pos = Jpg_Time*$n;//截图时间点
                $jpg_command = $this->ffmpeg.' -y -i '.$src_path.' -y -f image2 -ss '.$jpg_pos.' '.$size.' -t 0.001 '.$jpg_path2;
                $jpg = exec($jpg_command,$arr,$log);
            } 
        }
        if($log==0){
            return 'ok';
        }else{
            return 'no';
        }
    }
    //截取GIF
    function vodtogif($src_path,$obj_path){
        if(Gif_On==1) return 'no';
        mkdirss(dirname($obj_path));
        $gif_pos = Gif_Time;//开始位置
        $gif_len = Gif_Len;//截取时长
		$Gif_Size = Gif_Size;
        $size = !empty($Gif_Size)?'-s '.Gif_Size:'';
        if(strpos($obj_path,'[xu]') !== false){
            $obj_path = str_replace('[xu]', '1', $obj_path);
        }
        $gif_command = $this->ffmpeg.' -y -ss '.$gif_pos.' -t '.$gif_len.' -i '.$src_path.' '.$size.' -f gif '.$obj_path;
        $gif = exec($gif_command,$arr,$log);
        if($log==0){
            return 'ok';
        }else{
            return $gif_command;
        }
    }
    //水印OR字幕
    function watermark_zm(){
    	$cmd = '';
        //左上
        if(Wm_On == 1){
            $cmd = '-vf "movie='.$this->sypath.'.png[wm1];[in][wm1]overlay='.Wm_Lt.'[out]"';
        }
        //右上
        if(Wm_On2 == 1){
            $mar_arr = explode(':', Wm_Lt2);
            $mar1 = intval($mar_arr[0]);
            $mar2 = intval($mar_arr[1]);
            $wm = 'overlay=main_w-overlay_w-'.$mar1.':'.$mar2;
            if(empty($cmd)){
                $cmd = '-vf "movie='.$this->sypath.'2.png[wm2];[in][wm2]'.$wm.'[out]"';
            }else{
                $cmd = str_replace('[in]', 'movie='.$this->sypath.'2.png[wm2];[in][wm2]'.$wm.'[a];[a]', $cmd);
            }
        }
        //左下
        if(Wm_On3 == 1){
            $mar_arr = explode(':', Wm_Lt3);
            $mar1 = intval($mar_arr[0]);
            $mar2 = intval($mar_arr[1]);
            $wm = 'overlay='.$mar1.':main_h-overlay_h-'.$mar2;
            if(empty($cmd)){
                $cmd = '-vf "movie='.$this->sypath.'3.png[wm3];[in][wm3]'.$wm.'[out]"';
            }else{
                $cmd = str_replace('[in]', 'movie='.$this->sypath.'3.png[wm3];[in][wm3]'.$wm.'[b];[b]', $cmd);
            }
        }
        //右下
        if(Wm_On4 == 1){
            $mar_arr = explode(':', Wm_Lt4);
            $mar1 = intval($mar_arr[0]);
            $mar2 = intval($mar_arr[1]);
            $wm = 'overlay=main_w-overlay_w-'.$mar1.':main_h-overlay_h-'.$mar2;
            if(empty($cmd)){
                $cmd = '-vf "movie='.$this->sypath.'4.png[wm4];[in][wm4]'.$wm.'[out]"';
            }else{
                $cmd = str_replace('[in]', 'movie='.$this->sypath.'4.png[wm4];[in][wm4]'.$wm.'[c];[c]', $cmd);
            }
        }
        if(Wm_Zm == 1){
            if(empty($cmd)){
                $cmd = '-vf "ass='.$this->zmpath.'"';
            }else{
                $cmd = str_replace('[in]', 'ass='.$this->zmpath.'[asub];[asub]', $cmd);
            }
        }
        return $cmd;
    }
    //m3u8加密
    function m3u8aes($path){
		if(Mu_On == 1) return '';
        $src_str = '0123456789abcdefghijklmnopqrstuvwxyz';
        $aes_key = substr(str_shuffle($src_str), 0, 16);
        if(substr($path, -1)!='/') $path .= '/';
        if(!is_dir($path)) mkdir($path,0777,true);
        $fp =fopen($path.'key.key', 'w');
        fwrite($fp, $aes_key);
        fclose($fp);
        $keyinfo = "key.key\n".$path."key.key";
        $fp = fopen($path.'key_info', 'w');
        fwrite($fp, $keyinfo);
        fclose($fp);
        return '-hls_key_info_file '.$path.'key_info';
    }
}
