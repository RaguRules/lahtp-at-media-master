<?php

class CheckPrivilege {
	public static function access($class, $method){
		$passiveDb = DatabaseConnection::getPassiveDatabase();
		$collection = $passiveDb->privileges;
		$result = $collection->findOne([
				'class' => $class,
				'method' => $method,
				'status' => true
		]);
		if(isset($result['score'])){
			settype($result['score'], "integer");
			if(Session::getAuthStatus() == Constants::STATUS_DEFAULT){
				throw new UserNotAuthorizedForActionException('Please login or signup to interact.');
			}
			$score = Session::getUser()->getPoints();
			if($result['score'] <= $score){
				return true;
			} else {
				throw new UserNotAuthorizedForActionException($result['description']);
			}
		} else { //allow action if not specifically mentioned
			return true;
		}
	}
}