<?
${basename(__FILE__, '.php')} = function () {
	if (isset($this->_request['name']) and isset($_FILES['file'])) {
		$tmp_name = md5($this->_request['name'].time());
		$name = $this->_request['name'];
		mkdir('/tmp/'.$tmp_name);
		$fname = preg_replace('/[^A-Za-z0-9\. -]/', '', $_FILES['file']['name']);
		$file_ext = strtolower(end(explode('.',$fname)));
		$extensions = array("ppt","pptx","odp", "ppa", "pdf", "pot", "potx", "pps", "ppsx");
		if(in_array($file_ext,$extensions) === false){
			$this->response($this->packResonse(Constants::ERROR, [
				"reason" => "invalid_file",
				'ext' => $file_ext
			]), 200);
			return;
		}
		$move = move_uploaded_file($_FILES['file']['tmp_name'],  '/tmp/'.$tmp_name.'/'.$fname);
		if ($move) {
			$worker = new Worker('PresentationProcessor', array(
				'name' => $name,
				'pid' => $this->_request['pid'],
				'file' => '/tmp/'.$tmp_name.'/'.$fname,
				'output' => '/tmp/'.$tmp_name.'/',
				'is_pdf' => $file_ext == 'pdf',
				'env' => Session::$environment
			));
			$p = $worker->invoke();
			if(Session::$environment == "local"){
				$tmp_console = new Worker('DisplayConsole', [
					'tmp_file' => $p->getTempFile()
				]);
				$tmp_console->invoke();
			}
			$this->response($this->packResonse(Constants::OK, [
				"info" => "Process invoked successfully",
				"status" => 'success',
				"pid" => $p->getPid(),
				"temp" => $p->getTempFile(),
			]), 200);
		} else {
			$this->response($this->packResonse(Constants::ERROR, [
				"reason" => "process_failure",
			]), 200);
		}
	} else {
		$this->response($this->packResonse(Constants::ERROR, [
			"reason" => "Insufficient data",
		]), 200);
	}
};