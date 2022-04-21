<?php

/**
 * The DatabaseConnection is a class with public static methods used to access the database client objects originally created during the session. It has access to all the databases accross the server.
 */
class DatabaseConnection {

	/**
	 * Defined by WebApi in Init.php
	 * @var MongoDB/Client
	 */
	public static $client = null;

	/**
	 * From the Constants, it reads provides the Database object for the requested database.
	 * @return MongoDB/Database
	 */
	public static function getDefaultDatabase(){
		return DatabaseConnection::$client->{Constants::STORE_DATABASE};
	}

	public static function getWebDatabase(){
		return DatabaseConnection::$client->{Constants::WEB_DATABASE};
	}

	public static function getStatsDatabase(){
		return DatabaseConnection::$client->{Constants::STATS_DATABASE};
	}

	public static function getPassiveDatabase(){
		return DatabaseConnection::$client->{Constants::PASSIVE_DATABASE};
	}

	public static function getFileDatabase(){
		return DatabaseConnection::$client->{Constants::FILE_DATABASE};
	}
}