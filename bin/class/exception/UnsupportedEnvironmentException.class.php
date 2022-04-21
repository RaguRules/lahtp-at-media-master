<?php

/*
* Generated by Exception Generator 
* @branch sibi
* @user sibi
* @date August 16th, 2017 12:15:19
*/

class UnsupportedEnvironmentException extends Exception {

	protected $message = "The resource you are trying to access is unsupported in this environment or restricted.";
	protected $code = 1021;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}