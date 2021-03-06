<?php

/*
* Generated by Exception Generator 
* @branch sibi
* @user sibi
* @date August 16th, 2017 17:58:16
*/

class MediaNotRegisteredException extends Exception {

	protected $message = "The media entities you are trying to access is incomplete.";
	protected $code = 1027;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}