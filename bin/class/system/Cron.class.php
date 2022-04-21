<?

class Cron {

	const JOBS = [

	];

	public static function invoke(){
		$canRun = false;
		$db = DatabaseConnection::getDefaultDatabase();
		foreach (Cron::JOBS as $job) {
			if($jobDescription = Cache::get($job['worker'].'.cron')){
				if($jobDescription['lastInvoke'] < time()-$job['interval']){
					$canRun = true;
					$jobDescription = array(
						"lastInvoke" => time()
					);
					Cache::set($job['worker'].'.cron', $jobDescription);
				}
			} else {
				$canRun = true;
				$jobDescription = array(
					"lastInvoke" => time()
				);
				Cache::set($job['worker'].'.cron', $jobDescription);
			}
			if($canRun){
				$db->cron->insertOne([
						'job' => $job['worker'],
						'lastInvoke' => time()
					]);
				$helpWorker = new Worker($job['worker']);
				$helpWorker->invoke();
			}
		}
	}
}



