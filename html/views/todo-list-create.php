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
        // Create the todo list
        if(createTodoList($name)) {
            // Redirect to homepage
            header('HX-Redirect: /');
            exit();
        }

        // Handle todo list creation errors
        $errors[] = 'Todo list names must be unique per user.';
    }
}
?>
<form hx-post="/todo-lists/create/" hx-swap="outerHTML" 
    hx-indicator="#todoListCreateBtn">
    <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
    <?php
    // Did any errors occur?
    if(isset($errors)) {
        // Display each error
        foreach($errors as $error) {
            ?>
            <div class="row m-1">
                <div class="col alert alert-danger"><?php echo $error; ?></div>
            </div>
            <?php
        }
    }
    ?>
    <div class="row m-1">
        <label class="col-2" for="name">Name:</label>
        <div class="col-10"><input class="form-control" type="text" id="name" name="name" 
            maxlength="64" required/></div>
    </div>
    <div class="row m-1 mt-4 justify-content-end">
        <button class="col-2 btn btn-primary">
            <span class="spinner-border spinner-border-sm htmx-indicator" 
                id="todoListCreateBtn">
                <span class="visually-hidden">Loading...</span>
            </span>
            Create
        </button>
    </div>
</form>
