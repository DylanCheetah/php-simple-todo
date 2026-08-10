<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the request a POST request?
if($method === 'POST') {
    // Sign out the user and redirect to the homepage
    signOut();
    header('Location: /');
    exit();
}

// Send page header
$viewName = 'Sign-Out';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-6 m-2 card bg-light">
        <div class="card-body">
            <h1>Sign-Out</h1>
            <hr/>
            <form method="POST">
                <div class="row">
                    <p class="col">Are you sure you want to sign out?</p>
                </div>
                <div class="row justify-content-end m-2 mt-4">
                    <button class="col-2 btn btn-primary">Sign-Out</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
