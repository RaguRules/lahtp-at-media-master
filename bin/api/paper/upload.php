<?
${basename(__FILE__, '.php')} = function () {
	if(isset($this->_request['title']) and isset($this->_request['abstract']) and isset($this->_request['tags']) and isset( $_FILES['paper'])){
		$paper = new Paper();
		$paper->setPaper($this->_request['title'],$this->_request['abstract'],json_decode($this->_request['tags']),Session::getUser()->getUserName(),$this->_request['authors']);
		$pid = $paper->getPaperID();
		$pname = preg_replace('/[^A-Za-z0-9\. -]/', '', $_FILES['paper']['name']);
		$tmp_pname = md5($pname.time());
		mkdir('/tmp/'.$tmp_pname);
		$totemp = move_uploaded_file($_FILES['paper']['tmp_name'],  '/tmp/'.$tmp_pname.'/'.$pname);
		$result = $paper->uploadPaper('/tmp/'.$tmp_pname.'/'.$pname);
	}
	else {
		$this->response($this->packResonse(Constants::ERROR, [
			"reason" => "Insufficient data",
		]), 500);
	}
	if (isset($_FILES['ppt'])) {
		$fname = preg_replace('/[^A-Za-z0-9\. -]/', '', $_FILES['ppt']['name']);
		$tmp_name = md5($fname.time());
		$fnamesplit = explode('.',$fname);
		$file_ext = strtolower(end($fnamesplit));
		$extensions = array("ppt","pptx","odp", "ppa", "pdf", "pot", "potx", "pps", "ppsx");
		if(in_array($file_ext,$extensions) === false){
			$this->response($this->packResonse(Constants::ERROR, [
				"reason" => "invalid_file",
				'ext' => $file_ext
			]), 200);
			return;
		}		$move = move_uploaded_file($_FILES['ppt']['tmp_name'],  '/tmp/'.$tmp_name.'/'.$fname);
		if ($move) {
			$worker = new Worker('PresentationProcessor', array(
				'pid' => $pid,
				'file' => '/tmp/'.$tmp_name.'/'.$fname,
				'output' => '/tmp/'.$tmp_name.'/',
				'is_pdf' => $file_ext == 'pdf',
				'env' => Session::$environment
			));
			$p = $worker->invoke();
			// if(Session::$environment == "local"){
			// 	$tmp_console = new Worker('DisplayConsole', [
			// 		'tmp_file' => $p->getTempFile()
			// 	]);
			// 	$tmp_console->invoke();
			// }
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