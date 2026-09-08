<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Get the todo list the task is associated with so we know which page to 
// redirect to
$todoList = getTask($objectId)['todo_list'];

// Delete the given task and redirect to the todo list details page
deleteTask($objectId);
header("HX-Redirect: /todo-lists/$todoList/");
exit();
?>
