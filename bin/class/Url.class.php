<?php
/**
 * Url class for handling URLs inside the app.
 * This class can be used internally for the API and also for the UI part.
 */
class Url {
	private $urlPath = null;
	private $shortner = Constants::SHORTENER;
	private $apiKey = Constants::SHORTENER_API_KEY;
	private $canShorten = false;

	/**
	 * Constructs the URL by taking a URI aka URL Path.
	 * @param String $urlPath Resource Identifer
	 */
	function __construct($urlPath) {
		$this->urlPath = ltrim(trim($urlPath), '/');
		$wildcard = false;
		// if (Session::getUserSession() != null) {
		// 	$wildcard = Session::getUser()->isSuperUser();
		// }
		if (strpos($this->urlPath, "https://aftertutor.com") === 0 or strpos($this->urlPath, "http://aftertutor.com") === 0 or $wildcard) {
			$absoluteUrl = $this->urlPath;
			$this->canShorten = true;
		} else {
			$this->canShorten = false;
		}
	}

	/**
	 * Appends URL parameter to the URL
	 * @param  String $key   The Parameter
	 * @param  String $value Value foe the parameter
	 * @return NULL
	 */
	function append($key, $value) {
		if (strpos($this->urlPath, '?') !== false) {
			$this->urlPath = $this->urlPath . '&' . $key . '=' . urlencode($value);
		} else {
			$this->urlPath = $this->urlPath . '?' . $key . '=' . urlencode($value);
		}
	}

	/**
	 * Returns absoulute URL for the constructed path with the scheme. If $serverName is present, it will use the given server name instead of the original server name. Server name can be an IP or Domain Name.
	 * @param  String $serverName Default is false. But a String is welcomed.
	 * @return String
	 */
	function getAbsoluteUrl($serverName = false) {
		if (Session::get('mode') != 'web') {
			$_SERVER['REQUEST_SCHEME'] = 'http';
			$_SERVER['SERVER_NAME'] = 'aftertutor.com';
		}
		if ($serverName == false) {
			$absoluteUrl = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . "/" . $this->urlPath;
		} else {
			$absoluteUrl = $_SERVER['REQUEST_SCHEME'] . "://" . $serverName . "/" . $this->urlPath;
		}

		if (strpos($this->urlPath, "https://") === 0 or strpos($this->urlPath, "http://") === 0) {
			$absoluteUrl = $this->urlPath;
		}
		return $absoluteUrl;
	}

	/**
	 * Returns the canonical URL without the schema.
	 * @return String
	 */
	function getCanonicalUrl() {
		$canonicalUrl = "//" . $_SERVER['SERVER_NAME'] . "/" . $this->urlPath;
		return $canonicalUrl;
	}

	/**
	 * Returns the relative URL (the Resource Identifier)
	 * @return String
	 */
	function getRelativeUrl() {
		return "/" . $this->urlPath;
	}

	function getUri() {
		return $this->urlPath;
	}

	/**
	 * getUrl is a mask for getAbsoluteUrl
	 * @return $string
	 */
	function getUrl() {
		return $this->getAbsoluteUrl();
	}

	/**
	 * Using the atut.me server powered by Polr, the method makes an API call to atut.me to create a short url for a long url.
	 * @param  String $customEnding Can be a string if custom ending is required. If not random ending will be generated.
	 * @return String
	 */
	function shorten($customEnding = false) {
		if ($this->canShorten) {
			$url = $this->shortner . "?key=" . $this->apiKey . "&url=" . urlencode($this->getAbsoluteUrl());
			if ($customEnding != false && $customEnding != "" && strlen($customEnding) > 0) {
				$url = $url . "&custom_ending=$customEnding";
			}
			$url = $url . "&response_type=json";
			$response = file_get_contents($url);
			$response = json_decode($response, true);
			if (isset($response['result'])) {
				return $response['result'];
			} else {
				throw new Exception('Unknown URL Shortener error');
			}

		} else {
			throw new DomainBlacklistException();
		}

	}

	public static function exists($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($code == 200) {
			$status = true;
		} else {
			$status = false;
		}
		curl_close($ch);
		return $status;
	}

	function __toString() {
		return $this->getAbsoluteUrl();
	}
}