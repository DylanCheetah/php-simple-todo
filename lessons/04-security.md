# Lesson 04: Security

Now that we have configured authentication for our todo list website, let's address some security flaws. The first security flaw we need to address is cross-site request forgery. As our website is now, an attacker can create an HTML form with fields identical to one of ours and trick a user into submitting it to our website in order to perform actions within the context of the user. This is possible because while a user is signed into our website any requests sent to our website will also send along the session cookie for our website automatically. However, we can prevent this by setting up CSRF protection. First let's create `html/inc/csrf.php` with the following content:
```php
<?php
// CSRF Functions
// ==============
function csrfToken(): string {
    // Check if there is a CSRF token associated with the current session
    if(!isset($_SESSION['csrfToken'])) {
        // Generate new CSRF token and store it in the current session
        $_SESSION['csrfToken'] = random_bytes(32);
    }

    // Mask the CSRF token with random salt and return it
    $salt = random_bytes(32);
    $token = $_SESSION['csrfToken'] ^ $salt;
    $token = $token . $salt;
    return bin2hex($token);
}


function verifyCsrfToken(): bool {
    // Check if the CSRF token is present in the form POST data
    if(!isset($_POST['csrfToken'])) {
        return false;
    }

    // Get salt and original token
    $token = hex2bin($_POST['csrfToken']);
    $salt = substr($token, 32, 32);
    $token = substr($token, 0, 32) ^ $salt;

    // Verify token
    return hash_equals($_SESSION['csrfToken'], $token);
}
?>
```

The `csrfToken` function starts by checking if there's a CSRF token associated with the current session. If there isn't, it will generate a CSRF token as a sequence of random bytes and store it in the current session. Next it will generate a salt as a sequence of random bytes and xor the token by the salt to mask it. Then it will add the salt to the end of the masked CSRF token. Lastly it will convert the token to hexadecimal. The reason why we mask the token with a random salt is to prevent BREACH attacks without needing to generate a new CSRF token for each form. The `verifyCsrfToken` function will start by checking if the CSRF token is present in the form POST data. If it isn't it will return false. Otherwise, it will convert the token to binary. Then it will extract the salt and unmasked token from the CSRF token. Next it will do a constant time comparison of the CSRF token stored in the current session and the unmasked token submitted along with the form data. To add CSRF protection to our forms, we first need to modify `html/router.php` like this:
```php
<?php
// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path   = explode('?', $_SERVER['REQUEST_URI'])[0];

// Is the requested resource a static file?
if(preg_match('#^/static/#', $path)) {
    return false;
}

// Start a new session
session_start();

// Apply CSRF protection to POST requests
require_once(__DIR__ . '/inc/csrf.php');

if($method === 'POST' && !verifyCsrfToken()) {
    require_once(__DIR__ . '/views/400.php');
    exit();
}

// Is the requested resource at a static path?
$staticPaths = array(
    '/'                   => __DIR__ . '/views/home.php',
    '/accounts/sign-up/'  => __DIR__ . '/views/sign-up.php',
    '/accounts/sign-in/'  => __DIR__ . '/views/sign-in.php',
    '/accounts/sign-out/' => __DIR__ . '/views/sign-out.php',
    '/accounts/delete/'   => __DIR__ . '/views/delete-account.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// 404 Not Found
require_once(__DIR__ . '/views/404.php');
?>
```

If you try submitting any form at this point, you will get a 400 page. This means that our CSRF protection is working as expected. In order to form submission to work, we will need to add the CSRF token to a hidden field in each form. Open `html/views/sign-up.html` and modify it like this:
```php
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
```

The only change we need to make to each form is to add the following line to the top of each form:
```php
<input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
```

The other files we need to make this change to are `html/views/sign-in.php`, `html/views/sign-out.php`, and `html/views/delete-account.php`. After adding the CSRF token field to each form, they should all work as they did before. Now there's one last thing we need to fix. As it is right now, we can view the sign-out and delete account pages even if we aren't signed in. This is a problem. To fix it will first create a simple function that determines if the user is signed in. Open `html/inc/user.php` and modify it like this:
```php
<?php
// Connect to the database
require_once(__DIR__ . '/db.php');


// User Functions
// ==============
function createUser(string $username, string $password): bool {
    global $db;

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Create a user account
    try {
        $stmt = $db->prepare('INSERT INTO users(`username`, `password`) VALUES (?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($username, $passwordHash));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function signIn(string $username, string $password): bool {
    global $db;

    // Get the ID and password hash of the given user
    $stmt = $db->prepare('SELECT `id`, `password` FROM users WHERE `username` = ?;');
    $stmt->execute(array($username));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(!count($rows)) {
        return false;
    }

    $user = $rows[0];

    // Verify the password
    if(!password_verify($password, $user['password'])) {
        return false;
    }

    // Store the user's ID in their session
    $_SESSION["userId"] = $user['id'];
    return true;
}


function signOut(): void {
    // Clear all session variables
    session_unset();
}


function deleteUser(int $userId): void {
    global $db;

    // Delete the user account
    $stmt = $db->prepare('DELETE FROM users WHERE `id` = ?;');
    $db->beginTransaction();
    $stmt->execute(array($userId));
    $db->commit();

    // Sign out
    signOut();
}


function isSignedIn(): bool {
    return isset($_SESSION['userId']);
}
?>
```

The `isSignedIn` function will return true if the current session has a user ID stored in it. Otherwise it will return false. Next, modify `html/views/sign-out.php` like this:
```php
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
    <div class="col-6 m-2 card bg-light">
        <div class="card-body">
            <h1>Sign Out</h1>
            <hr/>
            <form method="POST">
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                <div class="row">
                    <p class="col">Are you sure you want to sign out?</p>
                </div>
                <div class="row justify-content-end m-2 mt-4">
                    <button class="col-2 btn btn-primary">Sign Out</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

Now the sign-out page will redirect to the sign-in page if the user isn't signed in already. Also notice that we added a `redirect_to` query parameter. This will allow the sign-in page to determine which page to redirect to when the user successfully signs in. Now let's do the same for the delete account page:
```php
<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the user signed in?
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header('Location: /accounts/sign-in/?redirect_to=/accounts/delete/');
    exit();
}

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
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
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
```

Next we need to modify `html/views/sign-in.php` like this:
```php
<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the request a POST request?
if($method === 'POST') {
    // Validate form data
    if(!isset($_POST['username']) || !isset($_POST['password'])) {
        // Send 400 page
        require_once(__DIR__ . '/400.php');
        exit();
    }

    $username = $_POST['username'];
    $password = $_POST['password'];
    $errors = array();

    if(strlen($username) > 64) {
        $errors[] = 'Username length must be less than 64 characters.';
    }

    if(strlen($password) < 8) {
        $errors[] = 'Password length must be at least 8 characters.';
    }

    // Is the form valid?
    if(!count($errors)) {
        // Sign the user in
        if(signIn($username, $password)) {
            // Redirect
            $redirectUrl = '/';

            if(isset($_GET['redirect_to'])) {
                $redirectUrl = $_GET['redirect_to'];
            }

            header('Location: ' . $redirectUrl);
            exit();
        }

        // Handle sign-in errors
        $errors[] = 'Invalid username or password.';
    }
}

// Send page header
$viewName = 'Sign In';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-6 m-2 card bg-light">
        <div class="card-body">
            <h1>Sign In</h1>
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
                        type="text"/></div>
                </div>
                <div class="row m-1">
                    <label class="col-3" for="password">Password:</label>
                    <div class="col-9"><input class="form-control" id="password" name="password"
                        type="password"/></div>
                </div>
                <div class="row m-1 mt-4 justify-content-end">
                    <button class="col-2 btn btn-primary">Sign In</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

Now if the sign-in page receives a `redirect_to` query parameter it will redirect to the given URL.
