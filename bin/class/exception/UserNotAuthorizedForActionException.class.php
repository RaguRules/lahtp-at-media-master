<?php

class UserNotAuthorizedForActionException extends Exception {

	protected $message = ErrorCodes::USER_NOT_AUTHORIZED_FOR_ACTION_EXCEPTION_MESSAGE;
	protected $code = ErrorCodes::USER_NOT_AUTHORIZED_FOR_ACTION_EXCEPTION_CODE;
	private $previlege;
	public function __construct($previlege='') {
		parent::__construct($this->message, $this->code);
		$this->previlege = $previlege;
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}

	public function getPrivilege(){
		return $this->previlege;
	}
}