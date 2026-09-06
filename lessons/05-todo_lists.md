# Lesson 05: Todo Lists

Now that we have improved the security of our website, we can create a page for creating and viewing todo lists. To keep things simple, we will just modify our current homepage for this purpose. First we need to modify `html/views/home.php` like this:
```php
<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the current user signed in?
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header('Location: /accounts/sign-in/?redirect_to=/');
    exit();
}

// Send page header
$viewName = 'Home';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<h1>Hello World!</h1>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

This will ensure that the current user has to be signed in to view the homepage. The next thing we need to do is add a new table to the database for our todo lists. Start the MariaDB client and execute the following SQL:
```sql
USE php_simple_todo;
CREATE TABLE todo_lists(
    `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `user` INTEGER CONSTRAINT `todo_lists_user` REFERENCES users(`id`) ON DELETE CASCADE,
    `name` VARCHAR(64),
    CONSTRAINT UNIQUE INDEX `todo_lists_user_name` (`user`, `name`)
);
```

Now we need to create `html/inc/todo-list.php` with the following content:
```php
<?php
// Connect to the database
require_once(__DIR__ . '/db.php');


// Todo List Functions
// ===================
function createTodoList(string $name): bool {
    global $db;

    // Create the todo list
    try {
        $stmt = $db->prepare('INSERT INTO todo_lists(`user`, `name`) VALUES (?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($_SESSION['userId'], $name));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}
?>
```

Since our todo lists page will be used for both creating and viewing our todo lists we will need 2 sections. One for creating todo lists via a form and one for viewing our todo lists via a paginated view. One way we could do this is to just place both directly on the page and reload the entire page every time we create a todo list, click the Next button, or click the previous button. However, this leads to wasted bandwidth since we really don't need to reload the entire page in order to accomplish this. Another way we could do this is to write custom JavaScript code to send HTTP requests to a backend API. But this method requires us to write and maintain more code. Instead we will use an existing library called HTMX. HTMX allows us to use special HTML attributes to trigger AJAX requests without having to write JavaScript code. The response from the backend is HTML code which gets inserted into the existing page to either replace existing content or add more content. To use HTMX, we first need to download https://cdn.jsdelivr.net/npm/htmx.org@2.0.10/dist/htmx.min.js into `html/static/js/`. Next we need to modify `html/inc/page-header.php` like this:
```php
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Simple Todo - <?php echo $viewName; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <link rel="stylesheet" href="/static/css/bootstrap.min.css"/>
        <script src="/static/js/bootstrap.bundle.min.js"></script>
        <script src="/static/js/htmx.min.js"></script>
    </head>
    <body>
        <div class="container-fluid">
```

This will allow use to use HTMX in our project. Now let's create a form for our todo lists as a standalone PHP file. Create `html/views/todo-list-create.php` with the following content:
```php
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
```

Our standalone form functions mostly the same as a standard HTML form. However, there are a few key differences. First, we need to do our redirect with the `HX-Redirect` header instead of the `Location` header. This is necessary because our form will be processed via an HTMX request. We also omit the page header and footer. This is necessary because our standalone form functions as a page fragment rather than a full page. Our form element has no `method` attribute. Instead it has `hx-post`, `hx-swap`, and `hx-indicator` attributes. `hx-post` determines the URL to submit the form to, `hx-swap` determines how to handle the response, and `hx-indicator` is used to specify the ID of the element which will indicate request progress. By setting `hx-swap` to "outerHTML" we can replace the entire form with the response content. Our submit button contains a spinner which will act as a progress indicator. The `htmx-indicator` CSS class will cause the spinner to be hidden by default and shown while the request is in progress. Next we need to modify `html/views/home.php` like this:
```php
<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the current user signed in?
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header('Location: /accounts/sign-in/?redirect_to=/');
    exit();
}

// Send page header
$viewName = 'Home';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<div class="row justify-content-center">
    <div class="col-6 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">New Todo List</h1>
            <hr/>
            <?php require_once(__DIR__ . '/todo-list-create.php'); ?>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

If we visit http://127.0.0.1:8000/ at this point, we will see this:
![todo list create form](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/12-todo_list_create_form.png?raw=true)

Now we need to create a paginated view for our todo lists. Modify `html/inc/todo-list.php` like this:
```php
<?php
// Connect to the database
require_once(__DIR__ . '/db.php');


// Todo List Functions
// ===================
function createTodoList(string $name): bool {
    global $db;

    // Create the todo list
    try {
        $stmt = $db->prepare('INSERT INTO todo_lists(`user`, `name`) VALUES (?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($_SESSION['userId'], $name));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function getTodoLists(): array {
    global $db;

    // Calculate offset
    $offset = isset($_GET['start']) ? (int)$_GET['start'] : 0;

    // Get the next 10 todo lists plus 1
    $stmt = $db->prepare('SELECT `id`, `name` FROM todo_lists WHERE user = ? ORDER BY `name` LIMIT 11 OFFSET ?;');
    $stmt->bindValue(1, $_SESSION['userId'], PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $todoLists = $stmt->fetchAll();

    // Determine if there's a previous and next page
    $hasPrevPage = $offset >= 10;
    $hasNextPage = false;

    if(count($todoLists) === 11) {
        // Remove the 11th todo list and set next page flag
        unset($todoLists[10]);
        $hasNextPage = true;
    }

    // Generate URL for previous and next page
    $prevOffset = $offset - 10;
    $nextOffset = $offset + 10;
    $prevUrl = $hasPrevPage ? "/?start={$prevOffset}" : null;
    $nextUrl = $hasNextPage ? "/?start={$nextOffset}" : null;

    // Return todo list page
    return array(
        'prevUrl'   => $prevUrl,
        'nextUrl'   => $nextUrl,
        'todoLists' => $todoLists
    );
}
?>
```

Notice that we limit the number of todo lists returned to 11 and use a query parameter called "start" to determine the first todo list to fetch. For our todo list app we will be displaying 10 todo lists at a time though. The reason why we fetch 11 instead of 10 is so we can easily determine if there are more todo lists available to fetch. Also, it is very important to remember to cast our start value to an integer and bind our values with the `bindValue` method of our SQL statement instead of passing them as an array to the `execute` method. If you forget you will get an error because the offset clause requires an integer and the `execute` method passes all values as strings. Next we need to create `html/views/todo-lists-view.php` with the following content:
```php
<div id="todoListsView">
    <?php
    // Load todo list functions
    require_once(__DIR__ . '/../inc/todo-list.php');

    // Get a page of results
    $page = getTodoLists();

    // Are there any todo lists to show?
    if(!empty($page['todoLists'])) {
        // Show all todo lists on the page
        foreach($page['todoLists'] as $todoList) {
            ?>
            <div class="row m-1">
                <div class="col card bg-white">
                    <div class="card-body row">
                        <a class="col-9 nav-link" href="/todo-lists/<?php echo $todoList['id']; ?>">
                            <?php echo $todoList['name']; ?></a>
                        <form class="col-3" hx-post="/todo-lists/<?php echo $todoList['id']; ?>/delete/"
                            hx-indicator="#todoListDeleteBtn<?php $todoList['id']; ?>">
                            <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                            <button class="btn btn-danger">
                                <span class="spinner-border spinner-border-sm htmx-indicator"
                                    id="todoListDeleteBtn<?php $todoList['id']; ?>">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="row">
            <div class="col">No todo lists found.</div>
        </div>
        <?php
    }
    ?>
    <div class="row m-1 mt-4 justify-content-center">
        <?php
        // Is there a previous URL?
        if($page['prevUrl'] === null) {
            ?>
            <a class="col-2 m-1 btn btn-secondary">Previous</a>
            <?php
        } else {
            ?>
            <a class="col-2 m-1 btn btn-primary" hx-get="<?php echo $page['prevUrl']; ?>" 
                hx-swap="outerHTML" hx-target="#todoListsView" 
                hx-push-url="<?php echo $page['prevUrl']; ?>">Previous</a>
            <?php
        }
        ?>
        <?php
        // Is there a next URL?
        if($page['nextUrl'] === null) {
            ?>
            <a class="col-2 m-1 btn btn-secondary">Next</a>
            <?php
        } else {
            ?>
            <a class="col-2 m-1 btn btn-primary" hx-get="<?php echo $page['nextUrl']; ?>"
                hx-swap="outerHTML" hx-target="#todoListsView"
                hx-push-url="<?php echo $page['nextUrl']; ?>">Next</a>
            <?php
        }
        ?>
    </div>
</div>
```

Each todo list will have its own URL that contains its ID. This will be used later to provide a details page for each todo list. Likewise, each todo list has its own delete URL which will allow us to delete any todo list. There will also be URLs to view the previous/next pages. Clicking the previous or next button will automatically replace the entire todo list view with a new one fetched from the server. Now modify `html/views/home.php` like this:
```php
<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Is the current user signed in?
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header('Location: /accounts/sign-in/?redirect_to=/');
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
    <div class="col-6 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">New Todo List</h1>
            <hr/>
            <?php require_once(__DIR__ . '/todo-list-create.php'); ?>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-6 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">Todo Lists</h1>
            <hr/>
            <?php require_once(__DIR__ . '/todo-lists-view.php') ?>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

By checking if the `HX-Request` HTTP header exists we can determine if the request was an AJAX request triggered by HTMX. This will allow us to have a single endpoint which sends either the full homepage or just the todo lists view. If you visit http://127.0.0.1:8000/ at this point you should see something like this. The actual todo lists will vary based on what you created:
![todo lists view](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/13-todo_lists_view.png?raw=true)

Lastly, we need to make it so we can delete existing todo lists. Open `html/inc/todo-list.php` and modify it like this:
```php
<?php
// Connect to the database
require_once(__DIR__ . '/db.php');


// Todo List Functions
// ===================
function createTodoList(string $name): bool {
    global $db;

    // Create the todo list
    try {
        $stmt = $db->prepare('INSERT INTO todo_lists(`user`, `name`) VALUES (?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($_SESSION['userId'], $name));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function getTodoLists(): array {
    global $db;

    // Calculate offset
    $offset = isset($_GET['start']) ? (int)$_GET['start'] : 0;

    // Get the next 10 todo lists plus 1
    $stmt = $db->prepare('SELECT `id`, `name` FROM todo_lists WHERE user = ? ORDER BY `name` LIMIT 11 OFFSET ?;');
    $stmt->bindValue(1, $_SESSION['userId'], PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $todoLists = $stmt->fetchAll();

    // Determine if there's a previous and next page
    $hasPrevPage = $offset >= 10;
    $hasNextPage = false;

    if(count($todoLists) === 11) {
        // Remove the 11th todo list and set next page flag
        unset($todoLists[10]);
        $hasNextPage = true;
    }

    // Generate URL for previous and next page
    $prevOffset = $offset - 10;
    $nextOffset = $offset + 10;
    $prevUrl = $hasPrevPage ? "/?start={$prevOffset}" : null;
    $nextUrl = $hasNextPage ? "/?start={$nextOffset}" : null;

    // Return todo list page
    return array(
        'prevUrl'   => $prevUrl,
        'nextUrl'   => $nextUrl,
        'todoLists' => $todoLists
    );
}


function deleteTodoList(int $id): void {
    global $db;

    // Delete the todo list
    $stmt = $db->prepare('DELETE FROM todo_lists WHERE user = ? AND id = ?;');
    $db->beginTransaction();
    $stmt->execute(array($_SESSION['userId'], $id));
    $db->commit();
}
?>
```

Next we need to create `html/views/todo-list-delete.php` with the following content:
```php
<?php
// Load todo list functions
require_once(__DIR__ . '/../inc/todo-list.php');

// Delete the given todo list and redirect to the homepage
deleteTodoList($objectId);
header('HX-Redirect: /');
?>
```

Now we need to map our todo list delete view to a URL in our router. However, we cannot map it to a static path like we have been doing for our other views because we have to pass the ID of the todo list to the view via the URL. Instead we will need to map it to a dynamic path. Open `html/router.php` and modify it like this:
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
    '/accounts/delete/'   => __DIR__ . '/views/delete-account.php',
    '/todo-lists/create/' => __DIR__ . '/views/todo-list-create.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// Is the requested resource at a dynamic path?
$dynamicPaths = array(
    'todo-lists' => array(
        'delete' => __DIR__ . '/views/todo-list-delete.php'
    )
);
$matches = array();

if(preg_match('#^/([^/]+)/([0-9]+)(?:/([^/]+))?/#', $path, $matches)) {
    // Extract the object type, id, and action before passing them to the matching view
    $objectType = $matches[1];
    $objectId = $matches[2];
    $objectAction = isset($matches[3]) ? $matches[3] : null;

    // Check if a view exists for the given object type and action pair
    if(isset($dynamicPaths[$objectType][$objectAction])) {
        require_once($dynamicPaths[$objectType][$objectAction]);
        exit();
    }
}

// 404 Not Found
require_once(__DIR__ . '/views/404.php');
?>
```

For each type of object that needs views with dynamic paths we will create one array which maps each possible action to a view. Next we will create an array to hold matches from a regular expression that will match any dynamic path. If the path matches the regular expression for a dynamic path we will extract the object type, ID, and action. Then we will dispatch the request to the view which matches the object type/action pair if it exists. And now we should be able to delete any todo list by clicking its delete button.
