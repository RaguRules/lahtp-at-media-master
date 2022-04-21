<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Notifier {

	public static function notify($to, $body) {
		if (get_class($to) != 'User') {
			throw new ObjectNotSupportedException;
		}

		if (!is_array($body)) {
			throw new ObjectNotSupportedException;
		}

		$exchange = md5(base64_encode($to->getUsername().Session::$environment))."_".$to->getUsername();
		$host = "mq.aftertutor.com";
		if (Session::$environment == "local") {
			$host = "lmq.aftertutor.com";
		}
		$connection = new AMQPStreamConnection($host, 5672, 'client', 'at_client');
		$channel = $connection->channel();
		$properties = array(
			'delivery_mode' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT,
			'expiration' => '10000',
			'content_type' => 'application/json',
		);

		$msg = new AMQPMessage(json_encode($body, JSON_PRETTY_PRINT), $properties);
		$channel->exchange_declare($exchange, 'fanout', false, false, true);
		$channel->basic_publish($msg, $exchange);
		$channel->close();
		$connection->close();
	}

	public static function ensureExchange(){
		$exchange = md5(base64_encode(Session::getUser()->getUsername().Session::$environment))."_".Session::getUser()->getUsername();
		$host = "mq.aftertutor.com";
		if (Session::$environment == "local") {
			$host = "lmq.aftertutor.com";
		}
		$connection = new AMQPStreamConnection($host, 5672, 'client', 'at_client');
		$channel = $connection->channel();
		$channel->exchange_declare($exchange, 'fanout', false, false, true);
		$channel->close();
		$connection->close();
		return $exchange;
	}

	/**
	 * sendSMS function is used to send SMS to a user to the provided mobile number.
	 * @param  String $to   10 digit mobile number
	 * @param  String $body Contents of the string
	 * @return null
	 */
	public static function sendSMS($to, $body) {
		$array = array('to' => $to, 'body' => $body);
		$w = new Worker('SendSMS', $array);
		$w->invoke();
	}

}
