<?
${basename(__FILE__, '.php')} = function (){
	if(isset($this->_request['folder'])){
		try {
			$dir = Constants::STORAGE.$this->_request['folder'];
			$it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new RecursiveIteratorIterator($it,
			             RecursiveIteratorIterator::CHILD_FIRST);
			foreach($files as $file) {
			    if ($file->isDir()){
			        rmdir($file->getRealPath());
			    } else {
			        unlink($file->getRealPath());
			    }
			}

			$this->response($this->packResonse(Constants::OK, [
				"status" => rmdir($dir),
			]), 200);
		} catch (UnexpectedValueException $e){
			$this->response($this->packResonse(Constants::OK, [
				"status" => false,
			]), 200);
		}
	}
};