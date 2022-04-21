<?
${basename(__FILE__, '.php')} = function () {
	if (isset($this->_request['folder']) and isset($_FILES['file'])) {
		if(!file_exists(Constants::STORAGE . $this->_request['folder'])){
			mkdir(Constants::STORAGE.$this->_request['folder']);
		}
		$move = move_uploaded_file($_FILES['file']['tmp_name'], Constants::STORAGE . $this->_request['folder'].'/video.mp4');
		$mi = '';
		if (file_exists('/usr/local/bin/mediainfo')) {
			$mi = '/usr/local/bin/mediainfo';
		} else if (file_exists('/usr/bin/mediainfo')) {
			$mi = '/usr/bin/mediainfo';
		} else {
			throw new EncoderUnavailableException('mediainfo');
		}
		$data = exec("$mi --Inform=\"Video;%Width%x%Height%\" ". Constants::STORAGE . $this->_request['folder'].'/video.mp4');
		if($data != "1920x1080"){
			$this->response($this->packResonse(Constants::ERROR, [
				"reason" => "Invalid file",
			]), 200);
			return;
		}

		if ($move) {
			try{
				$media = new Media($this->_request['folder']);
				$worker = new Worker('MultimediaHandler', array(
					'mid' => $media->getMediaID(),
					'file' => Constants::STORAGE . $this->_request['folder'].'/video.mp4',
				));
				$p = $worker->invoke();
				if(Session::$environment == "local"){
					$tmp_console = new Worker('DisplayConsole', [
						'tmp_file' => $p->getTempFile()
					]);
					$tmp_console->invoke();
				}
				$media->setProcessPID($p->getPid());
				$this->response($this->packResonse(Constants::OK, [
					"info" => "Process invoked successfully",
					"status" => 'success',
					"pid" => $p->getPid(),
					"temp" => $p->getTempFile(),
				]), 200);
			} catch (MediaResourceNotFoundException $e){
				$this->response($this->packResonse(Constants::ERROR, [
					"reason" => "Media file not registered.",
				]), 200);
			}
		} else {
			$this->response($this->packResonse(Constants::ERROR, [
				"reason" => "Unable to process the file",
			]), 200);
		}
	} else {
		$this->response($this->packResonse(Constants::ERROR, [
			"reason" => "Insufficient data",
		]), 200);
	}
};