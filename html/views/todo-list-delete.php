<?php
// Load todo list functions
require_once(__DIR__ . '/../inc/todo-list.php');

// Delete the given todo list and redirect to the homepage
deleteTodoList($objectId);
header('HX-Redirect: /');
?>
