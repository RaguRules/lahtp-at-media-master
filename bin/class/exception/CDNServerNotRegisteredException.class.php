<?php

/*
* Generated by Exception Generator
* @branch praveen-designs
* @user harman
* @date August 23rd, 2017 03:52:16
*/

class CDNServerNotRegisteredException extends Exception {

	protected $message = "Consider running CDNServer::refreshInfo() code before constructing the CDNServer.";
	protected $code = 5002;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}