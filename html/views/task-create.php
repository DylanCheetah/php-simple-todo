<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Is the request a POST request?
if($method === 'POST') {
    // Validate form data
    if(!isset($_POST['todoList']) ||
        !isset($_POST['name']) ||
        !isset($_POST['dueDate'])) {
        // Send 400 page
        require_once(__DIR__ . '/400.php');
        exit();
    }

    $todoList = $_POST['todoList'];
    $name = $_POST['name'];
    $dueDate = $_POST['dueDate'];
    $errors = array();
    
    if(strlen($name) > 64) {
        $errors[] = 'Task names must be no more than 64 characters long.';
    }

    // Is the form valid?
    if(!count($errors)) {
        // Create the task
        if(createTask($todoList, $name, $dueDate)) {
            // Redirect to the details page for the todo list
            header("HX-Redirect: /todo-lists/$todoList/");
            exit();
        }

        // Handle task creation errors
        $errors[] = 'Failed to create task.';
    }
}
?>
<form hx-post="/tasks/create/" hx-swap="outerHTML"
    hx-indicator="#taskCreateBtn">
    <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
    <input type="hidden" name="todoList" 
        value="<?php echo isset($objectId) ? $objectId : $todoList ?>"/>
    <?php
    // Are there any errors?
    if(isset($errors)) {
        // Show all errors
        foreach($errors as $error) {
            ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php
        }
    }
    ?>
    <div class="row m-1">
        <label class="col-2" for="taskName">Name:</label>
        <div class="col-10"><input class="form-control" id="taskName" 
            type="text" name="name" maxlength="64" required/></div>
    </div>
    <div class="row m-1">
        <label class="col-2" for="taskDueDate">Due Date:</label>
        <div class="col-10"><input class="form-control" id="taskDueDate"
            type="datetime-local" name="dueDate" required/></div>
    </div>
    <div class="row m-1 mt-4 justify-content-end">
        <button class="col-2 btn btn-primary">
            <span class="spinner-border spinner-border-sm htmx-indicator"
                id="taskCreateBtn">
                <span class="visually-hidden">Loading...</span>
            </span>
            Create
        </button>
    </div>
</form>
