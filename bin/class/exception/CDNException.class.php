<?php

/*
 * Generated by Exception Generator
 * @branch sibi
 * @user sibi
 * @date August 16th, 2017 22:23:05
 */

class CDNStorageAPIException extends Exception {

	protected $message = "There's some issue with the API call.";
	protected $code = 1030;
	protected $add = null;
	public function __construct($curl) {
		$this->add = $curl;
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: [$this->add] {$this->message}\n";
	}

}