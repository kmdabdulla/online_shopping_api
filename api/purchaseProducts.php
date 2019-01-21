<?php

header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header('Content-Type: application/json');

include_once "database_connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') { //prevent GET request from accesing the methods
	http_response_code(405);
	echo json_encode(array("message" => "GET method not allowed"));
	exit;
}

//check for authentication token
if(!isset($_POST['userName'])||!isset($_POST['apiKeyId'])||!isset($_POST['requestToken'])||!isset($_POST['requestSalt'])) {
	http_response_code(400);
	echo json_encode(array("message" => "Invalid request. check authentication parameters"));
	exit;
} else {
	$authStatus = authenticate($_POST);
	if(!$authStatus) {
		http_response_code(401);
		echo json_encode(array('message'=>'Authentication Failed'));
		exit;
	}

}

if(!isset($_POST['action'])) { //check whether action parameter exists
	http_response_code(400);
	echo json_encode(array("message" => "Invalid request."));
	exit;
}

switch($_POST['action']) {

	case createCart:
		echo json_encode(createCart($_POST['userName'])); 
		break;

	case addProductsToCart:
		//check whether productList parameter exists and it is of json format.
		if((!isset($_POST['productList'])) || !isJson(urldecode($_POST['productList']))) { 
			http_response_code(400);
			echo json_encode(array("message" => "productList parameter is missing or format is invalid"));
		} else {
			echo json_encode(addProductsToCart(urldecode($_POST['productList']),$_POST['userName']));
		}
		break;

	case checkOutCart:
		echo json_encode(checkOutCart($_POST['userName'])); 
		break;
	case emptyCart:
		echo json_encode(emptyCart($_POST['userName'])); 
		break;
	default:

		http_response_code(400);
		echo json_encode(array("message" => "Invalid Action."));
}

function createCart($userName) {
	$db_object = new Database();
	$connection = $db_object->getDatabaseConnection();
	$query = "select carts.cart_id,users.user_id,users.username from carts join users on carts.user_id = users.user_id where users.username ="."'".$userName."'"." and carts.cart_status != 'checkOutCompleted'";
	$response = $db_object->executeQuery($query); //check if a cart of status InProcess or empty exists for the user
	if($response['status']=="Success") {
		$cartDetails=mysqli_fetch_assoc($response['message']);
		if(empty($cartDetails)) { //if not then create a cart
			$query = "select user_id from users where username ="."'".$userName."'";
			$response = $db_object->executeQuery($query);
			if($response['status']=="Success") {	
				$userDetails=mysqli_fetch_assoc($response['message']);
				$query = "insert into carts (cart_status,user_id) VALUES('empty','".$userDetails['user_id']."')";
				$response = $db_object->executeQuery($query);
				if($response['status']=="Success") { 
					$response['cartId'] = mysqli_insert_id($connection);
					$response['message'] = "Cart Created Successfully";	
				}
			}
		} else { //if yes then return the cart id
			$response['status'] = "Cart Exists";
			$response['cartId'] = $cartDetails['cart_id'];
			$response['message'] = "Cart ". $cartDetails['cart_id']." already exists for user ".$cartDetails['username'];
		}
	}
	$db_object->closeDatabaseConnection();
	return $response;
}

function addProductsToCart($productList,$userName) {
	$productList = json_decode($productList,true);
	$db_object = new Database();
	$connection = $db_object->getDatabaseConnection();
	$totalPrice = 0;
	$cartId= NULL;
	$productsNoStock = array();
	$requestedProductsNotInDb = array();
	$productsInDb = array();
	if(!empty($productList) && is_array($productList)) { //check if it is not empty and it is an array
		$query = "select carts.cart_id,users.user_id from carts join users on carts.user_id = users.user_id where users.username ="."'".$userName."'"." and carts.cart_status != 'checkOutCompleted'";
		$response = $db_object->executeQuery($query); 
		if($response['status']=="Success") { //see whether cart exists for the user or not
			$cartDetails=mysqli_fetch_assoc($response['message']);
			if(empty($cartDetails)) { //if no then return failure message
				$response['status'] = "Failed";
				$response['message'] = "No empty Cart is found. Please create a cart to add Products";	 	
				goto respond;
			} else { 
				$cartId = $cartDetails['cart_id']; //get the cart id otherwise
			} 
			$requestedProducts = array_keys($productList);
			$query = "select title,price,inventory_count from products where title in ".'('."'" . implode ( "', '", $requestedProducts) . "'".')';
			$response = $db_object->executeQuery($query); //select the requested products from db
			if($response['status']=="Success") {
				while($row=mysqli_fetch_assoc($response['message'])) {
					$productsInDb[]=$row['title'];
					if($productList[$row['title']]<=$row['inventory_count']) { //check for inventory count
						$productAddedToCart[$row['title']]=$productList[$row['title']]; 											$totalPrice += $row['price']*$productList[$row['title']]; //calculate total price

					} else {

						$productsNoStock[] = $row['title'];
					}

				}
				$requestedProductsNotInDb = array_diff($requestedProducts,$productsInDb); //to check if any of the requeted products do not exists in the system
				if(empty($productAddedToCart)) { //if no requested product is found in system
					$response['status'] = "Failed";
					$response['message'] = "All the requested items are not available at the moment";
					goto respond;

				}
				$query = "update carts set items_list ="."'". json_encode($productAddedToCart)."'".",total_price = ".$totalPrice.",cart_status = 'InProcess' where cart_id = ". $cartId;
				$response = $db_object->executeQuery($query); //update the cart details
				if($response['status']=="Success") {
					$response['productAddedToCart'] = json_encode($productAddedToCart);
					if(empty($requestedProductsNotInDb) && empty($productsNoStock)) { //complete success case
						$response['message'] = "Items Added to Cart Successfully";
						$response['totalPrice'] = $totalPrice;
					} else if (!empty($requestedProductsNotInDb) || !empty($productsNoStock)) { //if some  of the products do not exists in the system
						$response['status'] = "Request Not Complete";
						$response['message'] = "Some or all of the requested products are not added to cart due to no stock or unavailability at the moment";
						if(!empty($requestedProductsNotInDb)) {
							$response['productsUnavailable'] = json_encode($requestedProductsNotInDb);  //requested products unavailable in system
						} 
						if(!empty($productsNoStock)) {
							$response['productsNoStock'] = json_encode($productsNoStock); //products that currently not in stock

						}
					}
				}


			}
		}
	} else { //if request parameter is empty or invalid
		$response['status'] = "Failed";
		$response['message'] = "Invalid Product List format..!.";

	}


	$db_object->closeDatabaseConnection();
respond:
	return $response;
}

function checkOutCart($userName) {
	$db_object = new Database();
	$db_object->getDatabaseConnection();
	$cartId = "";
	$query = "select carts.cart_id,carts.items_list,carts.total_price from carts join users on carts.user_id = users.user_id where users.username ="."'".$userName."'"." and carts.cart_status = 'InProcess'";
	$response = $db_object->executeQuery($query);//to check whether any cart is in progress for that user
	if($response['status']=="Success") {
		$cartDetails=mysqli_fetch_assoc($response['message']);
		if(empty($cartDetails)) { //if no retrun error message
			$response['status'] = "Failed";
			$response['message'] = "No Cart in progress is found";
			goto respond;
		} else { //if yes get cart id and total price
			$cartId = $cartDetails['cart_id'];
			$totalAmount = $cartDetails['total_price'];
		}
		$products =  json_decode($cartDetails['items_list'],true);
		$productTitles = array_keys($products);
		$query = "select product_id,title,inventory_count from products where title in ".'('."'" . implode ( "', '", $productTitles) . "'".')';	
		$response = $db_object->executeQuery($query); //select the added products from db
		if($response['status']=="Success") {
			while($row=mysqli_fetch_assoc($response['message'])) {
				$quantity = $row['inventory_count']-$products[$row['title']];
				$query_string .= "('".$row['product_id']."','".$quantity."'),";
			}
			$query_string = rtrim($query_string,", ");
			$query = "INSERT INTO products (product_id,inventory_count) VALUES ".$query_string." ON DUPLICATE KEY UPDATE product_id=VALUES(product_id),inventory_count=VALUES(inventory_count)";
			$response = $db_object->executeQuery($query); //update the inventory count
			if($response['status']=="Success") {
				$query = "update carts set cart_status = 'cartCheckedOut' where cart_id = $cartId"; //update the cart status
				$response = $db_object->executeQuery($query);
				if($response['status']=="Success") {
					$response['message'] = "Checkout completed";
					$response['TotalAmount'] = $totalAmount;
				} else {
					$response['message'] = "Cart Checkout Failed";
				}
			} else {

				$response['message'] = "Cart Checkout Failed";
			}
		}
	}
	$db_object->closeDatabaseConnection();
respond:
	return $response;
}


function emptyCart($userName) {

	$db_object = new Database();
	$connection = $db_object->getDatabaseConnection();
	$query = "select user_id from users where username ="."'".$userName."'"; //get the user id 
	$response = $db_object->executeQuery($query);
	if($response['status']=="Success") {
		$userDetails=mysqli_fetch_assoc($response['message']);
		$userId = $userDetails['user_id'];
		$query = "update carts set items_list = '',total_price = 0,cart_status='empty' where cart_status='InProcess' and user_id = "."'".$userId."'";
		$response = $db_object->executeQuery($query); //update only if an InProcess cart exists
		if($response['status']=="Success") {
			if(mysqli_affected_rows($connection) >0 ) { //check if any row is affected in db 
				$response['message'] = "Cart Emptied Successfully";	//if yes then the cart is emptied 
			} else {
				$response['message'] = "No cart currently in process of checkout to empty it";	//if not then return error message
			}
		}
	}
	$db_object->closeDatabaseConnection();
	return $response;
}

function authenticate($request) {
	//authenticate user credentials
	$post =  array('action'=>'authenticate','userName'=>$request['userName'],'apiKeyId'=>$request['apiKeyId'],'requestSalt'=>$request['requestSalt'],'requestToken'=>$request['requestToken']);
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
		return false; //on failure
	}
	return true; //on success
}

function isJson($string) { //to check a string whether it is json or not
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}


