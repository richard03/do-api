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
	 * Extract POST item
	 */
	function getPostItem($itemName) {
		if (isset($_POST[$itemName])) {
			return strval($_POST[$itemName]);
		} else {
			return '';
		}
	}


