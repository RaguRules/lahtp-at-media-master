<?php

class ClassNotConstructedForActionException extends Exception {

	protected $message = ErrorCodes::USER_NOT_AUTHORIZED_FOR_ACTION_EXCEPTION_MESSAGE;
	protected $code = ErrorCodes::USER_NOT_AUTHORIZED_FOR_ACTION_EXCEPTION_CODE;
	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}