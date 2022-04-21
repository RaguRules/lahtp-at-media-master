<?php


/**
 * QuickGit is used to get the version information of the current git build. This plays a vital role in purging client side caches while upgrading or pushing a new version of the web application.
 */

class QuickGit {
	private $version;
	
	/**
	 * Get the executing environment ($build) and generates the readable form of version string for the current instance of the WebApp.
	 * @param String $build Environment Description.
	 */
	function __construct($build) {
		// exec('git describe --always',$version_mini_hash);
		//exec('git rev-list HEAD | wc -l',$version_number);
		// exec('git log -1',$line);
		global $gitVersion;
		$this->version['short'] = $build.".".Constants::MAJOR_VERSION.$gitVersion;
		// $this->version['full'] = $build.".".Constants::MAJOR_VERSION.".".trim($version_number[0]).".$version_mini_hash[0] (".str_replace('commit ','',$line[0]).") - ".trim($line[4]);
		//$this->version['desc'] = trim($line[4]);
	}

	/**
	 * Short version of the current instance
	 * @return String
	 */
	public function getShort() {
		return $this->version['short'];
	}

	/**
	 * Full version of the current instance
	 * @return String
	 */
	// public function getFull(){
	// 	return $this->version['full'];
	// }

	// /**
	//  * Returns the commit description of the latest commit. 
	//  * @return String
	//  */
	// public function getDescription(){
	// 	return htmlentities($this->version['desc']);
	// }
}
