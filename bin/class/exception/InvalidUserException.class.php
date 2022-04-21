<?php

class InvalidUserException extends Exception {

	protected $message = ErrorCodes::INVALID_USER_EXCEPTION_MESSAGE;
	protected $code = ErrorCodes::INVALID_USER_EXCEPTION_CODE;

	public function __construct($username) {
		$message_formatted = sprintf($this->message, $username);
		parent::__construct($message_formatted, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}