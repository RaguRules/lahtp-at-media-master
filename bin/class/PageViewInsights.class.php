<?php

class PageViewInsights {

	/**
	 * For every page view, a CSRF token is generated. It happens with the help of this method. All the necessary data are updated to the Session variables.
	 * @return Array 
	 */
	public static function getInfo(){
		at_error_log("Preparing insights... ".getCurrentRenderTime(), "api");
		if($_SERVER['PHP_SELF'] != '/api.php'){
			$csrfToken = base64_encode(openssl_random_pseudo_bytes(64));
			Session::$csrfToken = $csrfToken;
		} 
		
		$array = array(
			'user' => Session::getUser(),
			'isValid' => true,
			'validTill' => (time()+(1800)),
			'version' => Session::$version,
			'request' => $_SERVER
		);

		if(isset($csrfToken)){
			$array['csrfToken'] = $csrfToken;
		}
		at_error_log("Done with insights... ".getCurrentRenderTime(), "api");
		return $array;
	}
}