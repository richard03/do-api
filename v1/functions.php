<?php


	function getSuccessDataObject($message = '') {
		return (object)array('status' => 'OK', 'message' => $message);
	}

	function getFailDataObject($statusCode, $message = '') {
		return (object)array('status' => 'error', 'statusCode' => $statusCode, 'message' => $message);
	}


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

	/**
	 * Extract POST item from Flight request object
	 */
	function getPostItem(&$request, $itemName) {
		if (isset($request->data[$itemName])) {
			return strval($request->data[$itemName]);
		} else {
			return '';
		}
	}

	/**
	 * Generate random ID
	 */
	function getRandomId() {
		$maxId = 9999999999999999;
		return rand(0, $maxId);
	}

	/**
	 *
	 */
	function getUserFromRequest() {
		if ( !isset( $_SERVER['HTTP_AUTHORIZATION'] ) || ( $_SERVER['HTTP_AUTHORIZATION'] == '') ) {
			return null;
		} else {
			$parts = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
			$token = $parts[1];
			$response = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$token);
			$data = json_decode($response, true);
			return $data['email'];
		}
	}

