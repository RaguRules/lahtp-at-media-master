<?
${basename(__FILE__, '.php')} = function () {
	if (isset($this->_request['pid'])) {
		$p = new Process();
		$p->setPid($this->_request['pid']);
		$this->response($this->packResonse(Constants::OK, [
			"status" => $p->isAlive(),
		]), 200);
	} else {
		$this->response($this->packResonse(Constants::ERROR, [
			"reason" => "Insufficient Data",
		]), 401);
	}
};