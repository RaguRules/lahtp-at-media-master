<?php
exec('git describe --always', $version_mini_hash);
global $gitVersion;
$gitVersion = $version_mini_hash[0];
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
global $__start;
$__start = $time;
date_default_timezone_set('Asia/Kolkata');
$_SERVER['UNIQUE_ID'] = uniqidReal();
/**
 * Scans for a directory and includes all the php files in it, and writes a list as json to __DIR__.'/../../../../../requires.json' which can be inclided without iterating everytime.
 * @param  String $path Directory
 * @param  boolean $generateRequires Whether it needs to generate the requires file.
 */
function renderRequires($path, $generateRequires = true) {
	global $gitVersion;
	$requires = array("version" => $gitVersion);
	$paths = array();
	$dir = new RecursiveDirectoryIterator($path);
	$iterator = new RecursiveIteratorIterator($dir);
	foreach ($iterator as $file) {
		$fname = $file->getFilename();
		if (preg_match('%\.php$%', $fname)) {
			require_once $file->getPathname();
			array_push($paths, $file->getPathname());
		}
	}

	$requires["path"] = $paths;

	if ($generateRequires) {
		Cache::set('includes.cache', $requires);
	}
}

function moment($time){
	return (new \Moment\Moment($time))->fromNow()->getRelative();
}

/**
 * For optimized perofrmance, all the php includes are happened from a json file, which has the list of all the files to be included. This list is generated based on the git version.
 * @param  [type] $json [description]
 * @return [type]       [description]
 */
require_once __DIR__.'/Cache.class.php';
function requireFromJson() {
	global $gitVersion;
	$data = Cache::get('includes.cache');
	if(empty($data)){
		renderRequires(__DIR__);
	} else {
		at_error_log("Trying to include from cache ".getCurrentRenderTime(), "api");
	}

	if (isset($data['version']) and $data['version'] == $gitVersion) {
		foreach ($data["path"] as $path) {
			require_once $path;
		}
		at_error_log("Finished including ".getCurrentRenderTime(), "api");
	} else {
		at_error_log("Version mismatch of $data[version] against $gitVersion.. Performing regular include. ".getCurrentRenderTime(), "api");
		renderRequires(__DIR__);
	}
}
requireFromJson();

/**
 * This method writes a log to at-web_error.log file located outside the reporitory.
 * @param  String $log The log message
 * @param  String $tag Tag the log message, Default is "system"
 * @return null
 */
function at_error_log($log, $tag = "system") {
	if(!isset($_SERVER['REQUEST_URI'])){
		$_SERVER['REQUEST_URI'] = 'cli';
	}

	$haystack = $_SERVER['PHP_SELF'];
	$needle = 'worker.php';
	$length = strlen($needle);
	if(substr($haystack, -$length) === $needle){
		$_SERVER['REQUEST_URI'] = 'worker:'.basename($_SERVER['PHP_SELF']);
	}
	if($tag == "system"){
		$date = date('l jS F Y h:i:s A');
		error_log($tag . ': [' . $date . '] '.$_SERVER['REQUEST_URI'] .": ". $log . "\n", 3, __DIR__ . '/../../../../at-web_error.log');
	}
}

/**
 * For any given URI/resource, a full cache CDN URL will be returned for appropriate environment.
 * @param  String $uri Resource locater
 * @return String      Returns the cache CDN URL (Absolute)
 */

$WebAPI = new WebAPI();
DatabaseConnection::$client = $WebAPI->getDatabaseClient();


if(!file_exists(Constants::STORAGE)){
	if(!mkdir(Constants::STORAGE)){
		die('Unable to create/access storage');
	}
}
/*
Begin the cron job with every request. Detached processes.
 */
Cron::invoke();

if (System::getOS() <= 2) {
	throw new UnsupportedEnvironmentException;
}
//Session::set('requires-by', $requiresFrom);
Session::set('page-render-start', $__start);
Session::set('currentUri', $_SERVER['REQUEST_URI']);
$self = $_SERVER['PHP_SELF'];
$self = str_replace('.php', '', $self);
Session::set('self', $self);
at_error_log("Application Begins ".getCurrentRenderTime(), "api");
if (php_sapi_name() == "cli") {
	if (WebAPI::endsWith($_SERVER['PHP_SELF'], 'worker.php')) {
		Session::set('mode', 'worker');
		parse_str(implode('&', array_slice($argv, 1)), $_GET);
		if(isset($_GET['work'])){
			$work = $_GET['work'];
			$work = json_decode(base64_decode($work), true);
			if(is_array($work)){
				$_GET = array_merge($_GET, $work);
			}
		}
	} else {
		Session::set('mode', 'cli');
	}
} else {
	Session::set('mode', 'api');
}



/**
 * Generate a UniqueID fror Request Identification
 * @param  integer $lenght
 * @return string
 */
function uniqidReal($lenght = 13) {
	// uniqid gives 13 chars, but you could adjust it to your needs.
	if (function_exists("random_bytes")) {
		$bytes = random_bytes(ceil($lenght / 2));
	} elseif (function_exists("openssl_random_pseudo_bytes")) {
		$bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
	} else {
		throw new Exception("no cryptographically secure random function available");
	}
	return substr(bin2hex($bytes), 0, $lenght);
}

function getCurrentRenderTime() {
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	global $__start;
	$total_time = number_format(($finish - $__start), 4);
	return $total_time;
}

function get_header($h){
	$headers = apache_request_headers();
	foreach ($headers as $header => $value) {
	    if($header == $h){
	    	return $value;
	    }
	}
	return null;
}

function strip_quotes($value){
    $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

    return str_replace($search, $replace, $value);
}

function get_all_methods($class){
	$functionArray = array();
	$parent = get_parent_class($class);
	if(!empty($parent)){
		$functionArray = array_merge($functionArray, get_all_methods($parent));
	}
	try{
		$reflector = new ReflectionClass($class);
		$functionFinder = '/function[\s\n]+(\S+)[\s\n]*\(/';
		$fnArray = array();
		$fileContents = file_get_contents($reflector->getFileName());
		preg_match_all( $functionFinder , $fileContents , $fnArray );

		if( count( $fnArray )>1 ){
			$functionArray = array_merge($functionArray, $fnArray[1]);
		}
		return $functionArray;
	} catch (ReflectionException $e){
		return array();
	}
}
