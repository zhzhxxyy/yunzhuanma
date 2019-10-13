<?php
function links($path){
	$url = site_url($path);
	$url = str_replace(SELF,'index.php',$url);
	if(CS_Web_Wjt == 1){
		$url = str_replace(Web_Path.'index.php/',Web_Path,$url);
	}
	return $url;
}
//m3u8地址
function m3u8_link($path,$ac='m3u8',$xu=1){
	$Mu_Path = substr(Mu_Path,0,2) == './' ? substr(Mu_Path,2) : Mu_Path;
	$webpath = CS_Web_Wjt == 1 ? Web_Path : Web_Path.'index.php/';
	//多图模式
	if($ac != 'm3u8'){
		if($xu > 1 && strpos($path, '[xu]') === false){
			$name = end(explode('/', $path));
			$path = str_replace($name, $xu.'_'.$name, $path);
		}else{
			$path = str_replace('[xu]', $xu, $path);
		}
		if($ac == 'gif') $path = str_replace('.jpg', '.gif', $path);
	}else{
		if(Mu_Type == 1 && $xu != '1'){
			$ext = end(explode('/', $path));
			$path = str_replace($ext, $xu.'/'.$ext, $path);
		}
	}
	if(Web_Play == ''){
		if(Web_M3u8On == 1){
			if($ac == 'm3u8'){
				$link = 'http://'.Web_Url.$webpath.'m3u8/'.sys_auth($path).'.'.$ac;
			}else{
				$link = 'http://'.Web_Url.$webpath.'m3u8/pic/'.sys_auth($path).'.'.$ac;	
			}
		}else{
			$link = 'http://'.Web_Url.Web_Path.$Mu_Path.$path;
		}
	}else{
		if(Web_M3u8On == 1){
			if($ac == 'm3u8'){
				$link = 'http://'.Web_Play.$webpath.'m3u8/'.sys_auth($path).'.'.$ac;
			}else{
				$link = 'http://'.Web_Play.$webpath.'m3u8/pic/'.sys_auth($path).'.'.$ac;
			}
		}else{
			$link = 'http://'.Web_Play.'/'.$path;
		}
	}
	return $link;
}

function m3u8_dir($id,$time,$ac='m3u8',$old=0){
	if($old == 0){
		$Mu_Pic_Name = defined('Mu_Pic_Name') ? Mu_Pic_Name : '[年]/[月]/[日]/[md5]/vod.jpg';
		$Mu_M3u8_Name = defined('Mu_M3u8_Name') ? Mu_M3u8_Name : '[年]/[月]/[日]/[md5]/m3u8.m3u8';
	}else{
		$Mu_Pic_Name = '[年]/[月]/[日]/[md5]/vod.jpg';
		$Mu_M3u8_Name = '[年]/[月]/[日]/[md5]/m3u8.m3u8';
	}
	$md5 = substr(md5(Mu_Key.$id.'ctcms'),0,8);
	$yarr = array('[年]','[月]','[日]','[id]','[md5]');
	$xarr = array(date('Y',$time),date('m',$time),date('d',$time),$id,$md5);
	if($ac == 'gif'){
		$dir_file = str_replace(array('.jpg','.png','jpeg'),'.gif',str_replace($yarr, $xarr, $Mu_Pic_Name));
	}elseif ($ac == 'jpg') {
		$dir_file = str_replace($yarr, $xarr, $Mu_Pic_Name);
	}else{
		$dir_file = str_replace($yarr, $xarr, $Mu_M3u8_Name);
	}
	return $dir_file;
}
//写文件
function write_file($path, $data, $mode = FOPEN_WRITE_CREATE_DESTRUCTIVE){
	$dir = dirname($path);
	if(!is_dir($dir)){
		mkdirss($dir);
	}
	if ( ! $fp = @fopen($path, $mode)){
		return FALSE;
	}
	flock($fp, LOCK_EX);
	fwrite($fp, $data);
	flock($fp, LOCK_UN);
	fclose($fp);
	return TRUE;
}
//递归创建文件夹
function mkdirss($dir) {
    if (!$dir) {
        return FALSE;
    }
    if (!is_dir($dir)) {
        mkdirss(dirname($dir));
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
    }
    return true;
}
//JSON输出
function getjson($info,$error=1,$sign=0,$callback=''){
	$msg = $info;
	$data['error'] = $error;
	$data['info'] = $info;
	//兼容前台
	$data['msg'] = $msg;
	if($sign==1 && is_array($msg)){
		$data = array_merge($msg);
	}
	$json  = json_encode($data);
	if(!empty($callback)){
		echo $callback."(".$json.")";
	}else{
		echo $json;
	}
	exit;
}

//视频分页
function admin_page($url,$page,$pages){
	$phtml = '<div class="layui-box layui-laypage layui-laypage-default" id="layui-laypage-0">';
	if($page > 1){
		$phtml .= '<a href="'.$url.($page-1).'" class="layui-laypage-prev" data-page="'.($page-1).'">上一页</a>';
	}
	if($pages<6 || $page<4){
		if($pages < 2){
			return '';
		}
		if($pages<6){
			$len = $pages;
		}else{
			$len = 5;
		}
		for($i=1;$i<$len+1;$i++){
			$phtml .= page_curr($url,$page,$i);
		}
		if($pages>5){
			$phtml .= '<span>…</span><a href="'.$url.$pages.'" class="layui-laypage-last" title="尾页" data-page="'.$pages.'">末页</a>';
		}
	}else{//pages>$nums
		if($pages<$page+2){
			$phtml .= '<a href="'.$url.'1" class="laypage_first" data-page="1" title="首页">首页</a><span>…</span>';
			for($i=$pages-4;$i<$pages+1;$i++){
				$phtml .= page_curr($url,$page,$i);
			}
		}else{
			$phtml .='<a href="'.$url.'1" class="laypage_first" data-page="1" title="首页">首页</a><span>…</span>';
			for($i=$page-2;$i<$page+3;$i++){
				$phtml .= page_curr($url,$page,$i);
			}
			$phtml .= '<span>…</span><a href="'.$url.$pages.'" class="layui-laypage-last" title="尾页" data-page="'.$pages.'">末页</a>';
		}
	}
	if($page < $pages){
		$phtml .= '<a href="'.$url.($page+1).'" class="layui-laypage-next" data-page="'.($page+1).'">下一页</a>';
	}
	$phtml .= '<span class="layui-laypage-total phide">到第 <input id="goto_page" type="number" min="1" onkeyup="this.value=this.value.replace(/\D/, \'\')" value="'.$page.'" class="layui-laypage-skip"> 页 <button type="button" onclick="goto_page(\''.$url.'\')" class="layui-laypage-btn">确定</button></span></div>';
	return $phtml;
}
function page_curr($url,$page,$i){
	$phtml = '';
	if($page==$i){
		$phtml .= '<span class="layui-laypage-curr"><em class="layui-laypage-em"></em><em>'.$page.'</em></span>';
	}else{
		$phtml .= '<a href="'.$url.$i.'" data-page="'.$i.'">'.$i.'</a>';
	}
	return $phtml;
}
function page_data($nums,$page,$pages){
	if($pages<2){
		return '';
	}else{
		return '共'.$nums.'条记录'.$pages.'页,当前显示第'.$page.'页';
	}
}

function formatsize($size, $dec=2){
	$a = array("B", "KB", "MB", "GB", "TB", "PB");
	$pos = 0;
	while ($size >= 1024) {
		$size /= 1024;
		$pos++;
	}
	return round($size,$dec)." ".$a[$pos];
}

function formattime($time,$sign=0){
	$h = $m = $s = 0;
	$str = '';
	$s = floor($time%60);
	$m = floor($time/60)%60;
	$h = floor($time/60/60);
	if($sign==0){
		if($h>0)  return $str = $h."时".$m."分".$s.'秒';
		if($m>0)  return $str = $m."分".$s.'秒';
		return $str = $s.'秒';
	}
	if($sign==1){
		if($m<10) $m = '0'.$m;
		if($s<10) $s = '0'.$s;
		if($h>0)  return $str = $h.":".$m.":".$s;
		if($m>0)  return $str = $m.":".$s;
		return $str = '00:'.$s;
	}
}

function is_referer($m3u8=0){

    if(Web_CrossDomain == '' || 
    	Web_CrossOn == 0 || 
    	preg_match("/(PlaySDK|ExoPlayerLib)/i", strtoupper($_SERVER['HTTP_USER_AGENT']))
    ){
    	return true;
	}
	if(empty($_SERVER['HTTP_REFERER'])){
		if(Web_Cross == 0 || (Web_M3u8On == 1 && $m3u8 == 1)){
			return false;
		}else{
			return true;
		}
	}

	$uriarr = parse_url($_SERVER['HTTP_REFERER']);
	$host = $uriarr['host'];
	$ymarr = explode("|",Web_CrossDomain);
    if($host == Web_Url || $host == Web_Play || in_array($host,$ymarr)){
    	return true;
    }
    return false;
}
//SQL过滤
function safe_replace($string){
	if(is_array($string)) {
		foreach($string as $k => $v) {
			$string[$k] = safe_replace($v); 
		}
	}else{
		if(!is_numeric($string)){
			$string = str_replace('&','&amp;',$string);
			$string = str_replace('%20','',$string);
			$string = str_replace('%27','',$string);
			$string = str_replace('%2527','',$string);
			$string = str_replace("'",'&#039;',$string);
			$string = str_replace('"','&quot;',$string);
			$string = str_replace(';','',$string);
			$string = str_replace('*','',$string);
			$string = str_replace('<','&lt;',$string);
			$string = str_replace('>','&gt;',$string);
			$string = str_replace("\\",'/',$string);
			$string = str_replace('%','\%',$string);
		    $string = str_replace('{','%7b',$string);
		    $string = str_replace('}','%7d',$string);
		}
	}
	return $string;
}
//删除目录和文件
function deldir($dir,$sid=1) {
	if(!is_dir($dir)){
		return true;
	}
	//先删除目录下的文件
	$dh=opendir($dir);
	while ($file=readdir($dh)) {
		if($file!="." && $file!="..") {
			$fullpath=$dir."/".$file;
			if(!is_dir($fullpath)) {
				@unlink($fullpath);
			} else {
				deldir($fullpath,$sid);
			}
		}
	}
	closedir($dh);
	//删除当前文件夹：
	if($sid==1){
		if(@rmdir($dir)) {
			return true;
		} else {
			return false;
		}
	}else{
		return true;
	}
}
//创建目录
function creatdir($path){
	if(!is_dir($path)){
		if(creatdir(dirname($path))){
			mkdir($path,0777);
			return true;
		}
	}else{
		return true;
	}
}
//CURL
function get_curl($url,$post=''){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	if(!empty($post)){
		curl_setopt($ch, CURLOPT_USERAGENT, 'ctcms_yzm_api');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	}else{
		curl_setopt($ch, CURLOPT_USERAGENT, "ctcms_yunzhuanma");
	}
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	$content = curl_exec($ch);
	curl_close($ch);
	return $content;
}

function get_api($ac,$data=array()){
	if(Api_On==0 || Api_Url=='' || Api_Key==''){
		return 'Api Unopened';
	}
	$data['ac'] = $ac;
	$data['key'] = Api_Key;
	$res = get_curl(Api_Url,$data);

	$logfile = FCPATH.'logs/';
	mkdirss($logfile);
	$txt = date('Y-m-d H:i:s')."-----".json_encode($data)."-----".$ac."-----".$res."\r\n";
	file_put_contents($logfile.date('Y-m-d').'.txt',$txt,FILE_APPEND);
	return $res;
}

function sys_auth($string, $type = 0, $expiry = 0, $key = '157503886_yunzhuanma') {
	if($type == 1) $string = str_replace(array('-','_'),array('+','/'),$string);
	$ckey_length = 4;  
	$key = md5($key);
	$keya = md5(substr($key, 0, 16));     
	$keyb = md5(substr($key, 16, 16));     
	$keyc = $ckey_length ? ($type == 1 ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : ''; 
	$cryptkey = $keya.md5($keya.$keyc);   
	$key_length = strlen($cryptkey);     
	$string = $type == 1 ? base64_decode(substr($string, $ckey_length)) :  sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;   
	$string_length = strlen($string);   
	$result = '';   
	$box = range(0, 255);   
	$rndkey = array();     
	for($i = 0; $i <= 255; $i++) {   
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);   
	}     
	for($j = $i = 0; $i < 256; $i++) {   
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;   
		$tmp = $box[$i];   
		$box[$i] = $box[$j];   
		$box[$j] = $tmp;   
	}   
	for($a = $j = $i = 0; $i < $string_length; $i++) {   
		$a = ($a + 1) % 256;   
		$j = ($j + $box[$a]) % 256;   
		$tmp = $box[$a];   
		$box[$a] = $box[$j];   
		$box[$j] = $tmp;   
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));   
	}   
	if($type == 1) {    
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {   
			return substr($result, 26);   
		} else {   
			return '';   
		}   
	} else {    
		return str_replace(array('+','/'), array('-','_'), $keyc.str_replace('=', '', base64_encode($result)));   
	}
}
//获取待转目录第一个文件
function dir_file($dir){
	if(!is_dir($dir)) return '';
	$darr = array();
	$dh = opendir($dir);
	while ($file=readdir($dh)) {
		if(empty($darr) && $file!="." && $file!="..") {
			if(!is_dir($dir.'/'.$file)){
				$darr['dir'] = $dir;
				$darr['file'] = $file;
				break;
			} else {
				$darr = dir_file($dir.'/'.$file);
				if(!empty($darr)) break;
			}
		}
	}
	closedir($dh);
	if(!empty($darr)){
		return $darr;
	}
}