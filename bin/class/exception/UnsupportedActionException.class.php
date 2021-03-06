<?php

/*
* Generated by Exception Generator
* @branch sibi
* @user sibi
* @date August 28th, 2017 13:22:57
*/

class UnsupportedActionException extends Exception {

	protected $message = "The action called is not supported yet.";
	protected $code = 5014;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}