<?php

class Constants {

	const MAJOR_VERSION = '0.1';
	const STORAGE = '/atmedia/';

	const USERNAME = 'a9781d0ca6abb43812a28783ccb3bbaa';
	const PASSWORD = '79b9d7f19a44b3a0147bb0a242edce8d763c60935e9675caf68344094ae0881c';

	//Default Databases
	const STORE_DATABASE = 'atmedia';
	const WEB_DATABASE = 'webdata';
	const STATS_DATABASE = 'stats';
	const PASSIVE_DATABASE = 'passive';
	const FILE_DATABASE = 'files';

	//api.php
	const SUCCESS = 'success';
	const OK = 'ok';
	const ERROR = 'error';

	//Authentication Status
	const STATUS_DEFAULT = "default";
	const STATUS_LOGGEDIN = "loggedin";

	//Notifier.class.php
	const RMQ_URL = "mq.aftertutor.com";
	const RMQ_PORT = '11110';
	const LOCAL_RMQ_URL = 'lmq.aftertutor.com';
	const LOCAL_RMQ_PORT = '15674';
	const RMQ_USER = 'client';
	const RMQ_PASS = 'at_client';

	//Url.class.php
	const SHORTENER = "https://atut.me/api/v2/action/shorten";
	const SHORTENER_API_KEY = "9e260d98672e88a2a63f91d45dd091";
	const CDN_URL_EXPIRY = 600;
	//More to be added in future.


}