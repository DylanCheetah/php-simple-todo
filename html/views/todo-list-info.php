<?php
// Load todo list functions
require_once(__DIR__ . '/../inc/todo-list.php');

// Get the todo list info
$todoList = getTodoList($objectId);
?>
<div class="row justify-content-center" id="todoListInfo">
    <h1 class="col-5 m-1"><?php echo htmlspecialchars($todoList['name']); ?></h1>
    <button class="col-1 m-1 btn btn-warning" 
        hx-get="/todo-lists/<?php echo $objectId; ?>/update/" 
        hx-swap="outerHTML" hx-target="#todoListInfo">Edit</button>
</div>
