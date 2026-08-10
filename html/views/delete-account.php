<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the request is a POST request?
if($method === 'POST') {
    // Delete the current user and redirect to the homepage
    deleteUser($_SESSION['userId']);
    header('Location: /');
    exit();
}

// Send page header
$viewName = 'Delete Account';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-6 m-2 card bg-light">
        <div class="card-body">
            <h1>Delete Account</h1>
            <hr/>
            <form method="POST">
                <div class="row">
                    <p class="col">Are you sure you want to permanently delete your account?</p>
                </div>
                <div class="row justify-content-end m-2 mt-4">
                    <button class="col-3 btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
