<?
${basename(__FILE__, '.php')} = function (){
	$info['service_domain'] = Session::$environment == "local" ? "medialocal.aftertutor.com" : "media.aftertutor.com";
	$info['storage']['hostname'] = Session::$environment == "local" ? "medialocal.aftertutor.com" : "upload.aftertutor.com";
	$info['storage']['username'] = Constants::USERNAME;
	$info['storage']['password'] = Constants::PASSWORD;
	$info['storage']['usage'] = disk_total_space('/') - disk_free_space('/');
	$info['storage']['total_space'] = disk_total_space('/');
	$info['storage']['free_space'] = disk_free_space('/');
	$info['updated_on'] = time();

	$this->response($this->packResonse(Constants::OK, [
		$info
	]), 200);
};