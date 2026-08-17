<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the request a POST request?
if($method === 'POST') {
    // Validate form data
    if(!isset($_POST['username']) || !isset($_POST['password']) || 
        !isset($_POST['confirmPassword'])) {
        // Send 400 page
        require_once(__DIR__ . '/400.php');
        exit();
    }

    $username        = $_POST['username'];
    $password        = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $errors = array();

    if(strlen($username) > 64) {
        $errors[] = 'Username length must be less than 64 characters.';
    }

    if(strlen($password) < 8) {
        $errors[] = 'Password length must be at least 8 characters.';
    }

    if($password !== $confirmPassword) {
        $errors[] = 'The passwords must match.';
    }

    // Is the form valid?
    if(!count($errors)) {
        // Create the user account
        if(createUser($username, $password)) {
            // Redirect to homepage
            header('Location: /');
            exit();
        }

        // Handle account creation errors
        $errors[] = 'The username is taken. Please choose a different username.';
    }
}

// Send page header
$viewName = 'Sign-Up';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-6 m-2 card bg-light">
        <div class="card-body">
            <h1 class="card-title">Sign-Up</h1>
            <hr/>
            <?php
            // Are there any errors?
            if(isset($errors)) {
                // Display an alert for each error
                foreach($errors as $error) {
                    ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php
                }
            }
            ?>
            <form method="POST">
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                <div class="row m-1">
                    <label class="col-3" for="username">Username:</label>
                    <div class="col-9"><input class="form-control" id="username" name="username" 
                        type="text" maxlength="64" 
                        value="<?php echo isset($username) ? $username : ''; ?>" required/></div>
                </div>
                <div class="row m-1">
                    <label class="col-3" for="password">Password:</label>
                    <div class="col-9"><input class="form-control" id="password" name="password" 
                        type="password" minlength="8" required/></div>
                </div>
                <div class="row m-1">
                    <label class="col-3" for="confirmPassword">Confirm Password:</label>
                    <div class="col-9"><input class="form-control" id="confirmPassword" 
                        name="confirmPassword" type="password" minlength="8" required/></div>
                </div>
                <div class="row m-1 mt-4 justify-content-end">
                    <button class="col-2 btn btn-primary">Sign-Up</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
