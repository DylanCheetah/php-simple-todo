<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Is the request a POST request
if($method === 'POST') {
    // Validate form data
    if(!isset($_POST['name']) ||
        !isset($_POST['dueDate'])) {
        // Send 400 page
        require_once(__DIR__ . '/400.php');
        exit();
    }

    $name    = $_POST['name'];
    $dueDate = $_POST['dueDate'];
    $errors = array();

    if(strlen($name) > 64) {
        $errors[] = 'Task names must be no more than 64 characters long.';
    }

    // Is the form valid?
    if(!count($errors)) {
        // Update the task
        if(updateTask($objectId, $name, $dueDate)) {
            // Redirect to task info page
            header("Location: /tasks/$objectId/");
            exit();
        }

        // Handle errors
        $errors[] = 'Failed to update task.';
    }
}

// Get task info
$task = getTask($objectId);
?>
<div class="row m-1" id="taskUpdateForm<?php echo $objectId; ?>">
    <div class="col card bg-light">
        <div class="card-body">
            <form hx-post="/tasks/<?php echo $objectId; ?>/update/"
                hx-swap="outerHTML" hx-target="#taskUpdateForm<?php echo $objectId; ?>"
                hx-indicator="#taskSaveBtn">
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                <div class="row m-1">
                    <label class="col-2" for="name">Name:</label>
                    <div class="col-10"><input class="form-control" id="name" type="text" 
                        name="name" value="<?php echo htmlspecialchars($task['name']); ?>" 
                        maxlength="64" required/></div>
                </div>
                <div class="row m-1">
                    <label class="col-2" for="dueDate">Due Date:</label>
                    <div class="col-10"><input class="form-control" id="dueDate" 
                        type="datetime-local" name="dueDate" 
                        value="<?php echo $task['due_date']; ?>" required/></div>
                </div>
                <div class="row m-1 mt-4 justify-content-end">
                    <button class="col-2 m-1 btn btn-success">
                        <span class="spinner-border spinner-border-sm htmx-indicator"
                            id="taskSaveBtn">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                        Save
                    </button>
                    <button class="col-2 m-1 btn btn-danger" 
                        hx-get="/tasks/<?php echo $objectId; ?>/"
                        hx-swap="outerHTML" 
                        hx-target="#taskUpdateForm<?php echo $objectId; ?>">
                        <span class="spinner-border spinner-border-sm htmx-indicator">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
