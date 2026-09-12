# Lesson 03: Authentication

Since we will be designing our todo list website to be a multi-tenant web application, we will need to create our own authentication system. For simplicity, we will only be storing a username and password for each user. Additionally, we won't be implementing email verification or password recovery. The first thing we will need to do is start the MariaDB client:

Windows:
01. open the Start Menu
02. search for "MariaDB"
03. start the MariaDB command prompt (the version number will vary)
04. execute `mariadb -u root -p`
05. enter your root password

Linux:
01. open a terminal
02. execute `sudo mariadb`

Mac:
01. open a terminal
02. execute `mariadb -u root`
03. if the previous command fails, execute `mariadb -u root -p`

Now we will need to create a database user, create a database, and grant the required database permissions for our todo list website. Execute the following commands in the MariaDB client. Be sure to use a password you come up with instead of "YOUR-PASSWORD-HERE":
```sql
CREATE USER 'php_simple_todo'@'%' IDENTIFIED BY 'YOUR-PASSWORD-HERE';
CREATE DATABASE php_simple_todo;
GRANT SELECT, INSERT, UPDATE, DELETE ON php_simple_todo.* TO 'php_simple_todo'@'%';
```

After each command you should see output like:
```
Query OK, 0 rows affected (0.527 sec)
```

Now we need to select our database by executing the following command:
```sql
USE php_simple_todo;
```

The output should be:
```
Database changed
```

Now that our database has been created, we can create the table for our todo list users. Execute the following command in the MariaDB client:
```sql
CREATE TABLE users(
    `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `username` VARCHAR(64) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL
);
```

The output should be like:
```
Query OK, 0 rows affected (1.228 sec)
```

Next, create `html/inc/db.php` with the following content:
```php
<?php
// Connect to the database
$db = new PDO(
    'mysql:host=127.0.0.1;dbname=php_simple_todo', 
    'php_simple_todo', 
    'YOUR-PASSWORD-HERE'
);
?>
```

This small script will establish a connection with our database. If you are using source control, be sure to not push this file to the remote repo since it contains sensitive data. Next, we need to create `html/inc/user.php` with the following content:
```php
<?php
// Connect to the database
require_once(__DIR__ . '/db.php');


// User Functions
// ==============
function createUser(string $username, string $password): bool {
    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Create a user account
    try {
        $stmt = $db->prepare('INSERT INTO users(`username`, `password`) VALUES (?, ?);');
        $stmt->execute(array($username, $passwordHash));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}
?>
```

The `createUser` function will be used to create a new user account. The first parameter is the username and the second parameter is the password. The return value is `true` on success or `false` on failure. Notice that we hash the password before storing it in the database as well. The next thing we need to create is a 400 view. This will be shown if an invalid form is submitted to the server. Create `html/views/400.php` with the following content:
```php
<?php
$viewName = 'Bad Request';
http_response_code(400);
require_once(__DIR__ . '/../inc/page-header.php');
?>
<h1>400 Bad Request</h1>
<p>The request sent to the server was invalid.</p>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

Now let's create a sign-up page for users to create an account. Create `html/views/sign-up.php` with the following content:
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

The first thing we do is load our user functions from `html/inc/user.php`. Afterwards, we check if the request method was POST. If the request method is POST, then we check if all the required form fields are present in the POST data. If they aren't, then we insert the contents of our 400 page and exit. Otherwise we collect our form fields into individual variables and check if the username is longer than 64 characters, if the password is shorter than 8 character, and if the 2 password fields don't match. We use an array to accumulate error messages regarding data validation. If the number of errors is 0, we use our `createUser` function to create a new user account. If `createUser` returns true, we set the location header to "/" and exit to redirect to the homepage. Otherwise, we add an error for a duplicate username. For GET requests and POST requests that have errors we will set the view name and insert the page header. If there are any errors to display we will display them above the sign-up form. The sign-up form contains username, password, and confirm password fields as well as a sign-up button. The form will submit a POST request to the same URL which serves the form. And lastly we will insert our page footer. We also need to modify `html/router.php` like this:
```php
<?php
// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path   = explode('?', $_SERVER['REQUEST_URI'])[0];

// Is the requested resource a static file?
if(preg_match('#^/static/#', $path)) {
    return false;
}

// Is the requested resource at a static path?
$staticPaths = array(
    '/'                  => __DIR__ . '/views/home.php',
    '/accounts/sign-up/' => __DIR__ . '/views/sign-up.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// 404 Not Found
require_once(__DIR__ . '/views/404.php');
?>
```

If you visit https://127.0.0.1:8000/accounts/sign-up/ at this point, you should see this:
![sign-up page](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/06-sign_up_page.png?raw=true)

If you enter a valid username, password, and confirm password you should be redirected to the hompage. If any of the fields are invalid you will see an error message instead:
![invalid username](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/07-invalid_username.png?raw=true)
![invalid password](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/08-invalid_password.png?raw=true)

Next we need to create a sign-in page. Open `html/inc/user.php` and modify it like this:
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
?>
```

Our new `signIn` function is used to sign-in a user. The first parameter is the username and the second parameter is the password. The return value is true if the username and password are valid. Otherwise the return value is false. The first thing the `signIn` function does is fetch the ID and password hash for the given username. If no user with the given username exists, the function will return false. Next the function will verify the password against the password hash of the user. If password verification fails, the function will return false. If the username and password are valid, the function will store the user's ID in their session and return true. Now we can create our sign-in page. Open `html/views/sign-in.php` and modify it like this:
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
            // Redirect to homepage
            header('Location: /');
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

Our sign-in page will first load our user functions. If the request is an HTTP POST request it will check if the required form fields are present in the POST data. If they aren't, it will insert our 404 page and exit. Otherwise, it will collect our form fields and validate them. If there are no validation errors, it will call the `signIn` function to sign in the user. On success it will redirect to the homepage and exit. Otherwise it will redisplay the form with error messages. We also need to modify `html/router.php` like this:
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

// Is the requested resource at a static path?
$staticPaths = array(
    '/'                  => __DIR__ . '/views/home.php',
    '/accounts/sign-up/' => __DIR__ . '/views/sign-up.php',
    '/accounts/sign-in/' => __DIR__ . '/views/sign-in.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// 404 Not Found
require_once(__DIR__ . '/views/404.php');
?>
```

The `session_start` function is called to start a new session. We will need to use a session to store data associated with the current user's session when they sign in. If you visit http://127.0.0.1:8000/accounts/sign-in/ at this point, you should see this:
![sign-in page](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/09-sign_in_page.png?raw=true)

If any of the data is invalid when you submit the form, you will see any error messages as alerts above the form.

Now we need to create a sign-out page. The first thing we will need to do is modify `html/inc/user.php` like this:
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
?>
```

The `signOut` function will sign out the user by clearing all session variables. Next we need to create `html/views/sign-out.php` with the following content:
```php
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
$viewName = 'Sign Out';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-6 m-2 card bg-light">
        <div class="card-body">
            <h1>Sign Out</h1>
            <hr/>
            <form method="POST">
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

All we need to do to sign out is call the `signOut` function, redirect to the homepage, and exit if the request is a POST request. For a GET request we display the sign-out form. We also need to modify `html/router.php` like this:
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

// Is the requested resource at a static path?
$staticPaths = array(
    '/'                   => __DIR__ . '/views/home.php',
    '/accounts/sign-up/'  => __DIR__ . '/views/sign-up.php',
    '/accounts/sign-in/'  => __DIR__ . '/views/sign-in.php',
    '/accounts/sign-out/' => __DIR__ . '/views/sign-out.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// 404 Not Found
require_once(__DIR__ . '/views/404.php');
?>
```

If you visit http://127.0.0.1:8000/accounts/sign-out/ at this point, you should see the following:
![sign-out page](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/10-sign_out_page.png?raw=true)

Lastly, we need to create a page for deleting a user account. Open `html/inc/user.php` and modify it like this:
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
?>
```

The `deleteUser` function deletes a user account from the database based on the given user ID and signs out the user. Next we need to create `html/views/delete-account.php` with the following content:
```php
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
```

If the request is an HTTP POST request, we will delete the current user and redirect to the homepage. Otherwise we will insert the page header, show an account deletion confirmation form, and insert the page footer. We also need to modify `html/router.php` like this:
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

If you visit http://127.0.0.1:8000/accounts/delete/ at this point, you should see this page:
![delete account page](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/11-delete_account_page.png?raw=true)

You should now have a minimal working user account system. However, we need to fix a few security flaws in the next lesson.
