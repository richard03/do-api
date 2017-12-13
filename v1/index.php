<?php
require 'flight/Flight.php';

require './config.php';
include './functions.php';

if ($cfg_env == 'DEV') {
	sleep(rand(0, 3)); // simulate random lag, for production deactivate this in config.php
}





// Attempt MySQL server connection. Assuming you are running MySQL server
Flight::register('db_link', 'mysqli', array($db_server, $db_user, $db_password, $db_name));
$db_link = Flight::db_link();



// $db_statement_login = mysqli_prepare($db_link, 'INSERT INTO sessions (sid, login, expiration_date) VALUES (?, ?, ?)');
// Flight::set('db_statement_login', $db_statement_login);

$db_statement_task_list = mysqli_prepare($db_link, 'SELECT * FROM tasks WHERE owner = ? AND NOT status = "deleted" ORDER BY priority DESC LIMIT 999');
Flight::set('db_statement_task_list', $db_statement_task_list);

$db_statement_task = mysqli_prepare($db_link, 'SELECT * FROM tasks WHERE id = ? AND owner = ? AND NOT status = "deleted" LIMIT 1');
Flight::set('db_statement_task', $db_statement_task);

$db_statement_task_insert = mysqli_prepare($db_link, 'REPLACE INTO tasks (id, title, owner, acceptance_criteria, due_date, status, priority) VALUES (?, ?, ?, ?, ?, ?, ?)');
Flight::set('db_statement_task_insert', $db_statement_task_insert);

$db_statement_task_delete = mysqli_prepare($db_link, 'UPDATE tasks SET status = "deleted" WHERE id = ? AND owner = ?');
Flight::set('db_statement_task_delete', $db_statement_task_delete);




// list tasks
Flight::route('GET /tasks/', function () {
	$db_statement = Flight::get('db_statement_task_list');
	mysqli_stmt_bind_param($db_statement, "s", getUserFromRequest());
	Flight::json(fetchList($db_statement));
});
// create task
Flight::route('POST /tasks/', function () {
	if ($_POST['id'] == null) {
		Flight::json(getFailDataObject('idNotSpecified', 'Task ID is not specified') );
	}
	$id = $_POST['id'];
	$title = getPostItem('title');
	$owner = getPostItem('owner');
	$acceptanceCriteria = getPostItem('acceptance_criteria');
	$dueDate = getPostItem('due_date');
	$status = getPostItem('status');
	$priority = getPostItem('priority');
	$db_statement = Flight::get('db_statement_task_insert');
	mysqli_stmt_bind_param($db_statement, "ssssssi", $id, $title, $owner, $acceptanceCriteria, $dueDate, $status, $priority);
	if (mysqli_stmt_execute($db_statement)) {
		mysqli_stmt_close($db_statement);
		Flight::json(getSuccessDataObject());
	} else {
		Flight::json(getFailDataObject('dbInsertFailed', 'Database insert failed'));
	}
});
// get task
Flight::route('GET /tasks/@taskId/', function ($taskId) {
	$db_statement = Flight::get('db_statement_task');
	mysqli_stmt_bind_param($db_statement, "ss", $taskId, getUserFromRequest());
	$results = fetchList($db_statement);
	if (isset($results[0])) {
		Flight::json($results[0]);
	} else {
		Flight::json((object)array());
	}
});
// update task
Flight::route('POST /tasks/@taskId/', function ($taskId) {

	if ($taskId == null) {
		Flight::json(getFailDataObject('idNotSpecified', 'Task ID is not specified') );
	}
	$title = getPostItem('title');
	$owner = getPostItem('owner');
	$acceptanceCriteria = getPostItem('acceptance_criteria');
	$dueDate = getPostItem('due_date');
	$status = getPostItem('status');
	$priority = getPostItem('priority');

	$db_statement = Flight::get('db_statement_task');
	mysqli_stmt_bind_param($db_statement, "ss", $taskId, getUserFromRequest());
	mysqli_stmt_execute($db_statement);
	$results = fetchList($db_statement);
	if (isset($results[0])) { // record exists
		$result = $results[0];
		if ($title == '') $title = $result->title;
		if ($owner == '') $owner = $result->owner;
		if ($acceptanceCriteria == '') $acceptanceCriteria = $result->acceptance_criteria;
		if ($dueDate == '') $dueDate = $result->due_date;
		if ($status == '') $status = $result->status;
		if ($priority == '') $priority = $result->priority;
	} else {
		Flight::json(getFailDataObject('dbUpdateFailed', 'Database record doesn\'t exist'));
	}
	// mysqli_stmt_close($db_statement);

	$db_statement = Flight::get('db_statement_task_insert');
	mysqli_stmt_bind_param($db_statement, "ssssssi", $taskId, $title, $owner, $acceptanceCriteria, $dueDate, $status, $priority);
	if (mysqli_stmt_execute($db_statement)) {
		// mysqli_stmt_close($db_statement);
		Flight::json(getSuccessDataObject());
	} else {
		Flight::json(getFailDataObject('dbUpdateFailed', 'Database update failed'));
	}
});
// delete task
Flight::route('DELETE /tasks/@taskId/', function ($taskId) {
	$db_statement = Flight::get('db_statement_task_delete');
	mysqli_stmt_bind_param($db_statement, "ss", $taskId, getUserFromRequest());
	if (mysqli_stmt_execute($db_statement)) {
		// mysqli_stmt_close($db_statement);
		Flight::json(getSuccessDataObject());
	} else {
		Flight::json(getFailDataObject('dbDeleteFailed', 'Deleting item ' . $taskId . ' failed'));
	}
});



Flight::start();
