<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Check if the user is logged in
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header("Location: /accounts/sign-in/?redirect_to=$path");
    exit();
}

// Is the request an HTMX request?
if(isset($_SERVER['HTTP_HX_REQUEST'])) {
    // Send just the tasks view
    require_once(__DIR__ . '/tasks-view.php');
    exit();
}

// Send page header
$viewName = 'Todo List Details';
require_once(__DIR__ . '/../inc/page-header.php');

// Send todo list details
require_once(__DIR__ . '/todo-list-info.php');
?>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 col-sm-12 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">New Task</h1>
            <hr/>
            <?php require_once(__DIR__ . '/task-create.php') ?>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 col-sm-12 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">Tasks</h1>
            <hr/>
            <?php require_once(__DIR__ . '/tasks-view.php') ?>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
