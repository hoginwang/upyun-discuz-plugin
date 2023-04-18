<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: discuz_ftp.php 32473 2013-01-24 07:11:38Z chenmengshu $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

// 引入UPYUN PHP-SDK
require_once DISCUZ_ROOT.'source/plugin/upyun/php-sdk/vendor/autoload.php';
use Upyun\Upyun;
use Upyun\Config;

class discuz_ftp
{

	var $enabled = false;
	var $config = array();
	var $api_access = array(UpYun::ED_AUTO, UpYun::ED_TELECOM, UpYun::ED_CNC, UpYun::ED_CTT);
	var $connectid;
	var $_error;
	var $upyun_config = array();

	public static function &instance($config = array()) {
		static $object;
		if(empty($object)) {
			$object = new discuz_ftp($config);
		}
		return $object;
	}

	function __construct($config = array()) {
		global $_G;
		$this->set_error(0);
		loadcache('plugin');
		$this->upyun_config = getglobal('cache/plugin/upyun');
		$this->config = !$config ? getglobal('setting/ftp') : $config;
		$this->enabled = false;
		$this->config['host'] = discuz_ftp::clear($this->config['host']);
		$this->config['port'] = intval($this->config['port']);
		$this->config['ssl'] = intval($this->config['ssl']);
		$this->config['bucketname'] = $this->config['host'];
		$this->config['username'] = discuz_ftp::clear($this->config['username']);
		$this->config['password'] = authcode($this->config['password'], 'DECODE', md5(getglobal('config/security/authkey')));
		$this->config['timeout'] = intval($this->config['timeout']);
		$this->config['api_access'] = $this->api_access[$this->config['port']];
		$this->connectid = true;
		$this->enabled = true;
	}

	function upload($source, $target) {
		$service_config = new Config(
			$this->upyun_config['bucket_name'],
			$this->upyun_config['operator_name'],
			$this->upyun_config['operator_pwd']
		);
		$fh = fopen($source, 'rb');
		if(!$fh) {
			return 0;
		}
		$upyun = new Upyun($serviceConfig);
		$rsp = $upyun->write('/'.ltrim($target, '/'), $fh, true);
		return $rsp;
	}

	function connect() {
		return 1;
	}

	function set_error($code = 0) {
		$this->_error = $code;
	}

	function error() {
		return $this->_error;
	}

	function clear($str) {
		return str_replace(array( "\n", "\r", '..'), '', $str);
	}

	function ftp_rmdir($directory) {
		return 1;
	}

	function ftp_size($remote_file) {
		$service_config = new Config(
			$this->upyun_config['bucket_name'],
			$this->upyun_config['operator_name'],
			$this->upyun_config['operator_pwd']
		);
		$upyun = new Upyun($serviceConfig);
		$remote_file = discuz_ftp::clear($remote_file);
		try{
			$rsp = $upyun->info('/'.ltrim($remote_file, '/'));
			return $rsp['x-upyun-file-size'];
		}
		catch(Exception $e){
			return -1;
		}
	}

	function ftp_close() {
		return 1;
	}

	function ftp_delete($path) {
		$service_config = new Config(
			$this->upyun_config['bucket_name'],
			$this->upyun_config['operator_name'],
			$this->upyun_config['operator_pwd']
		);
		$upyun = new Upyun($serviceConfig);
		$path = discuz_ftp::clear($path);
		try{
			$rsp = $upyun->delete('/'.ltrim($path, '/'));
			return $rsp;
		}
		catch(Exception $e){
			return 0;
		}
	}

	function ftp_get($local_file, $remote_file, $mode, $resumepos = 0) {
		$service_config = new Config(
			$this->upyun_config['bucket_name'],
			$this->upyun_config['operator_name'],
			$this->upyun_config['operator_pwd']
		);
		$upyun = new Upyun($serviceConfig);
		$remote_file = discuz_ftp::clear($remote_file);
		$local_file = discuz_ftp::clear($local_file);
		try{
			if($fh = fopen($local_file, 'wb')){
				$rsp = $upyun->read('/'.ltrim($remote_file, '/'), $fh);
				fclose($fh);
				return $rsp;
			}else{
				return 0;
			}
		}
		catch(Exception $e){
			return 0;
		}
	}

}