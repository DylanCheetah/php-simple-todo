<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the user signed in?
if(!isSignedIn()) {
    // Redirect to sign-in page
    header('Location: /accounts/sign-in/?redirect_to=/accounts/sign-out/');
    exit();
}

// Is the request a POST request?
if($method === 'POST') {
    // Sign out the user and redirect to the homepage
    signOut();
    header('Location: /');
    exit();
}

// Send page header
$viewName = 'Sign Out';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8 col-sm-12 m-2 card bg-light">
        <div class="card-body">
            <h1>Sign Out</h1>
            <hr/>
            <form method="POST">
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                <div class="row">
                    <p class="col">Are you sure you want to sign out?</p>
                </div>
                <div class="row justify-content-end m-2 mt-4">
                    <button class="col-lg-2 col-md-3 col-sm-12 btn btn-primary">Sign Out</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
