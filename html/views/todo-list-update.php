<?php
// Load todo list functions
require_once(__DIR__ . '/../inc/todo-list.php');

// Is the request a POST request?
if($method === 'POST') {
    // Validate form data
    if(!isset($_POST['name'])) {
        // Send 400 page
        require_once(__DIR__ . '/400.php');
        exit();
    }

    $name = $_POST['name'];
    $errors = array();

    if(strlen($name) > 64) {
        $errors[] = 'Todo list names must be no more than 64 characters long.';
    }

    // Is the form valid?
    if(!count($errors)) {
        // Update the todo list
        if(updateTodoList($objectId, $name)) {
            // Redirect to the details page
            header("Location: /php-simple-todo/todo-lists/$objectId/");
            exit();
        }

        // Handle todo list creation errors
        $errors[] = 'Todo list names must be unique per user.';
    }
}

// Get todo list info
$todoList = getTodoList($objectId);
?>
<div class="row justify-content-center">
    <form class="col-6" id="todoListUpdateForm" 
        hx-post="/php-simple-todo/todo-lists/<?php echo $objectId; ?>/update/" 
        hx-swap="outerHTML" hx-indicator="#todoListUpdateBtn">
        <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
        <?php
        // Are there any errors?
        if(isset($errors)) {
            // Display all errors
            foreach($errors as $error) {
                ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php
            }
        }
        ?>
        <div class="row m-1">
            <label class="col-2" for="name">Name:</label>
            <div class="col-10">
                <input class="form-control" id="name" type="text" name="name" 
                    value="<?php echo htmlspecialchars($todoList['name']); ?>" 
                    maxlength="64" required/></div>
        </div>
        <div class="row m-1 mt-4 justify-content-end">
            <button class="col-2 m-1 btn btn-success">
                <span class="spinner-border spinner-border-sm htmx-indicator"
                    id="todoListUpdateBtn">
                    <span class="visually-hidden">Loading...</span>
                </span>
                Save
            </button>
            <button class="col-2 m-1 btn btn-danger" type="button" 
                hx-get="/php-simple-todo/todo-lists/<?php echo $objectId; ?>/info/"
                hx-swap="outerHTML" hx-target="#todoListUpdateForm">
                <span class="spinner-border spinner-border-sm htmx-indicator">
                    <span class="visually-hidden">Loading...</span>
                </span>
                Cancel
            </button>
        </div>
    </form>
</div>
