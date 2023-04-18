<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
include_once 'function_upyun.php';
class plugin_upyun {
	function global_header() {
		global $_G;
		if(!($upyun_config['anti_hotlinking'] == 0 || $upyun_config['token'] || $upyun_config['token_timeout'])) return;
		//防盗链 token 写入用户网站的一级域名
		$cookie_domain = substr($_SERVER['SERVER_NAME'], strpos($_SERVER['SERVER_NAME'], '.'));
		setcookie('_upt', upyun_gen_sign(), $_SERVER['REQUEST_TIME'] + 180, '/', $cookie_domain, $_G['isHTTPS'], true);
	}

	function common() {
		global $_G;
		$upyun_config = $_G['cache']['plugin']['upyun'];
		$_G['setting']['ftp']['attachurl'] = rtrim($upyun_config['url'], '/') . '/';
	}
}

//mobile plugin is used for mobile access
class mobileplugin_upyun extends plugin_upyun{}
