<?php

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header('Content-Type: application/json');

include_once "database_connection.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') { //to prevent GET Request
	http_response_code(405);

	echo json_encode(array("message" => "GET method not allowed"));
	exit;
}

if(!isset($_POST['action'])) { //check for action parameter

	http_response_code(400);

	echo json_encode(array("message" => "Invalid request. Missing action parameter "));
	exit;
}

switch($_POST['action']) {
	case 'getAuthSalt':
		//check whether has sent username and api key id
		if(!isset($_POST['userName'])||!isset($_POST['apiKeyId'])) {
			 http_response_code(400);
			echo json_encode(array("message" => "Invalid request. Missing username or apiKeyId"));	
		} else {
			echo json_encode(getAuthSalt($_POST));
		}
		break;
	case 'authenticate':
		//check whether the request has username,api key id, request token and request salt
		if(!isset($_POST['userName'])||!isset($_POST['apiKeyId'])||!isset($_POST['requestToken'])||!isset($_POST['requestSalt'])) {
			 http_response_code(400);
			echo json_encode(array("message" => "Invalid request. check request parameters")); 
		} else {
			echo json_encode(authenticate($_POST));	
		}

		break;
	default:
		echo json_encode(array("message" => "Invalid request. check argument for action."));

}



function authenticate($request) {
	//function to authenticate security string
	$userName = urldecode($request['userName']);
	$apiKeyId = urldecode($request['apiKeyId']);
	$requestSalt = urldecode($request['requestSalt']);
	$requestToken = urldecode($request['requestToken']);
	$db_object = new Database();
	$db_object->getDatabaseConnection();
	$query = "select password,api_key from users where username ='".$userName."' and api_key_id ='".$apiKeyId."'";
	$response = $db_object->executeQuery($query); //get user details
	if($response['status']=="Success") {
		$row=mysqli_fetch_assoc($response['message']);
		if(empty($row)) { //if empty then invalid 
			$response['status'] = "Failed";
			$response['message'] = "Invalid Credentials";
		} else { //form the security string 
			$response['message'] = "Auth Credential request Success";
			$secureString =  crypt($row['password'].'|'.$requestSalt.'|'.$row['api_key'],'$2a$10$'.$requestSalt.'$');
			if(hashEquals($requestToken,$secureString)) { //compare the user hash and the server generated
				$response['message'] = "Authentication Successful"; //success
			} else {
				$response['status'] = "Failed";
				$response['message'] = "Authentication Unsuccessful"; //failure
			}
		}

	}
	$db_object->closeDatabaseConnection();
	return $response;

}

function getAuthSalt($request) { 
	//function to return the system salt for user
	$userName = urldecode($request['userName']);
	$apiKeyId = urldecode($request['apiKeyId']);
	$db_object = new Database();
	$db_object->getDatabaseConnection();
	$query = "select salt from users where username ='".$userName."' and api_key_id ='".$apiKeyId."'";
	$response = $db_object->executeQuery($query); //see whether the user exists or not
	if($response['status']=="Success") {
		$authDetails=mysqli_fetch_assoc($response['message']);
		if(empty($authDetails)) { //if no then failed
			$response['status'] = "Failed";
			$response['message'] = "Invalid Credentials";
		} else { //if yes then return salt
			$response['message'] = "Auth Salt Obtained Successfully";
			$response['salt'] =  $authDetails['salt'];
		}

	}
	$db_object->closeDatabaseConnection();
	return $response;

}

function hashEquals($str1, $str2) { //function to compare hashes
	if(strlen($str1) != strlen($str2)) {
		return false;
	} else {
		$res = $str1 ^ $str2;
		$ret = 0;
		for($i = strlen($res) - 1; $i >= 0; $i--) {
			$ret |= ord($res[$i]);
		}
		return !$ret;
	}
}

