<?php

/*
* Generated by Exception Generator 
* @branch sibi
* @user sibi
* @date August 16th, 2017 18:01:23
*/

class MediaRegistrationException extends Exception {

	protected $message = "The media you are trying to register is already registered.";
	protected $code = 1028;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}