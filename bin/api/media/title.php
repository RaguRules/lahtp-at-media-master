<?
${basename(__FILE__, '.php')} = function(){
	if (isset($this->_request['mid'])) {
		$m = new Media($this->_request['mid']);
		$this->response($this->packResonse(Constants::OK, [
			"title" => $m->getTitle(),
		]), 200);
	} else {
		$this->response($this->packResonse(Constants::ERROR, [
			"reason" => "Insufficient Data",
		]), 401);
	}
};