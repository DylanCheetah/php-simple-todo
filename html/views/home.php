<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the current user signed in?
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header('Location: /php-simple-todo/accounts/sign-in/?redirect_to=/php-simple-todo/');
    exit();
}

// Is the request an htmx request?
if(isset($_SERVER['HTTP_HX_REQUEST'])) {
    // Send just the todo lists view
    require_once(__DIR__ . '/todo-lists-view.php');
    exit();
}

// Send page header
$viewName = 'Home';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 col-sm-12 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">New Todo List</h1>
            <hr/>
            <?php require_once(__DIR__ . '/todo-list-create.php'); ?>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 col-sm-12 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">Todo Lists</h1>
            <hr/>
            <?php require_once(__DIR__ . '/todo-lists-view.php') ?>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
