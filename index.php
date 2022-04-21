<?php
require_once 'bin/load.php';
ini_set("memory_limit", "-1");
header('Access-Control-Allow-Origin: *');

if(!isset($_GET['path'])){
	die ();
}

if($_GET['path'] == ''){
	die ();
}

$path = $_GET['path'];
if(WebAPI::startsWith($path, '/')){
	$path = ltrim($path, '/');
}

if($_SERVER['HTTP_HOST'] != 'medialocal.aftertutor.com' and $_SERVER['REMOTE_ADDR'] != "127.0.0.1"){
	if(!Session::checkIP($_SERVER['REMOTE_ADDR'])){
		http_response_code(403);
		@header("Content-type: application/json");
		die("{'message': 'Unauthorized IP: {$_SERVER['REMOTE_ADDR']}', 'status': 403}");
	}
}

$path = Constants::STORAGE.$path;
$pathinfo = pathinfo($path);
global $mime_types;
if(!isset($pathinfo['extension']) or !file_exists($path)){
	http_response_code(404);
	@header("Content-type: application/json");
	die("{'message': 'File not found', 'status': 404}");
} else {
	@header("Content-type: ".$mime_types[$pathinfo['extension']]);
	echo file_get_contents($path);
}


/*

ffmpeg -i 1080.mp4 -profile:v baseline -level 3.0 -start_number 0 -hls_time 10 -hls_list_size 0 -f hls 1080p.m3u8 && ffmpeg -i 720.mp4 -profile:v baseline -level 3.0 -start_number 0 -hls_time 10 -hls_list_size 0 -f hls 720p.m3u8 && ffmpeg -i 480.mp4 -profile:v baseline -level 3.0 -start_number 0 -hls_time 10 -hls_list_size 0 -f hls 480p.m3u8 && rm 1080.mp4 && rm 720.mp4 && rm 480.mp4
 */
