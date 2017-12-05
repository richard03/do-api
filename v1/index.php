<?php

include './config.php';
include './functions.php';

if ($cfg_env == 'DEV') {
	sleep(rand(0, 3)); // simulate random lag, for production deactivate this in config.php
}

// Attempt MySQL server connection. Assuming you are running MySQL server
$db_link = mysqli_connect($db_server, $db_user, $db_password, $db_name);
 
// Check connection
if($db_link === false) {
	die('ERROR: Could not connect. ' . mysqli_connect_error());
} 

// parse http request
$path = explode('/', $_SERVER['REQUEST_URI']);
array_shift($path); array_shift($path); array_shift($path); array_pop($path); // remove unwanted parts

$method = strtolower($_SERVER['REQUEST_METHOD']);


function writeTask($taskId, &$data, $db_link) {

	// 1. identify task
	if ( ($taskId == null) && isset($data['id']) ) {
		$taskId = $data['id'];
	}
	if ($taskId == null) {
		return (object)array('status' => 'error', 'statusCode' => 'idNotSpecified', 'message' => 'Task ID is not specified');
	}

	// 2. get current task data
	$db_query = 'SELECT * FROM tasks WHERE id = ? LIMIT 1';
	$db_statement_read = mysqli_prepare($db_link, $db_query);
	mysqli_stmt_bind_param($db_statement_read, "s", $taskId);
	mysqli_stmt_execute($db_statement_read);
	mysqli_stmt_bind_result($db_statement_read, $id, $title, $acceptanceCriteria, $dueDate, $status, $priority);

	if (mysqli_stmt_fetch($db_statement_read)) {
		$result = (object)array('id' => $id, 'title' => $title, 'acceptance_criteria' => $acceptanceCriteria, 'due_date' => $dueDate, 'status' => $status, 'priority' => $priority);
	}
	mysqli_stmt_close($db_statement_read);

	// 3. validate inputs and update data
	if (isset($data['id']) && ($data['id'] != null)) {
		$id = $data['id'];
	} else {
		$id = $taskId;
	}
	if (isset($data['title']) && ($data['title'] != null)) {
		$title = $data['title'];
	}
	if (isset($data['acceptance_criteria']) && ($data['acceptance_criteria'] != null)) {
		$acceptanceCriteria = $data['acceptance_criteria'];
	}
	if (isset($data['due_date']) && ($data['due_date'] != null)) {
		$dueDate = $data['due_date'];
	}
	if (isset($data['status']) && ($data['status'] != null)) {
		$status = $data['status'];
	}
	if (isset($data['priority']) && ($data['priority'] != null)) {
		$priority = $data['priority'];
	}

	// 4. save updated data to db
	$db_query = 'REPLACE INTO tasks (id, title, acceptance_criteria, due_date, status, priority) VALUES (?, ?, ?, ?, ?, ?)';
	$db_statement = mysqli_prepare($db_link, $db_query);
	mysqli_stmt_bind_param($db_statement, "sssssi", $id, $title, $acceptanceCriteria, $dueDate, $status, $priority);
	
	if (mysqli_stmt_execute($db_statement)) {
		mysqli_stmt_close($db_statement);
		return (object)array('status' => 'OK');
	} else {
		return (object)array('status' => 'error', 'statusCode' => 'dbInsertFailed', 'message' => 'Database insert failed');
	}
}

// send the output headers
header('Content-Type: application/json');


// start the output
if (isset($path[0])) {
	switch ($path[0]) {
		case 'tasks':
			if (isset($path[1])) {
				$taskId = $path[1];
				switch ($method) {
					case 'get': 
						$db_query = 'SELECT * FROM tasks WHERE id = ? AND NOT status = "deleted" LIMIT 1';
						$db_statement = mysqli_prepare($db_link, $db_query);
						mysqli_stmt_bind_param($db_statement, "s", $taskId);
						$results = fetchList($db_statement);
						echo json_encode($results[0]);
						break;
					case 'post': 
						echo json_encode(writeTask($taskId, $_POST, $db_link));
						break;
					case 'delete':
						$db_query = 'UPDATE tasks SET status = "deleted" WHERE id = ?';
						$db_statement = mysqli_prepare($db_link, $db_query) or die(mysqli_error($db_link));
						mysqli_stmt_bind_param($db_statement, "s", $taskId);
						echo json_encode(executeStatement($db_statement));
						break;
					default: fail('undefinedMethod');
				}
			} else {
				switch ($method) {
					case 'post':
						echo json_encode(writeTask(null, $_POST, $db_link));
						break;
					case 'get':
						$db_query = 'SELECT * FROM tasks WHERE NOT status = "deleted" ORDER BY priority DESC LIMIT 999';
						$db_statement = mysqli_prepare($db_link, $db_query);
						echo json_encode(fetchList($db_statement));
						break;
					default: fail('undefinedMethod');
				}
			}
			break;
		default: fail('undefinedResource', "Method '$path[0]' doesn't exist.");
	}
} else fail('missingResourceLocator');

// Close connection
mysqli_close($db_link);

