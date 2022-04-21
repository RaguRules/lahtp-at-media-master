<?
${basename(__FILE__, '.php')} = function(){
	if(isset($this->_request['folder'])){
		$dir = Constants::STORAGE;
		$result = array();
		// Open a directory, and read its contents
		if (is_dir($dir)){
			if ($dh = opendir($dir)){
				while (($file = readdir($dh)) !== false){
					if(!WebAPI::startsWith($file, '.')){
						array_push($result, $file);
					}
				}
				closedir($dh);
			}
		}

		$this->response($this->packResonse(Constants::OK, [
			"contains" => in_array($this->_request['folder'], $result),
		]), 200);

	} else {
		$this->response($this->packResonse(Constants::ERROR, [
			"reason" => "Insufficient Data",
		], "Insufficient Data", "Unable to process request"), 401);
	}
};