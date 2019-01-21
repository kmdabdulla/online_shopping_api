<?php

class Database {
	//database connection parameters
	private $mysql_servername =  "localhost";
	private $mysql_username = "root";
	private $mysql_password = "root";
	private $mysql_database = "shopping_api";
	private $database_connection;

	public function getDatabaseConnection() {
		//to get database connection
		$this->database_connection = mysqli_connect($this->mysql_servername,$this->mysql_username,$this->mysql_password,$this->mysql_database);
		if (!$this->database_connection) {
			echo json_encode(array("message"=>"Database connection failed: "));
		}
		
		return $this->database_connection;
	}    


	public function executeQuery($query) {
		//to execute a given query
		$query_response['status'] = "Success";
		$query_response['message'] = NULL;
		$query_result = mysqli_query($this->database_connection, $query);
		if (!$query_result) {	//if failed returns error message
			$query_response['status'] = "Failed";
			$query_response['message'] = "Operation Failed. please try after some time";//.mysqli_error($this->database_connection);for db error message
		} else { //on success returns the result object in message
			$query_response['message'] = $query_result;
		}
		return $query_response;
	}

	public function closeDatabaseConnection() {
		mysqli_close($this->database_connection); //closes the database connection
	}
}
?>
