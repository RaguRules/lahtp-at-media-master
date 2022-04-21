<?
/**
 * @author : Sibidharan
 * @Email  : sibi@aftertutor.com
 */
class Paper extends ContentVoter{

	/**
	 * The id representing the current paper.
	 * @var null
	 */
	private $id = null;
	/**
	 * The original database client vairable fetched from the DatabaseConnection::getDefaultDatabase()
	 * @var MongoDB\Client
	 */
	private $db = null;
	/**
	 * The collection object for the Paper with which the calls are made.
	 * @var MongoDB\Collection
	 */
	private $collection = null;
	/**
	 * This flag is set when a new content is created.
	 * @var boolean
	 */
	private $creationMode = false;

	private $paper = null;

	const PAPER_FILTER_NEW = 1;
	const PAPER_FILTER_POPULAR = 2;
	const PAPER_FILTER_POPULAR_BY_USER = 3;
	const PAPER_FILTER_NONE = 4;
	const PAPER_FILTER_NEW_BY_USER = 5;
	const PAPER_FILTER_HOT = 6;
	const PAPER_FILTER_HOT_WEEK = 7;
	const PAPER_FILTER_HOT_MONTH = 8;

	function __call($method, $args) {
		if(!method_exists($this, $method)) {
			throw new Exception('Unreachable method: '.$method);
		}
		CheckPrivilege::access(__CLASS__, $method);
		$result = call_user_func_array(__CLASS__."::".$method, $args);
		// $event = new Event(__CLASS__, $method, $result, $this);
		return $result;
	}


	function __construct($id = null){
		parent::__construct();
		$this->id = $id;
		$this->db = DatabaseConnection::getDefaultDatabase();
		$this->collection = $this->db->papers;
		if($this->id==null){
			$this->id = md5(Session::generatePesudoRandomHash(24));
			$this->paper = array("_id"=>$this->id);
			$this->creationMode = true;
			$this->collection->insertOne($this->paper);
		} else {
			$this->paper = ['$or'=>[["_id"=>$this->id], ["pid"=>$this->id]]];
			$this->paper = $this->collection->findOne($this->paper);
			if(empty($this->paper)){
				throw new PaperNotFoundException;
			}
			$this->id = $this->paper['_id'];
		}

		/*db.papers.find({ $or: [ { "pid": "what-molecule-is-a-byproduct-of-condensation-polymarization" }, { "_id": "what-molecule-is-a-byproduct-of-condensation-polymarization" } ] })*/
	}

	/**
	 * Returns the Title of the Paper.
	 * @return String
	 */
	protected function getTitle(){
		return isset($this->paper['title']) ? $this->paper['title'] : "Untitled";
	}

	protected function getPostedOn(){
		return $this->paper['postedOn'];
	}

	protected function getUpdatedOn(){
		return $this->paper['postedOn']; //change it to updatedOn if edited/answered/commented anytime soon.
	}

	protected function getPostedBy(){
		return $this->paper['postedBy'];
	}

	protected function getPostedByFullName(){
		$user = new User($this->paper['postedBy']);
		return $user->getFullName();
	}

	function postedBy(){
		return $this->getPostedBy();
	}

	function postedOn(){
		return $this->getPostedOn();
	}

	protected function isCreationMode(){
		return $this->creationMode;
	}

	function getFollowers(){
		return isset($this->paper['involved']) ? $this->paper['involved'] : [];
	}

	function unfollow($username){
		if(($key = array_search($username, $this->paper['involved'])) !== false) {
		    unset($this->paper['involved'][$key]);
		}
		$this->collection->updateOne([
			"_id" => $this->id
		], [
			'$pull' => [
				'involved' => $username
			]
		]);

	}

	function isFollowing($username){
		if(array_search($username, $this->paper['involved']) !== false) {
		    return true;
		} else {
			return false;
		}
	}

	function getShareUrl(){
		if(isset($this->paper['share_url'])){
			return $this->paper['share_url'];
		} else {
			$url = new Url('/paper/'.$this->getPaperID());
			$url->append('_ref', 'share');
			$shortUrl = $url->shorten();
			$this->collection->updateOne([
				'_id'=>$this->getId()
			], [
				'$set'=>[
					'share_url' => $shortUrl
				]
			]);
			$this->paper['share_url'] = $shortUrl;
			return $shortUrl;
		}
	}

	function getAuthors(){
		if(isset($this->paper['authors'])){
			return $this->paper['authors'];
		}
	}

	public function uploadSlides($slides){
		$pack = array();
		foreach ($slides as $index => $slide) {
			$bucketname = str_replace(" ","_",$slide);
			$bucket = new Bucket(basename($bucketname));
			$pack[$index] = array("pack" => $bucket->upload($slide, true),"sortorder" => $index,"voiceover" => "http://urlofvoiceover");
		}
		$this->collection->updateOne([
			"_id" => $this->id
		], [
			'$set' => [
				'slides' => $pack,
				'slidesCreated' => true
			]
		]);
		if($pack){
			return true;
		}
		else{
			return false;
		}
	}
	public function uploadPaper($paper){
		$bucketname = str_replace(" ","_",$paper);
		$bucket = new Bucket(basename($bucketname));
		$pack = array("pack" => $bucket->upload($paper, true));
		$this->collection->updateOne([
			"_id" => $this->id
		], [
			'$set' => [
				'paper' => $pack
			]
		]);
		if($pack){
			return true;
		}
		else{
			return false;
		}

	}
	public function downloadPaper(){
		return $this->paper['paper']['pack']['download_url'];
	}

	protected function getSlides(){
		return $this->paper['slides'];
	}

	function follow($username){
		if(!isset($this->paper['involved']) or !is_array($this->paper['involved'])){
			$this->paper['involved'] = array();
		}
		array_push($this->paper['involved'], $username);
		$this->collection->updateOne([
			"_id" => $this->id
		], [
			'$addToSet' => [
				'involved' => $username
			]
		]);
		$this->setUpdatedTime();
	}

	/**
	 * Sets the Title of the Paper.
	 * @param String $newTitle
	 */
	protected function setTitle($newTitle){
		$update = $this->collection->updateOne(
			array(
				"_id" => $this->id
				),
			array(
				'$set'=>array(
					'title'=>$newTitle,
					)
				)
			);
		if($update){
			$this->paper['title'] = $newTitle;
			$this->setUpdatedTime();
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Returns the Abstract of the Paper
	 * @return String
	 */
	protected function getAbstract(){
		return isset($this->paper['abstract'])?$this->paper['abstract']:"";
	}


	/**
	 * Sets the Abstract of the Paper.
	 * @param String $abstract
	 */
	protected function setAbstract($abstract){
		$update = $this->collection->updateOne(
			array(
				"_id" => $this->id
				),
			array(
				'$set'=>array(
					'abstract'=>$abstract,
					)
				)
			);
		if($update){
			$this->paper['abstract'] = $abstract;
			$this->setUpdatedTime();
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Returns the Tags for the  Paper.
	 * @return String
	 */
	protected function getTags(){
		return $this->paper['tags'];
	}

	function getTagsAsTitles() {
		$tags = $this->getTags();
		$titles = array();
		foreach ($tags as $tag) {
			$tag = new Tag($tag);
			array_push($titles, $tag->getTitle());
		}
		return $titles;
	}

	/**
	 * Sets the Tags for the  Paper.
	 * @param String $newTags
	 */
	protected function setTags($newTags){
		$update = $this->collection->updateOne(
			array(
				"_id" => $this->id
				),
			array(
				'$set'=>array(
					'tags'=>$newTags[0],
					)
				)
			);
		if($update){
			$this->paper['tags'] = $newTags;
			$this->setUpdatedTime();
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Sets the Paper with title,abstract,posted_by and posted_on details.
	 * @param  String $title
	 * @param  String $abstract
	 */
	protected function setPaper($title, $abstract, $tags, $username,$authors){
		$pid = implode('-', explode(' ',strtolower($title)));
		$pid = preg_replace("/[^a-z0-9-]/", '', $pid);
		$authors = json_decode($authors);
		$check = $this->collection->find(['title'=>$title], [
			'sort' => ['postedOn'=>-1],
			'limit' => 1
			]);
		$chk1 = null;
		foreach ($check as $chk) {
			$chk1 = $chk;
		}
		$check = $chk1;
		if(!empty($check)){
			$pid = $check['pid'];
			$varArray = explode('-', $pid);
			$last = $varArray[count($varArray)-1];
			if(is_numeric($last)){
				settype($last, "integer");
				$last++;
				$varArray[count($varArray)-1] = $last;
			} else {
				$last = 0;
				$varArray[count($varArray)] = $last;
			}
			$pid = implode('-', $varArray);
		}

		$update = $this->collection->updateOne(
			array(
				"_id" => $this->id
				),
			array(
				'$set'=>array(
					'pid' => $pid,
					'title' => $title,
					'abstract'=>$abstract,
					'authors' => $authors,
					'tags' => $tags,
					'upvoteCount'=> 0,
					'downvoteCount'=> 0,
					'views'=> 0,
					'postedBy' => Session::getUser()->getUsername(),
					'postedOn'=> time(),
					'deleted'=> false,
					'published' => false, //can be turned to true only if slide created is true
					'slidesCreated' => false
				)
			)
			);
		if($update){
			$this->paper['title'] = $title;
			$this->paper['pid'] = $pid;
			$this->paper['abstract'] = $abstract;
			$this->paper['authors'] = $authors;
			$this->paper['tags'] = $tags;
			$this->paper['votes'] = 0;
			$this->paper['postedBy'] = Session::getUser()->getUsername();
			$this->paper['posteOn'] = date("Y-m-d H:i");
			$this->setUpdatedTime();
			return 1;
		} else {
			return 0;
		}
	}

	protected function delete($status = true) {
 		$this->collection->updateOne([
 			'_id'=>$this->getId()
 		], [
 			'$set'=>[
				'deleted' => $status,
 			]
 		]);
 		$this->setUpdatedTime();
		$this->paper['deleted'] = true;
 	}


	/**
	 * Required by the abstract class ContentVoter
	 * @return String MongoDB Collection ID
	 */
	function getId(){
		return $this->id;
	}

	protected function getPaperID(){
		return $this->paper['pid'];
	}

	protected function getUpvotedCount(){
		return $this->paper['upvoteCount'];
	}

	protected function getDownvotedCount(){
		return $this->paper['downvoteCount'];
	}

	/**
	 * Required by abstract class ContentVoter
	 * @return String MongoDB collection
	 */
	protected function getCollection(){
		return $this->collection;
	}

	function getClassName(){
		return __CLASS__;
	}

	protected function getBenificiary($td=null){
		at_error_log($td, "notification");
		return new User($this->paper['postedBy']);
	}

	function getViewCount(){
		if(isset($this->paper['views'])){
			return $this->paper['views'];
		} else {
			return 0;
		}
	}

	function setViewCount($count){
		$this->paper['views'] = $count;
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

	function setPublished($published = true){
		if(!$this->paper['slidesCreated']){
			return false;
		}

		$this->paper['published'] = $published;
		$this->collection->updateOne(
			[
				'_id'=>$this->getId()
			],
			[
				'$set'=> [
					'published' => $published
				]
			]
		);
	}

	function isPublished(){
		return $this->paper['published'];
	}

	function close($user,$reasons){
		$this->collection->updateOne(
			[
				'_id'=>$this->getId()
			],
			[
				'$set'=> [
					'closed' => true,
					'closedBy' => $user,
					'reasons' =>$reasons
				]
			]
		);
		$this->paper['closed'] = true;
		$this->paper['closedBy'] = $user;
		$this->setUpdatedTime();
	}

	function isClosed(){
		return isset($this->paper['closed']) ? $this->paper['closed'] : false;
	}

	function closedBy(){
		return isset($this->paper['closedBy']) ? $this->paper['closedBy'] : Constants::COMMUNITY;
	}

	function closedFor(){
		if($this->isClosed()){
			return isset($this->paper['reasons']) ? $this->paper['reasons'] :"";
		}
	}

	function isDeleted(){
		return isset($this->paper['deleted']) ? $this->paper['deleted'] : false;
	}

	function re_open($user){
		$this->collection->updateOne(
			[
				'_id'=>$this->getId()
			],
			[
				'$set'=> [
					'closed' => false,
					'openedBy' => $user
				]
			]
		);
		$this->setUpdatedTime();
	}

	function setUpdatedTime(){
		$time = time();
		$this->collection->updateOne(
			array(
				"_id" => $this->id,
			),
			array(
				'$set' => array(
					'updatedOn' => $time
				),
			)
		);
		$this->paper['updatedOn'] = $time;
	}

	public static function getPapersBySort($sort = Paper::PAPER_FILTER_NEW, $user = null){
		switch ($sort) {
			case Paper::PAPER_FILTER_NEW:
				$university = null;
				if(isset($_GET['area']) and $_GET['area'] == "university"){
					$university = Session::getUser()->getUniversity()->getId();
				}
				return Paper::getLatestPapers(time(), $university);
				break;

			case Paper::PAPER_FILTER_POPULAR:
				$university = null;
				if(isset($_GET['area']) and $_GET['area'] == "university"){
					$university = Session::getUser()->getUniversity()->getId();
				}
				return Paper::getPopularPapers(0, $university);
				break;

			case Paper::PAPER_FILTER_HOT:
				$university = null;
				if(isset($_GET['area']) and $_GET['area'] == "university"){
					$university = Session::getUser()->getUniversity()->getId();
				}
				return Paper::getFeaturedPapers(0, $university);
				break;

			case Paper::PAPER_FILTER_NEW_BY_USER:
				if($user == null){
					throw new InvalidObjectException;
				} else {
					if(is_object($user) and get_class($user) == 'User'){
						return Paper::getNewPapers($user);
					} else {
						throw new InvalidObjectException;
					}
				}
				break;

			case Paper::PAPER_FILTER_POPULAR_BY_USER:
				if($user == null){
					throw new InvalidObjectException;
				} else {
					if(is_object($user) and get_class($user) == 'User'){
						return Paper::getPopularPapers($user);
					} else {
						throw new InvalidObjectException;
					}
				}
				break;

			default:
				throw new UnsupportedActionException;
				break;
		}
	}

	public static function getNewPapers($user=null){
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->papers;
		if($user!=null)
		{
			$newpapers = $collection->find(
				[
					'postedBy' =>$user,
					'deleted' => false
				],
				[
					'sort' => [
						'postedOn'=>-1
					],
					'limit'=>4 //TODO: Change limit to 10
				]
			);
		}
		else{
			$newpapers = $collection->find(
				[
					'deleted' => false,
				],
				[
					'sort' => [
						'postedOn'=>-1
					],
					'limit'=>4 //TODO: Change limit to 10
				]
			);
		}
		$newpapers = iterator_to_array($newpapers);
		return $newpapers;
	}

	public static function getTotalPapersCount($user){
		if($user!=null){
			$db = DatabaseConnection::getDefaultDatabase();
			$collection = $db->papers;
			$totalpapers = $collection->count(
				[
					'postedBy' => $user,
					'deleted' => false,
				]
			);
			return $totalpapers;
		}else{
			return false;
		}
	}

	public static function getTotalPapers($user){
		if($user!=null){
			$db = DatabaseConnection::getDefaultDatabase();
			$collection = $db->papers;
			$totalpapers = $collection->find(
				[
					'postedBy' => $user,
					'deleted' => false
				],
				[
					'sort' => [
						'postedOn' => -1
					]
				]
			);
			$totalpapers = iterator_to_array($totalpapers);
			return $totalpapers;
		}else{
			return false;
		}
	}



	public static function getPopularPapers($skip=0,$university=null,$mode='web') {
		require_ui_component('user_badges');
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->papers;
		settype($skip, "integer");
		if($university != null){
			$result = $collection->aggregate([[
				'$lookup' => [
				    "from" => "users",
				    "localField" => "postedBy",
				    "foreignField" => "authData.username",
				    "as" => "user_data"
				]
			],[
				'$match'=>[
					"user_data.university" => $university
				]
			],[
				'$sort' => [
					'views'=>-1
				]
			],[
				'$skip' => $skip
			],[
				'$limit' => 10
			]
			]);
		}
		else{
			$result = $collection->aggregate([[
				'$lookup' => [
				    "from" => "users",
				    "localField" => "postedBy",
				    "foreignField" => "authData.username",
				    "as" => "user_data"
				]
			],[
				'$sort' => [
					'views'=>-1
				]
			],[
				'$skip' => $skip
			],[
				'$limit' => 10
			]
			]);
		}
		$papers = iterator_to_array($result);
		if($mode == 'app'){
			foreach ($papers as $key => $paper) {
				$quser = null;
				try{
					$quser = new User($papers[$key]['postedBy']);
				} catch (InvalidUserException $e){
					continue;
				}

				$papers[$key]['votes'] = $paper['upvoteCount'] - $paper['downvoteCount'];
				unset($papers[$key]['upvoteCount']);
				unset($papers[$key]['downvoteCount']);
				$tags = $papers[$key]['tags'];
				$tag_title = [];
				foreach ($tags as $tag) {
					$t = new Tag($tag);
					array_push($tag_title, $t->getTitle());
				}
				$papers[$key]['tags'] = $tag_title;
				$papers[$key]['answers'] = Answer::countAnswersFor($papers[$key]['_id']);
				unset($papers[$key]['_id']);
				unset($papers[$key]['user_data']);
				if(isset($papers[$key]['edited'])){
					unset($papers[$key]['edited']);
				}
				unset($papers[$key]['description']);
				$papers[$key]['user_dp'] = Session::getUser()->getDp();
			}
			return $papers;
		}
		foreach ($papers as $key => $paper) {
			$quser = null;
			try{
				$quser = new User($papers[$key]['postedBy']);
			} catch (InvalidUserException $e){
				continue;
			}

			$papers[$key]['votes'] = $paper['upvoteCount'] - $paper['downvoteCount'];
			unset($papers[$key]['upvoteCount']);
			unset($papers[$key]['downvoteCount']);
			$papers[$key]['tags'] = Tag::printTags($papers[$key]['tags'], true);
			$papers[$key]['answers'] = Answer::countAnswersFor($papers[$key]['_id']);
			$papers[$key]['postedBy'] = generate_user_badge($quser, true);
			$papers[$key]['postedByMobile'] = '<a style="margin-right: 15px;" class="link" href="/'.$quser->getUsername().'">'.$quser->getFirstName().'</a>';
			unset($papers[$key]['_id']);
		}
		return $papers;
	}
	public static function getFeaturedPapers($skip=0,$university=null,$mode='web') {
		require_ui_component('user_badges');
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->papers;
		settype($skip, "integer");
		$result = [];
		if($university != null){
			$result = $collection->aggregate([[
				'$lookup' => [
				    "from" => "users",
				    "localField" => "postedBy",
				    "foreignField" => "authData.username",
				    "as" => "user_data"
				]
			],[
				'$match'=>[
					"user_data.university" => $university
				]
			],[
				'$addFields' =>[
					'votes'=> [
						'$subtract' => ['$upvoteCount', '$downvoteCount']
					]
				]

			],[
				'$sort' => [
					'votes'=>-1
				]
			],[
				'$skip' => $skip
			],[
				'$limit' => 10
			]
			]);
		}
		else{
			$result = $collection->aggregate([[
				'$lookup' => [
				    "from" => "users",
				    "localField" => "postedBy",
				    "foreignField" => "authData.username",
				    "as" => "user_data"
				]
			],[
				'$addFields' =>[
					'votes'=> [
						'$subtract' => ['$upvoteCount', '$downvoteCount']
					]
				]

			],[
				'$sort' => [
					'votes'=>-1
				]
			],[
				'$skip' => $skip
			],[
				'$limit' => 10
			]
			]);
		}
		$papers = iterator_to_array($result);
		if($mode == 'app'){
			foreach ($papers as $key => $paper) {
				$quser = null;
				try{
					$quser = new User($papers[$key]['postedBy']);
				} catch (InvalidUserException $e){
					continue;
				}

				$papers[$key]['votes'] = $paper['upvoteCount'] - $paper['downvoteCount'];
				unset($papers[$key]['upvoteCount']);
				unset($papers[$key]['downvoteCount']);
				$tags = $papers[$key]['tags'];
				$tag_title = [];
				foreach ($tags as $tag) {
					$t = new Tag($tag);
					array_push($tag_title, $t->getTitle());
				}
				$papers[$key]['tags'] = $tag_title;
				$papers[$key]['answers'] = Answer::countAnswersFor($papers[$key]['_id']);
				unset($papers[$key]['_id']);
				unset($papers[$key]['user_data']);
				$papers[$key]['user_dp'] = Session::getUser()->getDp();
			}
			return $papers;
		}
		foreach ($papers as $key => $paper) {
			$quser = null;
			try{
				$quser = new User($papers[$key]['postedBy']);
			} catch (InvalidUserException $e){
				continue;
			}

			$papers[$key]['votes'] = $paper['upvoteCount'] - $paper['downvoteCount'];
			unset($papers[$key]['upvoteCount']);
			unset($papers[$key]['downvoteCount']);
			$papers[$key]['tags'] = Tag::printTags($papers[$key]['tags'], true);
			$papers[$key]['answers'] = Answer::countAnswersFor($papers[$key]['_id']);
			$papers[$key]['postedBy'] = generate_user_badge($quser, true);
			$papers[$key]['postedByMobile'] = '<a style="margin-right: 15px;" class="link" href="/'.$quser->getUsername().'">'.$quser->getFirstName().'</a>';
			unset($papers[$key]['_id']);
		}
		return $papers;
	}
	public static function getLatestPapers($before=null,$university=null,$mode='web'){
		require_ui_component('user_badges');
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->papers;
		if($before == null){
			$before = time();
		} else {
			settype($before, "integer");
		}
		if($university == null){
			$result = $collection->aggregate([
				[
					'$match' =>[
						'postedOn' => [
							'$lt' => $before
						],
						'deleted' => false
					]
				],[
					'$project' => [
						'deleted' => 0,
						'involved' => 0,
					]
				],[
					'$sort' => [
						'postedOn'=>-1
					]
				],[
					'$limit' => 10
				]
			]);
		} else {
			$result = $collection->aggregate([
				[
					'$lookup' => [
					    "from" => "users",
					    "localField" => "postedBy",
					    "foreignField" => "authData.username",
					    "as" => "user_data"
					]
				],[
					'$match' =>[
						'postedOn' => [
							'$lt' => $before
						],
						'deleted' => false,
						'user_data.university' => $university
					]
				],[
					'$project' => [
						'deleted' => 0,
						'involved' => 0,
					]
				],[
					'$sort' => [
						'postedOn'=>-1
					]
				],[
					'$limit' => 10
				]
			]);
		}
		$papers = iterator_to_array($result);
		if($mode == 'app'){
			foreach ($papers as $key => $paper) {
				$quser = null;
				try{
					$quser = new User($papers[$key]['postedBy']);
				} catch (InvalidUserException $e){
					continue;
				}

				$papers[$key]['votes'] = $paper['upvoteCount'] - $paper['downvoteCount'];
				unset($papers[$key]['upvoteCount']);
				unset($papers[$key]['downvoteCount']);
				$tags = $papers[$key]['tags'];
				$tag_title = [];
				foreach ($tags as $tag) {
					$t = new Tag($tag);
					array_push($tag_title, $t->getTitle());
				}
				$papers[$key]['tags'] = $tag_title;
				$papers[$key]['answers'] = Answer::countAnswersFor($papers[$key]['_id']);
				unset($papers[$key]['_id']);
				if(isset($papers[$key]['edited'])){
					unset($papers[$key]['edited']);
				}
				unset($papers[$key]['description']);
				$papers[$key]['user_dp'] = Session::getUser()->getDp();
			}
			return $papers;
		}
		foreach ($papers as $key => $paper) {
			$quser = null;
			try{
				$quser = new User($papers[$key]['postedBy']);
			} catch (InvalidUserException $e){
				continue;
			}

			$papers[$key]['votes'] = $paper['upvoteCount'] - $paper['downvoteCount'];
			unset($papers[$key]['upvoteCount']);
			unset($papers[$key]['downvoteCount']);
			$papers[$key]['tags'] = Tag::printTags($papers[$key]['tags'], true);
			$papers[$key]['answers'] = Answer::countAnswersFor($papers[$key]['_id']);
			$papers[$key]['postedBy'] = generate_user_badge($quser, true);
			$papers[$key]['postedByMobile'] = '<a style="margin-right: 15px;" class="link" href="/'.$quser->getUsername().'">'.$quser->getFirstName().'</a>';
			unset($papers[$key]['_id']);
		}
		return $papers;
	}

}