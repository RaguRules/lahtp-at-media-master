<?
${basename(__FILE__, '.php')} = function (){
	if(isset($this->_request['folder'])){
		if(!file_exists(Constants::STORAGE . $this->_request['folder'])){
			$result = mkdir(Constants::STORAGE.$this->_request['folder']);
		} else {
			$result = "Already exists";
		}
		$this->response($this->packResonse(Constants::OK, [
			"status" => $result,
		]), 200);
	} else {
		$this->response($this->packResonse(Constants::ERROR, [
			"reason" => "Insufficient Data",
		]), 401);
	}
};