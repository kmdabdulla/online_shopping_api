<?php

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

include_once "database_connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //To prevent POST request from accessing
	http_response_code(405);
	echo json_encode(array("message" => "POST method not allowed"));
	exit;
}

//checking whether the necessary security tokens are given by user
if(!isset($_SERVER['HTTP_USERNAME'])||!isset($_SERVER['HTTP_APIKEYID'])||!isset($_SERVER['HTTP_REQUESTTOKEN'])||!isset($_SERVER['HTTP_REQUESTSALT'])) {
	http_response_code(400);
	echo json_encode(array("message" => "Invalid request. check authentication parameters"));
	exit;
} else {
	$authStatus = authenticate($_SERVER);
	if(!$authStatus) {         
		http_response_code(401);
		echo json_encode(array('message'=>'Authentication Failed'));
		exit;
	} 

}
//check whether action parameter is specified. action parameter holds the method name
if(!isset($_GET['action'])) {
	http_response_code(400);
	echo json_encode(array("message" => "Invalid request. Missing action parameter "));
	exit;
}

switch($_GET['action']) {

	case getProductsByTitle:
		//check whether user has sent productTitles parameter and whether it is in json format.
		if(!isset($_GET['productTitles']) || !isJson($_GET['productTitles'])) {
			http_response_code(400);
			echo json_encode(array("message" => "productTitles parameter is missing or format is invalid"));
		} else {
			//send response to the user
			echo json_encode(getProductsByTitleOrId(urldecode($_GET['productTitles']),'byTitle'));
		}
		break;
	case getProductsById:
		//check whether user has sent productIds parameter and whether it is in json format.
		if(!isset($_GET['productIds']) || !isJson($_GET['productIds'])) {
			http_response_code(400);
			echo json_encode(array("message" => "productIds parameter is missing or format is invalid"));
		} else {
			//send response to the user
			echo json_encode(getProductsByTitleOrId(urldecode($_GET['productIds']),'byId'));
		}
		break;
	case getAllProducts:
		$onlyAvailableInventory = false; //default value is false
		if(isset($_GET['onlyAvailableInventory'])) { //check whether user has sent onlyAvailableInventory parameter
			$onlyAvailableInventory = true; //if yes then set $onlyAvailableInventory it to true
		}
		echo json_encode(getAllProducts($onlyAvailableInventory)); //send response to user
		break;
	case getCartDetails:
		$cartId = NULL; //default value of cart id is null
		$cartStatus = NULL;//default value of cart status is null
		if(isset($_GET['cartId'])) {
			$cartId = $_GET['cartId']; //if user sent the cart id set it to cartId
		}
		if(isset($_GET['cartStatus'])) {
			$cartStatus = $_GET['cartStatus']; //if user sent cartStatus then set it to cartStatus
		}
		echo json_encode(getCartDetails($cartId,$_SERVER['HTTP_USERNAME'],$cartStatus)); //send response to user
		break;
	default:
		http_response_code(400); //return invalid action for values other than specified for action variable.
		echo json_encode(array("message" => "Invalid Action."));	
}


function getProductsByTitleOrId($requestedProducts,$requestType) {
	//method to handle getProductByTitle and getProductById api calls
	$db_object = new Database();
	$db_object->getDatabaseConnection(); //get database connection
	$requestedProducts = json_decode($requestedProducts,true);
	if(!empty($requestedProducts) && is_array($requestedProducts)) { //check if requestedProducts is not empty and it is an array
		if($requestType=="byTitle") {
			$query = "select * from products where title in ".'('."'" . implode ( "', '", $requestedProducts) . "'".')';
		} else {
			$query = "select * from products where product_id in ".'('."'" . implode ( "', '", $requestedProducts) . "'".')';
		}
		$response = $db_object->executeQuery($query);//get the product details from the db
		if($response['status']=="Success") {
			while($row=mysqli_fetch_assoc($response['message'])) {
				if($requestType=="byTitle") {
					$productsInDb[] = $row['title']; //getting the product title if the request is getting product by title
				} else {
					$productsInDb[] = $row['product_id'];//getting the product ids if the request is getting product by id
				}
				$products[] = $row; // put the details in products array
			}
			$requestedItemsNotInDb = array_diff($requestedProducts,$productsInDb); //comparing to see whether some invalid product has been requested
			if(!empty($products)) {  //complete success all requested products are found
				$response['productDetails'] = json_encode($products); //holds requested products found in system
				$response['message'] = "Products details obtained successfully.";
			} else {
				$response['status'] = "Failed"; //failure case no requested products are found
				if($requestType=="byTitle") {
				$response['message'] = "No Products Found by the requested title(s)";
				} else {
				$response['message'] = "No Products Found by the requested Id(s)";
				}
			}
			if(!empty($requestedItemsNotInDb)&&!empty($products)){ //if some products exists and some do not
				$response['status'] = "Partial Response";
				$response['message'] = "Sorry, Some Products are not in our system";
				$response['productsNotInSystem'] = json_encode(array_values($requestedItemsNotInDb)); //holds products not in system. Product ids in the case of request is by id or title otherwise
			} 

		}
	} else { //sending faliure message if request parameter is invalid or empty
		$response['status'] = "Failed"; 
		if($requestType=="byTitle") {
			$response['message'] = "productTitles parameter is empty or improper.";
		} else {
			$response['message'] = "productIds parameter is empty or improper.";
		}

	}
	$db_object->closeDatabaseConnection(); //close database connection
	return $response;
}

function getAllProducts($onlyAvailableInventory) {
	//this methods gives all the product details
	$db_object = new Database();
	$db_object->getDatabaseConnection();
	$query = "select * from products";
	if($onlyAvailableInventory) { //if true then include only products with available inventory
		$query .= " where inventory_count != 0";
	}
	$response = $db_object->executeQuery($query);
	if($response['status']=="Success") {
		while($row=mysqli_fetch_assoc($response['message'])) {
			$products[] = $row;
		}
		if(!empty($products)) {
			$response['productDetails'] = json_encode($products); //product details
			$response['message'] = "Products details obtained successfully.";
		} else { //if no products found
			$response['message'] = "Sorry, No Products Found at this moment.";
		}
	}
	$db_object->closeDatabaseConnection();
	return $response;

}

function getCartDetails($cartId,$userName,$cartStatus) {
	
	$db_object = new Database();
	$db_object->getDatabaseConnection();
	if(empty($cartId) && empty($cartStatus)) { //if no parameter is specified
		$query = "select carts.* from carts left join users on carts.user_id = users.user_id where users.username ="."'".$userName."'";
	} else if(!empty($cartId)) { //if cart id is specified
		$query = "select carts.* from carts left join users on carts.user_id = users.user_id where users.username ="."'". $userName."'"." and carts.cart_id = "."'".$cartId."'";
	} else if(!empty($cartStatus)){  //if cart status is specified
		$query = "select carts.* from carts left join users on carts.user_id = users.user_id where users.username ="."'". $userName."'"." and carts.cart_status = "."'".$cartStatus."'";
	}
	$response = $db_object->executeQuery($query);
	if($response['status']=="Success") {
		while($row=mysqli_fetch_assoc($response['message'])) {
                                $cartDetails[] = $row; //get cart details
                  }
		if(empty($cartDetails)) {
			$response['status'] = "Failed";
			if(!empty($cartId)) { //if cart id is invalid
				$response['message'] = "Cart Id is invalid.";
			} else if(!empty($cartStatus)){ //if no records found for cart status
				$response['message'] = "No cart found matching the given cart status.";
			} else {
				$response['message'] = "No cart found for this user."; 
			}
		} else {
			$response['message'] = "Cart Details Obtained Successfully";
			$response['cartDetails'] = json_encode($cartDetails); //holds cart details

		}
	}
	$db_object->closeDatabaseConnection();
	return $response;
}

function authenticate($request) {
	//this funciton authenticates the security token
	$post =  array('action'=>'authenticate','userName'=>$request['HTTP_USERNAME'],'apiKeyId'=>$request['HTTP_APIKEYID'],'requestSalt'=>$request['HTTP_REQUESTSALT'],'requestToken'=>$request['HTTP_REQUESTTOKEN']);
	$url = "http://localhost/api/authentication.php";
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_TIMEOUT, 3);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
	$response = curl_exec($curl);
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$response = json_decode($response,true);
	curl_close($curl);
	if($httpCode!=200 || $response['status']!="Success") {
		return false; //false if authentication failed
	} 
	return true; //true if authentication success
}

function isJson($string) { //check if a given string is json or not
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE); //returns true on success
}
?>
