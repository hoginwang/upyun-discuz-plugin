<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include DISCUZ_ROOT . 'source/discuz_version.php';

function upyun_install($plugin_id, $version, $files) {
	$result = upyun_move_file($plugin_id, $version, $files);
	if(!$result) {
		upyun_move_file($plugin_id, $version, $files, false);
		return false;
	}
	return true;
}

function upyun_uninstall($plugin_id, $version, $files) {
	return upyun_move_file($plugin_id, $version, $files, false);
}

function upyun_get_discuz_version() {
	switch(DISCUZ_VERSION) {
		case 'X3.5':
			$version = 'discuz_3_5';
			break;
		default:
			$version = false;
	}
	return $version;
}

function upyun_move_file($plugin_id,  $version, $files, $is_install = true) {
	$install_dir = DISCUZ_ROOT . "source/plugin/$plugin_id/$version/" . ($is_install ? 'install' : 'uninstall');

	$changed = 0;
	foreach($files as $file_path) {
		$result = copy($install_dir . '/' . basename($file_path), $file_path);
		$changed += $result;
	}
	if($changed == count($files)) {
		return true;
	} else {
		return false;
	}
}

function upyun_file_check($files) {
	global $operation;
	$msg = array();
	if(! is_array($files)) {
		return false;
	}
	$md5_check_files = upyun_get_file_md5();
	foreach($files as $file_path) {
		$handle = fopen($file_path, 'ab');
		if(! $handle) {
			$msg[] = $file_path . ' 不能写入; 请执行命令修改: chmod 666 ' . $file_path;
		}
		fclose($handle);
		$filename = basename($file_path);
		//仅在安装时校验文件
		if($operation == 'import' &&
		   upyun_md5_file($file_path) !== $md5_check_files[$filename]) {
			$msg[] = $file_path . ' 已经被修改，请手动安装。';
		}
	}
	if(!empty($msg)) {
		return implode("\n", $msg);
	}
	return true;
}

function upyun_attachment_download($attach, $module) {
	global $_G;
	$upyun_config = $_G['cache']['plugin']['upyun'];
	$dl_url = empty($upyun_config['dl_url']) ? $upyun_config['url'] : $upyun_config['dl_url'];
	$url = rtrim($dl_url, '/') . "/$module/";
	if($attach['remote'] && !$_G['setting']['ftp']['hideurl']){
		if(strtolower(CHARSET) == 'gbk') {
			$attach['filename'] = urlencode(iconv('GBK', 'UTF-8', $attach['filename']));
		} elseif (strtolower(CHARSET) == 'big5'){
			$attach['filename'] = urlencode(iconv('BIG5', 'UTF-8', $attach['filename']));
		} else {
			$attach['filename'] = urlencode($attach['filename']);
		}
		$path = $module ? "/$module/{$attach['attachment']}" : $attach['attachment'];
		$sign_add = '';
		switch($upyun_config['anti_hotlinking']){
			case 1:
				$sign = upyun_gen_signA($path);
				$sign_add = '&path='.$path.'&sign='.$sign.'&auth_key='.$sign;
				break;
			default:
				$sign = upyun_gen_sign($path);
				$sign_add = '&_upt='.$sign;
		}
		dheader('Location:'.$url.$attach['attachment'].'?_upd='.$attach['filename'].$sign_add);
	}
}

function upyun_gen_sign($path = '/') {
	global $_G;
	$upyun_config = $_G['cache']['plugin']['upyun'];

	if($upyun_config['token'] && $upyun_config['token_timeout']){
		$etime = time() + $upyun_config['token_timeout'];
		$sign = substr(md5($upyun_config['token'].'&'.$etime.'&'.$path), 12,8).$etime;
	} else {
		$sign = '';
	}
	return $sign;
}

function upyun_gen_signA($path = '/') {
	global $_G;
	$upyun_config = $_G['cache']['plugin']['upyun'];
	
	function gen_uuid(){
		mt_srand(crc32(microtime()));
		$a = sprintf('%04x%04x',
			mt_rand(0, 0xffff ), mt_rand(0, 0xffff)
		);
		$b = sprintf('%04x-%04x',
			mt_rand(0, 0xffff ),
			mt_rand(0, 0x0fff ) | 0x4000
		);
		mt_srand(crc32(microtime()));
		$c = sprintf('%04x%04x',
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff)
		);
		$d = sprintf('%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
		return $a.'-'.$b.'-'.$c.$d;
	}

	if($upyun_config['token'] && $upyun_config['token_timeout']){
		$etime = $_SERVER['REQUEST_TIME'];
		$rand_uuid = gen_uuid();
		$rand = strtr($rand_uuid, array('-' => ''));
		//$sstring = "URI-Timestamp-rand-uid-PrivateKey"
		$sign_string = $path."-".$etime."-".$rand."-0-".$upyun_config['token'];
		$md5 = md5($sign_string);
		$sign = $etime."-".$rand."-0-".$md5;
	} else {
		$sign = '';
	}
	return $sign;
}

function upyun_get_install_files() {
	$files = array(
		DISCUZ_ROOT . "source/class/discuz/discuz_ftp.php",
		DISCUZ_ROOT . "source/function/function_attachment.php",
		DISCUZ_ROOT . "source/function/function_home.php",
		DISCUZ_ROOT . "source/function/function_post.php",
		DISCUZ_ROOT . "source/module/forum/forum_attachment.php",
		DISCUZ_ROOT . "source/module/forum/forum_image.php",
		DISCUZ_ROOT . "source/module/portal/portal_attachment.php",
	);
	return $files;
}

function upyun_get_file_md5() {
	switch(DISCUZ_VERSION) {
		case 'X3.5':
			return array(
				'discuz_ftp.php' => '65e20ff0b6a2a2946b873c4b531a924f',
				'function_attachment.php' => '5a5e8bc6d7a1fe47d130140872e8ce38',
				'function_home.php' => '7294f9d64605447044524f5d086833b2',
				'function_post.php' => '3b907aba6a09cfbbac7937007bc87ae2',
				'forum_attachment.php' => '933649ad83bbdf6f9a32dc5996a764e0',
				'forum_image.php' => '7442f1f6e8908e909cd00324dc220f70',
				'portal_attachment.php' => '86b3faa2eb8b69e1b3fe29751537594e',
			);
			break;
		default:
			return array();
	}
}

/**
 * 将换行符统一处理为 \r\n 再生成 md5
 * @param $path: 文件路径
 * @return bool|string
 */
function upyun_md5_file($path) {
	$f = file_get_contents($path);
	if(!$f) {
		return false;
	}

	return md5(preg_replace("/(?<!\r)\n/", "\r\n", $f));
}
