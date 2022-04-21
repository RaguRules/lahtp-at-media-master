<?php

require_once __DIR__ . '/Rest.class.php';

class API extends REST {

	protected $start = null;
	protected $finish = null;
	private $methods = array();
	private $addlInfo = array();

	public function __construct() {
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$this->start = $time;
		parent::__construct();
	}

	/**
	 * Public method for access api. This method dynmically call the method based on the query string.
	 * @param  Array $methodsRegister List of methods to be processed
	 * @return null
	 */
	public function processApi($methodsRegister) {
		if (isset($_REQUEST['rquest'])) {
			if ($this->get_request_method() != "POST") {
				$this->response($this->packResonse(Constants::ERROR, [], "Bad Request", "The request is built with improper "), 406);
			}
			if (!empty(get_header('Authorization'))) {
				try {
					$this->addlInfo['requestData'] = $this->_request;
					$ra = new RequestAuthorization(get_header('Authorization'));
					$this->addlInfo['authMethod'] = $ra->method;
				} catch (InvalidRequestException $e) {
					$this->addlInfo['requestValidationResult'] = false;
					$this->response($this->packResonse(Constants::ERROR, ['status'=>'Request Expired'], "Unauthorized", "Request unauthorized. "), 401);
				}

			} else {
				$this->response($this->packResonse(Constants::ERROR, [], "Unauthorized", "Cannot process request due to security restirctions."), 403);
			}
			$ns = null;
			if(isset($_REQUEST['ns'])){
				$ns = strtolower(trim(str_replace("/", "", $_REQUEST['ns'])));
			}

			$func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
			if ((int) method_exists($this, $func) > 0 and $ns == null) {
				$this->$func();
			} else {
				$dir = "/../../api";
				if($ns){
					$dir = $dir.'/'.$ns;
				}
				$ns_file = __DIR__.$dir.'/'.$func.'.php';
				if(file_exists($ns_file)){
					include $ns_file;
					$this->methods[$func] = \Closure::bind(${$func}, $this, get_class());
					$this->$func();
				} else {
					try {
						$dir = new RecursiveDirectoryIterator(__DIR__ . $dir);
						$iterator = new RecursiveIteratorIterator($dir);
						foreach ($iterator as $file) {
							$fname = $file->getFilename();
							if (preg_match('%\.api.php$%', $fname)) { //If ends with .api.php it will read from anywhere if not using namespace (irrespective of directory under /bin/api/**(/).api.php) - all namespaced files must end with .php with the designated format.
								include $file->getPathname();
							}
						}
					} catch (UnexpectedValueException $e){
						$this->response($this->packResonse(Constants::ERROR, [], "Bad Request", "Bad API namespace."), 500);
					}
					foreach ($methodsRegister as $method) {
						$this->methods[$method] = \Closure::bind(${$method}, $this, get_class());
					}
					$this->$func();
				}
			}
		} else {
			$this->response($this->packResonse(Constants::ERROR, [], "Bad Request", "Improper request format."), 500);
		}
	}

	function __call($method, $args) {
		if(!isset($this->methods[$method])){
			$this->response($this->packResonse(Constants::ERROR, [], "Invalid API Call", "The method has not been registered with us."), 502);
		}
		if (is_callable($this->methods[$method])) {
			$var = call_user_func_array($this->methods[$method], $args);
			return $var;
		} else {
			$this->response($this->packResonse(Constants::ERROR, [], "Invalid API Call", "The method has not been registered with us."), 502);
		}
	}

	/*************API SPACE START*******************/
	//API methods can also be written here as a private method.

	/*************API SPACE END*********************/

	/**
	 * Internal method for converting Array into JSON
	 * @param  Array $data
	 * @return null
	 */
	private function json($data) {
		if (is_array($data)) {
			return json_encode($data, JSON_PRETTY_PRINT);
		}
	}

	/**
	 * This method helps to pack the response into final transmittable packet with all the informations packed.
	 * @param  String $status  Constants::OK || Constants::ERROR
	 * @param  Array  $payload The payload which has to be sent via the response
	 * @return JSON          The final json as transmittable packet.
	 */
	private function packResonse($status, $payload = array(), $dialogTitle=null, $dialogMessage=null) {
		$payload = WebAPI::purifyArray($payload);
		$pack = array(
			'status' => $status,
			//'additionalInfo' => $this->addlInfo,
		);

		if (Session::$environment == "local") {
			if (!empty(Session::$localPack)) {
				Session::$localPack['message'] = Session::$localPack['message'] . "<hr><kbd>local emulation only</kbd>";
				$pack['localDialog'] = Session::$localPack;
			}
		}

		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$this->finish = $time;
		$total_time = round(($this->finish - $this->start), 6);
		$pack['loadTime'] = $total_time;
		$pack['payload'] = $payload;
		return $this->json($pack);
	}

}