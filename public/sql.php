<?php
session_start();
if(!isset($_SESSION['sql_file_name']) || !file_exists($_SESSION['sql_file_name'])){
	$latest = file_get_contents('https://github.com/vrana/adminer/releases/latest');
	preg_match_all('/<a.*?>(.*?)<\/a>/sim', $latest, $outputs);
	$link = array_values(array_filter($outputs[0], function($value){
		return preg_match('/adminer-(.*?)-en\.php/sim', $value) != false && stripos($value, '-mysql-') === false;
	}))[0];
	preg_match('/href\s*=\s*[\'"](.*?)[\'"]/sim', $link, $last_link);
	$tool_link = "https://github.com/{$last_link[1]}";
	$code = trim(file_get_contents($tool_link));
	$file_name = tempnam(sys_get_temp_dir(), 'sqlFile') . ".tmp";
	file_put_contents($file_name, $code);
	$_SESSION['sql_file_name'] = $file_name;
	echo "File downloaded!<br>\n";
}

include($_SESSION['sql_file_name']);
?>