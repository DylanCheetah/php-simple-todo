<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Is the task data already loaded?
if(!isset($task)) {
    // Load the task data
    $task = getTask($objectId);
}
?>
<div class="row m-1" id="task<?php echo $task['id']; ?>">
    <div class="col card bg-white">
        <div class="card-body row">
            <div class="col-7 nav-link">
                <div><?php echo $task['name']; ?></div>
                <div class="text-secondary"><?php echo (new Datetime($task['due_date']))->format("m/d/Y h:m A"); ?></div>
            </div>
            <div class="col-2 m-1 row">
                <button class="col btn btn-warning" 
                    hx-get="/tasks/<?php echo $task['id']; ?>/update/"
                    hx-swap="outerHTML" hx-target="#task<?php echo $task['id']; ?>">
                    Edit
                </button>
            </div>
            <form class="col m-1 row" 
                hx-post="/tasks/<?php echo $task['id']; ?>/delete/"
                hx-indicator="taskDeleteBtn<?php echo $task['id']; ?>">
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                <button class="col btn btn-danger">
                    <span class="spinner-border spinner-border-sm htmx-indicator"
                        id="taskDeleteBtn<?php echo $task['id']; ?>">
                        <span class="visually-hidden">Loading...</span>
                    </span>
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>
