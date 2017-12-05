<?php

	function getFailJson($statusCode, $message = '') {
		return json_encode((object)array('status' => 'error', 'statusCode' => $statusCode, 'message' => $message));
	}

	/**
	 * Output the error message
	 */
	function fail($statusCode, $message = '') {
		header('HTTP/1.0 500 Internal Server Error');
		echo getFailJson($statusCode, $message);
	}


	// /**
	//  * fetch query result from DB
	//  */
	// function dbQuery($sqlQuery, $db_server, $db_user, $db_password, $db_name) {
	// 	// Create connection
	// 	$db_connection = mysqli_connect($db_server, $db_user, $db_password, $db_name);
	// 	// Check connection
	// 	if (!$db_connection || $db_connection->connect_error) {
	// 		fail('dbConnectionFailed', $db_connection->connect_error);
	// 	} else {
	// 		return $db_connection->query($sqlQuery);
	// 	}
	// 	$db_connection->close();
	// }


	/**
	 * Fetch list of object from SQL db
	 * using prepared statement
	 */
	function fetchList($db_statement) {
		$result = array();

		mysqli_stmt_execute($db_statement);

		$db_result = mysqli_stmt_get_result($db_statement);

		/* bind result variables */
		// mysqli_stmt_bind_result($db_statement, $id, $title, $acceptanceCriteria, $dueDate, $status, $priority);

		/* fetch values */
		while ($row = mysqli_fetch_array($db_result, MYSQLI_ASSOC)) {
			$result[] = (object)$row;
		}

		mysqli_stmt_close($db_statement);
		
		return $result;
	}

	/**
	 * Execute prepared statement and return result
	 */
	function executeStatement($db_statement) {
		mysqli_stmt_execute($db_statement);
		mysqli_stmt_close($db_statement);
		return (object)array('status' => 'OK');
	}


