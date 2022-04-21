<?php

class Media {

	const STATUS_PUBLISHED = 'published';
	const STATUS_DRAFT = 'draft';
	const STATUS_BETA = 'beta';

	private $key = null;
	private $allow = array();
	private $deny = array();
	private $expire = null;

	/**
	 * The original database client vairable fetched from the DatabaseConnection::getDefaultDatabase()
	 * @var MongoDB\Client
	 */
	private $database = null;

	/**
	 * The collection object for the Media with which the calls are made.
	 * @var MongoDB\Collection
	 */
	private $collection = null;

	/**
	 * The array fetched from the collection (database query result)
	 * @var Array
	 */
	private $media = null;

	/**
	 * Creation mode flag. If set, the tag can create contents.
	 * @var boolean
	 */
	private $creationMode = false;

	/**
	 * id representing the current media file, stored in the CDN.
	 * @var null
	 */
	private $id = null;

	private $registration = false;

	function __call($method, $args) {
		if(!method_exists($this, $method)) {
			throw new Exception('Unreachable method: '.$method);
		}
		CheckPrivilege::access(__CLASS__, $method);
		$result = call_user_func_array(__CLASS__."::".$method, $args);
		$event = new Event(__CLASS__, $method, $result, $this);
		return $result;
	}

	function __construct($id = null) {
		$this->database = DatabaseConnection::getWebDatabase();
		$this->collection = $this->database->media;
		//Creation Mode
		if ($id == null) {
			if (!Session::getUser()->isContentAdmin()) {
				throw new UserNotAuthorizedForActionException;
			}
			$this->id = md5(Session::generatePesudoRandomHash(24));
			$this->creationMode = true;
			$this->media = $mediaContent = array(
				"_id" => $this->id,
				"createdOn" => time(),
				"updatedOn" => time(),
				"createdBy" => Session::getUser()->getUsername(),
				"status" => Media::STATUS_DRAFT,
				"registration" => false,
				"available" => false,
				"logs" => [],
				"build" => [
					"state" => "Yet to begin...",
					"progress" => 0.00,
				]
			);
			$this->collection->insertOne($mediaContent);

		} else {
			$this->id = $id;
			$this->media = $this->collection->findOne(
				array('$or' => [
					[
						"_id" => $id,
					],
					[
						'mid' => $id,
					],
				])
			);
			$this->id = $this->media['_id'];
			$this->registration = $this->media['registration'];
			if (count(WebAPI::purifyArray($this->media)) < 1) {
				throw new MediaResourceNotFoundException($this->id);
			}
		}
	}

	/**
	 * This method registeres the media into the database with the respective tags given as array.
	 * @param  String $title        Title for the media content
	 * @param  String $description  Description for the media content to be registered.
	 * @param  Array[Tag] $tags         Array of Tag objects
	 * @param  Url                   Pointing to Bucket
	 * @return $string               mid of the inserted object.
	 */
	function register($title, $description, $tags, $coverpic, $comingsoon) {

		if ($this->registration) {
			throw new MediaRegistrationException;
		}

		if (!Session::getUser()->isContentAdmin()) {
			throw new UserNotAuthorizedForActionException;
		}

		$mid = implode('-', explode(' ', strtolower($title)));
		$mid = preg_replace("/[^a-z0-9-]/", '', $mid);

		//Get the latest title sorted by time, and get the mid set for it. With that, the next in queue mid can be predicted or generated and can be processed accordingly.
		$check = $this->collection->find(['title' => $title], [
			'sort' => ['createdOn' => -1],
			'limit' => 1,
		]);
		$chk1 = null;
		foreach ($check as $chk) {
			$chk1 = $chk;
		}
		$check = $chk1;
		if (!empty($check)) {
			$mid = $check['mid'];
			$varArray = explode('-', $mid);
			$last = $varArray[count($varArray) - 1];
			if (is_numeric($last)) {
				settype($last, "integer");
				$last++;
				$varArray[count($varArray) - 1] = $last;
			} else {
				$last = 0;
				$varArray[count($varArray)] = $last;
			}
			$mid = implode('-', $varArray);
		}

		$mediaTags = array();
		foreach ($tags as $tag) {
			if (get_class($tag) == "Tag") {
				array_push($mediaTags, $tag->getTag());
			} else {
				throw new ObjectNotSupportedException;
			}
		}
		$query = array(
			'_id' => $this->id,
			"createdBy" => Session::getUser()->getUsername(),
		);

		if(Session::$environment!= "prod"){
			$mid = Session::$environment."-".$mid;
		}

		$update = array(
			"mid" => $mid,
			"title" => $title,
			"tags" => $mediaTags,
			"description" => $description,
			"cover" => $coverpic,
			"registration" => true,
			"coming-soon" => $comingsoon,
		);

		$this->media = array_merge($this->media, $update);
		$this->collection->updateOne($query, ['$set' => $update]);
		$w = new Worker('CDNStorageInitialize', ['mid' => $mid]);
		$w->invoke();
		return $mid;
	}

	/**
	 * Returns the description of the media content
	 * @return String
	 */
	function getDescription() {
		if (isset($this->media['description'])) {
			return $this->media['description'];
		} else {
			throw new MediaResourceNotFoundException;
		}
	}

	/**
	 * Returns the tags of the media content
	 * @return String
	 */
	function getTags() {
		if (isset($this->media['tags'])) {
			return $this->media['tags'];
		} else {
			throw new MediaResourceNotFoundException;
		}
	}

	function getViewCount(){
		if(isset($this->media['views'])){
			return $this->media['views'];
		} else {
			return 0;
		}
	}

	function setViewCount($count){
		$this->media['views'] = $count;
		$this->collection->updateOne(
				[
					'_id'=>$this->getId()
				],
				[
					'$set'=> [
						'views' => $count
					]
				]
			);
	}

	function setTags($tags) {
		if(isset($tags)){
			$mediaTags = array();
			foreach ($tags as $tag) {
				if (get_class($tag) == "Tag") {
					array_push($mediaTags, $tag->getTag());
				} else {
					throw new ObjectNotSupportedException;
				}
			}
			$this->collection->updateOne(
				array(
					"_id" => $this->id,
				),
				array(
					'$set' => array(
						"tags" => $mediaTags,
					),
				)
			);
		}
	}

	function getCoverPic(){
		if(isset($this->media['cover'])){
			return $this->media['cover'];
		}
	}

	function setCoverPic($coverpic){
		if(isset($coverpic)){
			$this->collection->updateOne(
				array(
					"_id" => $this->id,
				),
				array(
					'$set' => array(
						"cover" => $coverpic,
					),
				)
			);
		}
	}

	function getComingSoon(){
		if(isset($this->media['coming-soon'])){
			return $this->media['coming-soon'];
		}else{
			throw new MediaResourceNotFoundException;
		}
	}
	function setComingSoon($comingsoon){
		if(isset($comingsoon)){
			$this->collection->updateOne(
				array(
					"_id" => $this->id,
				),
				array(
					'$set' => array(
						"coming-soon" => $comingsoon,
					),
				)
			);
		}
	}

	/**
	 * Sets the description of the media content
	 * @param String $newDescription
	 */
	function setDescription($newDescription) {
		$this->collection->updateOne(
			array(
				"_id" => $this->id,
			),
			array(
				'$set' => array(
					'description' => $newDescription,
				),
			)
		);

		$this->media['description'] = $newDescription;
	}

	function addActivityLog($msg, $tag) {
		$time = date("F jS, Y H:i:s", time());
		$this->collection->updateOne(
			[
				"_id" => $this->id,
			],
			[
				'$push' => [
					"logs" => [
						"tag" => $tag,
						"time" => $time,
						"msg" => $msg,
					],
				],
			]
		);
	}

	function setServiceDomain($domain) {
		$this->collection->updateOne(
			array(
				"_id" => $this->id,
			),
			array(
				'$set' => array(
					'service_domain' => $domain,
				),
			)
		);

		$this->media['service_domain'] = $domain;
	}

	function getServiceDomain() {
		if (isset($this->media['service_domain'])) {
			return $this->media['service_domain'];
		} else {
			throw new ServiceDomainUnavailableException;
		}
	}

	function getThumbTemplate() {
		$schema = Session::$environment == 'local' ? 'http' : 'https';
		$thumb = str_replace('<%schema%>', $schema, Media::THUMB_PATTERN);
		$thumb = str_replace('<%cdn%>', Session::$cacheCDN, $thumb);
		$thumb = str_replace('<%mid%>', $this->getMediaID(), $thumb);
		return $thumb;
	}

	function updateBuildInfo($progress, $state) {
		if ($this->media['available'] == false) {
			$this->collection->updateOne(
				array(
					"_id" => $this->id,
				),
				array(
					'$set' => array(
						'build.state' => $state,
						'build.progress' => $progress,
					),
				)
			);
			$this->media['build']['state'] = $state;
			$this->media['build']['progress'] = $progress;
		}
	}

	function setAvailablity($state){
		$this->collection->updateOne(
			array(
				"_id" => $this->id,
			),
			array(
				'$set' => array(
					'available' => $state,
				),
			)
		);
		$this->media['available'] = $state;
	}

	function isAvailable(){
		return $this->media['available'];
	}

	function getBuildProgress() {
		return $this->media['build']['progress'];
	}

	function getBuildInfo() {
		return $this->media['build']['state'];
	}

	function getProcessPID() {
		return isset($this->media['build']['pid']) ? $this->media['build']['pid'] : 0;
	}

	function setProcessPID($pid) {
		$this->collection->updateOne(
			array(
				"_id" => $this->id,
			),
			array(
				'$set' => array(
					'build.pid' => $pid,
				),
			)
		);
		$this->media['build']['pid'] = $pid;
	}

	function isProcessAlive() {
		$process = new Process();
		$process->setPid($this->getProcessPID());
		return $process->isAlive();
	}

	function stopBuildProcess() {
		$process = new Process();
		$process->setPid($this->getProcessPID());
		if ($process->isAlive()) {
			$process->stop();
		}
	}

	/**
	 * Returns the title of the media content
	 * @return String
	 */
	function getTitle() {
		return $this->media['title'];
	}

	function getOwner() {
		return new User($this->media['createdBy']);
	}

	function userHasAccess() {
		if (Session::getUser()->getUsername() == $this->media['createdBy']) {
			return true;
		} else {
			try {
				$lic = Session::getUser()->getLicense();
				$license = new License($lic, $this->getOwner()->getPublicKey());
				if ($license->checkLicense()) {
					return true;
				} else {
					return false;
				}
			} catch (LicenseNotFoundException $e) {
				return false;
			}
		}
	}

	/**
	 * Sets thr title of the media content
	 * @param String $newTitle
	 */
	function setTitle($newTitle) {
		$update = $this->collection->updateOne(
			array(
				"_id" => $this->id,
			),
			array(
				'$set' => array(
					'title' => $newTitle,
				),
			)
		);
		if ($update) {
			$this->media['title'] = $newTitle;
			return true;
		} else {
			return false;
		}
	}

	function getHLSUrl() {
		$hls = new Url('/video/' . $this->getMediaID . '.m3u8');
		return $hls->getAbsoluteUrl();
	}

	function getMediaID() {
		return $this->media['mid'];
	}

	function setDuration($time){
		$this->collection->updateOne(
			array(
				"_id" => $this->id,
			),
			array(
				'$set' => array(
					'duration' => $time,
				),
			)
		);
		$this->media['duration'] = $time;
	}

	/**
	 * Required by the abstract class Voter
	 * @return String MongoDB Collection ID
	 */
	function getId() {
		return $this->id;
	}

	/**
	 * Required by the abstract class Voter
	 * @return String MongoDB Collection
	 */
	protected function getCollection() {
		return $this->collection;
	}

	protected function shouldNotify() {
		return false;
	}

	protected function getClassName() {
		return __CLASS__;
	}

	protected function getBenificiary() {
		return Session::getUser();
	}

	/**
	 * This function takes input as tags, which can either be thier IDs or the tag names itself, and by default, they make an $or operation on the multiKey index on the database on the tags array on media collection, and returns the media objects for them all.
	 * @param  Array/String  $tags Array of tags or a single tag
	 * @param  boolean $any  Default true. If set false, $and operation will takes place, which means, only if all the tags exists as required, the media will be selected.
	 * @return Array[Media]
	 */
	public static function searchMedia($tags, $any = true) {
		$db = DatabaseConnection::getDefaultDatabase();
		if (is_array($tags)) {
			$tagArray = array();
			foreach ($tags as $tag) {
				$tag = new Tag($tag);
				array_push($tagArray, array("tags" => $tag->getId()));
			}
			if ($any) {
				$query = array('$or' => $tagArray);
			} else {
				$query = $tagArray;
			}
			return $db->media->find($query);
		} else {
			$tag = new Tag($tags);
			return $db->media->find(array("tags" => $tag->getId()));
		}
	}

	public static function getAllMedia(){
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->media;
		$allmedia = $collection->find(
			array(
				)
			);
		return $allmedia;
	}
}