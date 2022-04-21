<?
${basename(__FILE__, '.php')} = function (){
	$dir = Constants::STORAGE;
	$result = array();
	// Open a directory, and read its contents
	if (is_dir($dir)){
		if ($dh = opendir($dir)){
			while (($file = readdir($dh)) !== false){
				if(!WebAPI::startsWith($file, '.') and !WebAPI::startsWith($file, 'secure.key')){
					array_push($result, $file);
				}
			}
			closedir($dh);
		}
	}
	$this->response($this->packResonse(Constants::OK, [
		"list" => $result
	]), 200);
};