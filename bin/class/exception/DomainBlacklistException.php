<?php

class DomainBlacklistException extends Exception {

	protected $message = ErrorCodes::DOMAIN_BLACKLIST_MESSAGE;
	protected $code = ErrorCodes::DOMAIN_BLACKLIST_CODE;

	public function __construct() {
		parent::__construct($this->message, $this->code);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

}
