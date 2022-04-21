<?php

/**
 * Abstract class Voter, used for voting purposes. When a question, media, or some resource required to be voted up or down, voter class is will satisfy the need.
 */

abstract class ContentVoter {

	abstract protected function getId();
	abstract protected function getCollection();
	abstract protected function getClassName();
	abstract protected function getBenificiary($td=null);

	private $votesCollection = null;
	private $constructed = false;

	function __construct() {
		$this->constructed = true;
		$this->votesCollection = DatabaseConnection::getDefaultDatabase()->votes;
	}

	const STATUS_UPVOTED = 1;
	const STATUS_DOWNVOTED = 2;
	const STATUS_REMOVED_UPVOTE = 3;
	const STATUS_REMOVED_DOWNVOTE = 4;
	const UPVOTE = "Upvote";
	const DOWNVOTE = "Downvote";

	protected function countUpVotes($format = true) {
		if (!$this->constructed) {
			throw new Exception('Content voter not constructed on ' . $this->getClassName());
		}
		$var = $this->getCollection()->findOne(
			array(
				"_id" => $this->getId(),
				)
			);
		$count = isset($var['upvoteCount']) ? $var['upvoteCount'] : 0;
		return $format ? si_num($count) : $count;
	}

	protected function countDownVotes($format = true) {
		if (!$this->constructed) {
			throw new Exception('Content voter not constructed on ' . $this->getClassName());
		}
		$var = $this->getCollection()->findOne(
			array(
				"_id" => $this->getId(),
				),
			array(
				'projection' => array(
					'downvoteCount' => 1,
					"_id" => 0,
					),
				)
			);
		$count = isset($var['downvoteCount']) ? $var['downvoteCount'] : 0;
		return $format ? si_num($count) : $count;
	}

	protected function countDifference($format = true){
		$diff =  $this->countUpVotes(false) - $this->countDownVotes(false);
		return $format ? si_num($diff) : $diff;
	}

	protected function isUpVoted() {
		if (!$this->constructed) {
			throw new Exception('Content voter not constructed on ' . $this->getClassName());
		}
		if(Session::getUser() == null){
			return false;
		}
		$var = $this->votesCollection->findOne(
			array(
				"hash" => md5($this->getId() . $this->getClassName() . ContentVoter::UPVOTE),
				"username" => Session::getUser()->getUsername(),
				)
			);
		if (empty($var)) {
			return false;
		} else {
			return true;
		}
	}

	protected function isDownVoted() {
		if (!$this->constructed) {
			throw new Exception('Content voter not constructed on ' . $this->getClassName());
		}
		if(Session::getUser() == null){
			return false;
		}
		$var = $this->votesCollection->findOne(
			array(
				"hash" => md5($this->getId() . $this->getClassName() . ContentVoter::DOWNVOTE),
				"username" => Session::getUser()->getUsername(),
				)
			);
		if (empty($var)) {
			return false;
		} else {
			return true;
		}
	}

	protected function deleteVote($type){
		$this->votesCollection->deleteOne(
			array(
				"hash" => md5($this->getId() . $this->getClassName() . $type),
				"username" => Session::getUser()->getUsername(),
			)
		);
	}

	protected function toggleUpVote() {
		if (!$this->constructed) {
			throw new Exception('Content voter not constructed on ' . $this->getClassName());
		}
		$return = 0;
		if ($this->isDownVoted()) {
			at_error_log('Help Action: Downvote detected, toggling...', 'vote');
			$this->deleteVote(self::DOWNVOTE);
		}
		$array = array(
			"vid" => $this->getId(),
			"class" => $this->getClassName(),
			"type" => ContentVoter::UPVOTE,
			"username" => Session::getUser()->getUsername(),
			"hash" => md5($this->getId() . $this->getClassName() . ContentVoter::UPVOTE),
			);
		if ($this->isUpVoted()) {
			at_error_log('upvoted, so deleting...', 'vote');
			$this->deleteVote(ContentVoter::UPVOTE);
			$return = ContentVoter::STATUS_REMOVED_UPVOTE;
		} else {
			at_error_log('not upvoted, so casting...', 'vote');
			$array['votedOn'] = time();
			$this->votesCollection->insertOne($array);
			$return = ContentVoter::STATUS_UPVOTED;
		}
		$this->updateCount();
		return $return;
	}

	protected function toggleDownVote() {
		if (!$this->constructed) {
			throw new Exception('Content voter not constructed on ' . $this->getClassName());
		}
		$return = 0;
		if ($this->isUpVoted()) {
			$this->deleteVote(self::UPVOTE);
		}

		$array = array(
			"vid" => $this->getId(),
			"class" => $this->getClassName(),
			"type" => ContentVoter::DOWNVOTE,
			"username" => Session::getUser()->getUsername(),
			"votedOn" => time(),
			"hash" => md5($this->getId() . $this->getClassName() . ContentVoter::DOWNVOTE),
			);
		if ($this->isDownVoted()) {
			$this->deleteVote(ContentVoter::DOWNVOTE);
			$return = ContentVoter::STATUS_REMOVED_DOWNVOTE;
		} else {
			$this->votesCollection->insertOne($array);
			$return = ContentVoter::STATUS_DOWNVOTED;
		}
		$this->updateCount();
		return $return;
	}


	protected function updateCount($worker = false){
		if(!$worker){
			$worker = new Worker('MonoVoteCounter', ['class'=>$this->getClassName(), '_id'=>$this->getId()]);
			$worker->invoke();
			return;
		}

		$aggregate = [
			// Stage 1
			[
				'$group'=> [
				  	'_id'=> 0,
					'upvoteCount'=> [
					  	'$sum'=> [
					  	  	'$cond'=>[
					  	  			['$eq'=> ['$hash', md5($this->getId() . $this->getClassName() . ContentVoter::UPVOTE)]],1,0
					  		]
					  	]
					],
					'downvoteCount'=> [
					  	'$sum'=> [
					  	  	'$cond'=>[
					  	  			['$eq'=> ['$hash', md5($this->getId() . $this->getClassName() . ContentVoter::DOWNVOTE)]],1,0
					  		]
					  	]
					]
				]
			],
		];
		$var = $this->votesCollection->aggregate($aggregate);
		$var = iterator_to_array($var)[0];
		$this->getCollection()->updateOne(
			array(
				"_id" => $this->getId(),
				),
			array(
				'$set' => array(
					'downvoteCount' => $var['downvoteCount'],
					'upvoteCount' => $var['upvoteCount']
					),
				)
		);
		return $var;
	}
}