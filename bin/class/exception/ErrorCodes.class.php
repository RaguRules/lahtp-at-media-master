<?php

/*
* Fixed by Scan Ghost 
* @branch sibi
* @user sibi
* @date August 16th, 2017 09:29:21
*/



/*
* Fixed by Scan Ghost 
* @branch sibi
* @user sibi
* @date August 16th, 2017 09:28:50
*/



/*
* Fixed by Scan Ghost 
* @branch sibi
* @user sibi
* @date August 16th, 2017 09:07:05
*/



/*
* Fixed by Scan Ghost 
* @branch sibi
* @user sibi
* @date August 16th, 2017 09:06:39
*/



class ErrorCodes {
	const INVALID_USER_EXCEPTION_MESSAGE = "User %s is not registered or invalid.";
	const INVALID_USER_EXCEPTION_CODE = 1001;
	const AUTHENTICATION_EXCEPTION_MESSAGE = "User or password does not match.";
	const AUTHENTICATION_EXCEPTION_CODE = 1002;
	const INVALID_SESSION_EXCEPTION_MESSAGE = "Session invalid or timed out. Try login again.";
	const INVALID_SESSION_EXCEPTION_CODE = 1003;
	const INVALID_REQUEST_EXCEPTION_MESSAGE = "Your request is invalid or blocked for security reasons.";
	const INVALID_REQUEST_EXCEPTION_CODE = 1004;
	const INVALID_MOBILE_EXCEPTION_MESSAGE = "Invalid mobile number.";
	const INVALID_MOBILE_EXCEPTION_CODE = 1005;
	const INCOMPLETE_SIGNUP_EXCEPTION_MESSAGE = "Signup process is incomplete. Please continue it.";
	const INCOMPLETE_SIGNUP_EXCEPTION_CODE = 1006;
	const INCOMPLETE_PROFILE_EXCEPTION_MESSAGE = "Signup process is incomplete. Please continue it.";
	const INCOMPLETE_PROFILE_EXCEPTION_CODE = 1007;
	const USER_REGISTERED_EXCEPTION_MESSAGE = "User already registered. If you forgot your password, recover your account.";
	const USER_REGISTERED_EXCEPTION_CODE = 1008;
	const INVALID_SIGNUP_TOKEN_EXCEPTION_MESSAGE = "Signup token invalid. You need to start over your signup process.";
	const INVALID_SIGNUP_TOKEN_EXCEPTION_CODE = 1009;
	const NO_SUCH_INTEREST_EXCEPTION_MESSAGE = "No such interest has been found. Consider requesting a moderator for creation.";
	const NO_SUCH_INTEREST_EXCEPTION_CODE = 1010;
	const USER_NOT_AUTHORIZED_FOR_ACTION_EXCEPTION_MESSAGE = "This user cannot perform this action.";
	const USER_NOT_AUTHORIZED_FOR_ACTION_EXCEPTION_CODE = 1011;
	const CLASS_NOT_CONSTRUCTED_FOR_ACTION_EXCEPTION_MESSAGE = "No sofficient data constructed for this action.";
	const CLASS_NOT_CONSTRUCTED_FOR_ACTION_EXCEPTION_CODE = 1012;
	const SIMILAR_INTEREST_FOUND_EXCEPTION_MESSAGE = "Similar interest has been found, cannot process.";
	const SIMILAR_INTEREST_FOUND_EXCEPTION_CODE = 1013;
	const SIMILAR_INTEREST_ID_FOUND_EXCEPTION_MESSAGE = "Similar interest titleId has been found, cannot process.";
	const SIMILAR_INTEREST_ID_FOUND_EXCEPTION_CODE = 1014;
	const DOMAIN_BLACKLIST_MESSAGE = "The domain cannot be shortened.";
	const DOMAIN_BLACKLIST_CODE = 1015;
	const TAG_NOT_FOUND_EXCEPTION = "The tag specified is not found";
	const TAG_NOT_FOUND_EXCEPTION_CODE = 1016;
	const MEDIA_NOT_FOUND_EXCEPTION = "The media resource specified does not exist in the CDN path";
	const MEDIA_NOT_FOUND_EXCEPTION_CODE = 1017;
}

