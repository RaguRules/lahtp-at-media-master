<?php

class Worker {
	private $path = __DIR__ . "/../../worker/";
	private $sessionData = array();
	private $arg1;
	private $worker = null;
	private $name = null;

	function __construct($worker, $work = array()) {
		if (!file_exists($this->path . $worker . ".worker.php")) {
			throw new WorkerNotFoundException;
		} else {
			$this->name = $worker;
			$this->worker = $this->path . $worker . ".worker.php";
			$this->sessionData['username'] = isset($_COOKIE['username']) ? $_COOKIE['username'] : null;;
			$this->sessionData['sessionHash'] = isset($_COOKIE['sessionHash']) ? $_COOKIE['sessionHash'] : null;
			$this->sessionData['sessionID'] = isset($_COOKIE['sessionID']) ? $_COOKIE['sessionID'] : null;

			$json = json_encode($this->sessionData);
			$this->arg1 = base64_encode($json);
			$this->arg2 = base64_encode(json_encode($work));
		}
	}

	function invoke() {
		$cmd = Session::get('php') . ' ' . $this->worker . " auth=" . $this->arg1 . " work=" . $this->arg2;
		return new Process($cmd, false, $this->name.'-'.substr(md5(Session::generatePesudoRandomHash(4)), 7));
	}
}