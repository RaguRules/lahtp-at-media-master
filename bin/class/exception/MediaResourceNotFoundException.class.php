<?php

class MediaResourceNotFoundException extends Exception {

	protected $message = ErrorCodes::MEDIA_NOT_FOUND_EXCEPTION;
	protected $code = ErrorCodes::MEDIA_NOT_FOUND_EXCEPTION_CODE;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}