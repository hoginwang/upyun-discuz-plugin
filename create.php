<?php
header('Content-Type: text/plain; charset=utf-8');
define('IN_DISCUZ', true);
define('DISCUZ_ROOT', 'D:\\xampp\\htdocs\\dzx3.5\\demo\\');
$plugin_id = 'upyun';
include DISCUZ_ROOT . "source/plugin/$plugin_id/function_upyun.php";
$files = upyun_get_install_files();
$result = [];
$output = [];
foreach($files as $file){
	$source_file = substr($file, strlen(DISCUZ_ROOT));
	$source_filepath = explode('/', $source_file);
	$result[$source_file] = upyun_md5_file('./discuz_3_5/'.$source_file);
	$output[] = '				\''.end($source_filepath).'\' => \''.$result[$source_file].'\','."\n";
}
print_r($result);
echo implode('', $output);