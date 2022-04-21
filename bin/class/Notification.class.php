<?

class Notification {

	private $db = null;
	private $collection = null;
	private $creationMode = false;
	private $data = null;
	private $id = null;

	const ACTION_DIALOG = 'dialog';
	const ACTION_TOAST = 'toast';

	function __construct($id = null) {
		$this->db = DatabaseConnection::getDefaultDatabase();
		$this->collection = $this->db->notifications;

		if ($id == null) {
			$this->creationMode = true;
			$this->id = md5(Session::generatePesudoRandomHash(8));
		} else {
			$this->data = $this->collection->findOne([
				'_id' => $id,
			]);
		}
	}

	function setNotificationType($type){
		$this->data['action'] = $type;
	}

	function build($title, $subtitle, $href, $image=null){
		if($image == null){
			$image = cdn('/assets/prop/icon-256.png');
		}
		$this->data['_id'] = $this->id;
		$this->data['body'] = $title;
		$this->data['subtitle'] = $subtitle;
		$this->data['notifiedOn'] = time();
		$this->data['href'] = $href;
		$this->data['image'] = $image;
		$this->data['read'] = false;
		$this->data['action'] = Notification::ACTION_TOAST;
	}

	function commit($for){
		if (get_class($for) != 'User') {
			throw new ObjectNotSupportedException;
		}

		if($this->creationMode){
			$this->data['from'] = Session::getUser()->getUsername();
			$this->data['to'] = $for->getUsername();
			$dbPack = WebAPI::purifyArray($this->data);
			unset($dbPack['carbon']);
			$this->collection->insertOne($dbPack);
			unset($this->data['from']);
			Notifier::notify($for, $this->data);
		} else {
			throw new CannotRecommitNotificationException;
		}
	}

	function resendNotification(){
		unset($this->data['from']);
		Notifier::notify($for, $this->data);
	}

	function markRead(){
		$this->collection->updateOne([
			'_id' => $this->data['_id']
		], [
			'$set' => [
				'read' => true
			]
		]);
		$this->data['read'] = true;
	}

	function markUnread(){
		$this->collection->updateOne([
			'_id' => $this->data['_id']
		], [
			'$set' => [
				'read' => false
			]
		]);
		$this->data['read'] = false;
	}

	function toggleMark(){
		if($this->data['read']){
			$this->markUnread();
		} else {
			$this->markRead();
		}
	}

	function isRead(){
		return $this->data['read'];
	}

	function getHref(){
		return isset($this->data['href']) ? $this->data['href'] : null;
	}

	function getTitle(){
		return isset($this->data['title']) ? $this->data['title'] : null;
	}

	function getSubtitle(){
		return isset($this->data['subtitle']) ? $this->data['subtitle'] : null;
	}

	function getTime(){
		return isset($this->data['time']) ? $this->data['time'] : null;
	}

	function getImage(){
		return isset($this->data['image']) ? $this->data['image'] : null;
	}

	function notifiedOn(){
		return isset($this->data['notifiedOn']) ? $this->data['notifiedOn'] : null;
	}

	public static function getLatestNotifications($before=null){
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->notifications;
		if($before == null){
			$before = time();
		} else {
			settype($before, "integer");
		}
		$result = $collection->find([
				'to' => Session::getUser()->getUsername(),
				'notifiedOn' => [
					'$lt' => $before
				]
		],
		[
			'projection' => [
				'from' => 0
			],
			'sort' => [
				'notifiedOn'=>-1
			],
			'limit' => 10
		]);
		return iterator_to_array($result);
	}

	public static function countUnreadNotifications(){
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->notifications;
		$result = $collection->aggregate([[
			'$match' => [
				'to' => Session::getUser()->getUsername(),
				'read' => false
			]
		],
		[
			'$count' => 'count'
		]]);
		$result = iterator_to_array($result);
		if(empty($result)) return 0;
		return $result[0]['count'];
	}

	public static function markAllAsRead(){
		$db = DatabaseConnection::getDefaultDatabase();
		$collection = $db->notifications;
		$collection->updateMany([
			'to' => Session::getUser()->getUsername()
		], [
			'$set' => [
				'read' => true
			]
		]);
	}
}