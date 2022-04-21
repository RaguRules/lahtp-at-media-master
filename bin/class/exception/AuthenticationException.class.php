<?php

class AuthenticationException extends Exception {

	protected $message = ErrorCodes::AUTHENTICATION_EXCEPTION_MESSAGE;
	protected $code = ErrorCodes::AUTHENTICATION_EXCEPTION_CODE;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}