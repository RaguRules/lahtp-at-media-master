<?php

/**
 * This class provide CSRF protection for each page load and request origin from the loaded page.
 * For more details on CSRF protection: https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)
 *
 * A token is generated which acts as authentic identification for any request origning from the page generated.
 * On local environments, they can be escaped with the help of "local" as the csrf-token.
 *
 * On construction, this checks the validity of the token matched against the token in database. If invalid, InvalidRequestException is thrown. Else, it will simply do nothing, thus allowing the execution of the script.
 */
class RequestAuthorization {
	private $database = null;
	private $pageViewsCollection = null;
	public $method = "BasicAuth";

	/**
	 * Consturcts the class and throws InvalidRequestException if some error is found.
	 * @param String $csrf_token
	 */
	function __construct($csrf_token){
		$csrf_token = str_replace("Basic ", '', $csrf_token);
		$csrf_token = base64_decode($csrf_token);
		$auth = explode(':', $csrf_token);
		if($auth[0] == Constants::USERNAME and $auth[1] == Constants::PASSWORD) {

		} else {
			throw new InvalidRequestException();
		}
	}
}