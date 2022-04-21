<?php
require_once "bin/load.php";

$api = new API;

/*
While processing API methods, please do not use Camel case or Pascal case letters. Always stick with default structural naming with no upper case, method words seperated with underscore(_) [snake_case].

Every method needs to be registered here manually inorder to be called.
 */

$api->processApi(array(
	//Media test for CA
	"status",
	"create",
	"info",
	"list",
	"contains",
	"remove",
	"upload",
	"title"
));
