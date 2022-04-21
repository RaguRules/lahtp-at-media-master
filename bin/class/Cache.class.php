<?php 

class Cache {
	public static function set($key, $val) {
		if(is_object($val)){
			throw new ObjectNotSupportedException;
		}
		$val = var_export($val, true);
		// HHVM fails at __set_state, so just use object cast for now
		$val = str_replace('stdClass::__set_state', '(object)', $val);
		// Write to temp file first to ensure atomicity
		$tmp = __DIR__."/../../garbage/$key." . md5(uniqid('', true)) . '.tmp';
		file_put_contents($tmp, '<?php $val = ' . $val . ';', LOCK_EX);
		rename($tmp, __DIR__."/../../cache/$key");
	}

	public static function get($key) {
		if(file_exists(__DIR__."/../../cache/$key")){
			@include __DIR__."/../../cache/$key";
			return isset($val) ? $val : false;
		} else {
			return false;
		}
	}

	public static function clear($key){
		unlink(__DIR__."/../../cache/$key");
	}

}