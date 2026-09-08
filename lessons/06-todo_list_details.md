# Lesson 06: Todo List Details

Now that we are able to create, view, and delete todo lists we need to add the ability to view the details of each todo list. The details page for each todo list should display the name of the todo list, a button we can use to edit the name of the todo list, a form for adding a task to the todo list, and the tasks on the todo list. Let's start by creating a new database table to hold the tasks for each todo list. Connect to your database and execute the following SQL statements:
```sql
CREATE TABLE tasks(
    `id` INTEGER PRIMARY KEY AUTO_INCREMENT,
    `todo_list` INTEGER REFERENCES todo_listS(`id`) ON DELETE CASCADE,
    `name` VARCHAR(64),
    `due_date` DATETIME
);
```

Next we need to modify `html/inc/todo-list.php` like this:
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
    $prevUrl = $hasPrevPage ? "/?start=$prevOffset" : null;
    $nextUrl = $hasNextPage ? "/?start=$nextOffset" : null;

    // Return todo list page
    return array(
        'prevUrl'   => $prevUrl,
        'nextUrl'   => $nextUrl,
        'todoLists' => $todoLists
    );
}


function getTodoList(int $id): array {
    global $db;

    // Get the given todo list
    $stmt = $db->prepare('SELECT `name` FROM todo_lists WHERE `id` = ? AND `user` = ?;');
    $stmt->execute(array($id, $_SESSION['userId']));
    return $stmt->fetchAll()[0];
}


function updateTodoList(int $id, string $name): bool {
    global $db;

    // Update the todo list
    try {
        $stmt = $db->prepare('UPDATE todo_lists SET `name` = ? WHERE `id` = ? AND `user` = ?;');
        $db->beginTransaction();
        $stmt->execute(array($name, $id, $_SESSION['userId']));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function deleteTodoList(int $id): void {
    global $db;

    // Delete the todo list
    $stmt = $db->prepare('DELETE FROM todo_lists WHERE `id` = ? AND `user` = ?;');
    $db->beginTransaction();
    $stmt->execute(array($id, $_SESSION['userId']));
    $db->commit();
}
?>
```

Now we need to create `html/views/todo-list-info.php` with the following content:
```php
<?php
// Load todo list functions
require_once(__DIR__ . '/../inc/todo-list.php');

// Get the todo list info
$todoList = getTodoList($objectId);
?>
<div class="row justify-content-center" id="todoListInfo">
    <h1 class="col-5 m-1"><?php echo $todoList['name']; ?></h1>
    <button class="col-1 m-1 btn btn-warning" 
        hx-get="/todo-lists/<?php echo $objectId; ?>/update/" 
        hx-swap="outerHTML" hx-target="#todoListInfo">Edit</button>
</div>
```

Next we need to create `html/views/todo-list-update.php` with the following content:
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
        // Update the todo list
        if(updateTodoList($objectId, $name)) {
            // Redirect to the details page
            header("Location: /todo-lists/$objectId/");
            exit();
        }

        // Handle todo list creation errors
        $errors[] = 'Todo list names must be unique per user.';
    }
}

// Get todo list info
$todoList = getTodoList($objectId);
?>
<div class="row justify-content-center">
    <form class="col-6" id="todoListUpdateForm" 
        hx-post="/todo-lists/<?php echo $objectId; ?>/update/" 
        hx-swap="outerHTML" hx-indicator="#todoListUpdateBtn">
        <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
        <?php
        // Are there any errors?
        if(isset($errors)) {
            // Display all errors
            foreach($errors as $error) {
                ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php
            }
        }
        ?>
        <div class="row m-1">
            <label class="col-2" for="name">Name:</label>
            <div class="col-10">
                <input class="form-control" id="name" type="text" name="name" 
                    value="<?php echo $todoList['name']; ?>" 
                    maxlength="64" required/></div>
        </div>
        <div class="row m-1 mt-4 justify-content-end">
            <button class="col-2 m-1 btn btn-success">
                <span class="spinner-border spinner-border-sm htmx-indicator"
                    id="todoListUpdateBtn">
                    <span class="visually-hidden">Loading...</span>
                </span>
                Save
            </button>
            <button class="col-2 m-1 btn btn-danger" type="button" 
                hx-get="/todo-lists/<?php echo $objectId; ?>/"
                hx-swap="outerHTML" hx-target="#todoListUpdateForm">
                <span class="spinner-border spinner-border-sm htmx-indicator">
                    <span class="visually-hidden">Loading...</span>
                </span>
                Cancel
            </button>
        </div>
    </form>
</div>
```

Now we need to create `html/views/todo-list-details.php` with the following content:
```php
<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Check if the user is logged in
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header("Location: /accounts/sign-in/?redirect_to=$path");
    exit();
}

// Send page header
$viewName = 'Todo List Details';
require_once(__DIR__ . '/../inc/page-header.php');

// Send todo list details
require_once(__DIR__ . '/todo-list-info.php');
?>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

We also need to modify `html/router.php` like this:
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
        ''       => __DIR__ . '/views/todo-list-details.php',
        'update' => __DIR__ . '/views/todo-list-update.php',
        'delete' => __DIR__ . '/views/todo-list-delete.php'
    )
);
$matches = array();

if(preg_match('#^/([^/]+)/([0-9]+)(?:/([^/]+))?/#', $path, $matches)) {
    // Extract the object type, id, and action before passing them to the matching view
    $objectType = $matches[1];
    $objectId = $matches[2];
    $objectAction = isset($matches[3]) ? $matches[3] : '';

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

If you click one of the todo lists on the homepage, you will now see a page like this:
![todo list details](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/14-todo_list_details.png?raw=true)

And if you click the Edit button you will see a form to update the name of the todo list:
![todo list update form](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/15-todo_list_update_form.png?raw=true)

Clicking the Save or Cancel button should save or discard the changes respectively. Next we need to create `html/inc/task.php` with the following content:
```php
<?php
// Connect to the database
require_once(__DIR__ . '/db.php');

// Task Functions
// ==============
function createTask(int $todoList, string $name, string $dueDate): bool {
    global $db;

    // Create the task
    try {
        $stmt = $db->prepare('INSERT INTO tasks(`todo_list`, `name`, `due_date`) VALUES (?, ?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($todoList, $name, $dueDate));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}
?>
```

Now we need to create `html/views/task-create.php` with the following content:
```php
<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Is the request a POST request?
if($method === 'POST') {
    // Validate form data
    if(!isset($_POST['todoList']) ||
        !isset($_POST['name']) ||
        !isset($_POST['dueDate'])) {
        // Send 400 page
        require_once(__DIR__ . '/400.php');
        exit();
    }

    $todoList = $_POST['todoList'];
    $name = $_POST['name'];
    $dueDate = $_POST['dueDate'];
    $errors = array();
    
    if(strlen($name) > 64) {
        $errors[] = 'Task names must be no more than 64 characters long.';
    }

    // Is the form valid?
    if(!count($errors)) {
        // Create the task
        if(createTask($todoList, $name, $dueDate)) {
            // Redirect to the details page for the todo list
            header("HX-Redirect: /todo-lists/$todoList/");
            exit();
        }

        // Handle task creation errors
        $errors[] = 'Failed to create task.';
    }
}
?>
<form hx-post="/tasks/create/" hx-swap="outerHTML"
    hx-indicator="#taskCreateBtn">
    <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
    <input type="hidden" name="todoList" 
        value="<?php echo isset($objectId) ? $objectId : $todoList ?>"/>
    <?php
    // Are there any errors?
    if(isset($errors)) {
        // Show all errors
        foreach($errors as $error) {
            ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php
        }
    }
    ?>
    <div class="row m-1">
        <label class="col-2" for="taskName">Name:</label>
        <div class="col-10"><input class="form-control" id="taskName" 
            type="text" name="name" maxlength="64" required/></div>
    </div>
    <div class="row m-1">
        <label class="col-2" for="taskDueDate">Due Date:</label>
        <div class="col-10"><input class="form-control" id="taskDueDate"
            type="datetime-local" name="dueDate" required/></div>
    </div>
    <div class="row m-1 mt-4 justify-content-end">
        <button class="col-2 btn btn-primary">
            <span class="spinner-border spinner-border-sm htmx-indicator"
                id="taskCreateBtn">
                <span class="visually-hidden">Loading...</span>
            </span>
            Create
        </button>
    </div>
</form>
```

We also need to modify `html/views/todo-list-details.php` like this:
```php
<?php
// Load user functions
require_once(__DIR__ . '/../inc/user.php');

// Check if the user is logged in
if(!isSignedIn()) {
    // Redirect to the sign-in page
    header("Location: /accounts/sign-in/?redirect_to=$path");
    exit();
}

// Send page header
$viewName = 'Todo List Details';
require_once(__DIR__ . '/../inc/page-header.php');

// Send todo list details
require_once(__DIR__ . '/todo-list-info.php');
?>
<div class="row justify-content-center">
    <div class="col-6 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">New Task</h1>
            <hr/>
            <?php require_once(__DIR__ . '/task-create.php') ?>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

And we need to modify `html/router.php` like this as well:
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
    '/todo-lists/create/' => __DIR__ . '/views/todo-list-create.php',
    '/tasks/create/'      => __DIR__ . '/views/task-create.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// Is the requested resource at a dynamic path?
$dynamicPaths = array(
    'todo-lists' => array(
        ''       => __DIR__ . '/views/todo-list-details.php',
        'update' => __DIR__ . '/views/todo-list-update.php',
        'delete' => __DIR__ . '/views/todo-list-delete.php'
    )
);
$matches = array();

if(preg_match('#^/([^/]+)/([0-9]+)(?:/([^/]+))?/#', $path, $matches)) {
    // Extract the object type, id, and action before passing them to the matching view
    $objectType = $matches[1];
    $objectId = $matches[2];
    $objectAction = isset($matches[3]) ? $matches[3] : '';

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

If youe view the details page for a todo list now, you should see a form for creating tasks on the todo list:
![task create form](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/16-task_create_form.png?raw=true)

Next we need to modify `html/inc/task.php` like this:
```php
<?php
// Connect to the database
require_once(__DIR__ . '/db.php');

// Task Functions
// ==============
function createTask(int $todoList, string $name, string $dueDate): bool {
    global $db;

    // Create the task
    try {
        $stmt = $db->prepare('INSERT INTO tasks(`todo_list`, `name`, `due_date`) VALUES (?, ?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($todoList, $name, $dueDate));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function getTasks(int $todoList): array {
    global $db;

    // Calculate page offset
    $offset = isset($_GET['start']) ? (int)$_GET['start'] : 0;

    // Get the next page of tasks on the given todo list
    $stmt = $db->prepare('SELECT `id`, `name`, `due_date` FROM tasks WHERE `todo_list` IN (SELECT `id` FROM todo_lists WHERE `id` = ? AND `user` = ?) ORDER BY `name` LIMIT 11 OFFSET ?;');
    $stmt->bindValue(1, $todoList, PDO::PARAM_INT);
    $stmt->bindValue(2, $_SESSION['userId'], PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tasks = $stmt->fetchAll();

    // Are there previous/next pages of tasks?
    $hasPrevPage = $offset >= 10;
    $hasNextPage = false;

    if(count($tasks) == 11) {
        // Remove the 11th task and set the next page flag
        unset($tasks[10]);
        $hasNext = true;
    }

    // Generate previous/next page URLs
    $prevOffset = $offset - 10;
    $nextOffset = $offset + 10;
    $prevUrl = $hasPrevPage ? "/todo-lists/$todoList/?start=$prevOffset" : null;
    $nextUrl = $hasNextPage ? "/todo-lists/$todoList/?start=$nextOffset" : null;

    // Return task data
    return array(
        'prevUrl' => $prevUrl,
        'nextUrl' => $nextUrl,
        'tasks'   => $tasks
    );
}


function getTask(int $id): array {
    global $db;

    // Get the task with the given ID
    $stmt = $db->prepare('SELECT `id`, `todo_list`, `name`, `due_date` FROM tasks WHERE `id` = ? AND `todo_list` IN (SELECT `id` FROM todo_lists WHERE `user` = ?);');
    $stmt->execute(array($id, $_SESSION['userId']));
    return $stmt->fetchAll()[0];
}


function updateTask(int $id, string $name, string $dueDate): bool {
    global $db;

    // Update the given task
    try {
        $stmt = $db->prepare('UPDATE tasks SET `name` = ?, `due_date` = ? WHERE `id` = ? AND `todo_list` IN (SELECT `id` FROM todo_lists WHERE `user` = ?);');
        $db->beginTransaction();
        $stmt->execute(array($name, $dueDate, $id, $_SESSION['userId']));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function deleteTask(int $id): void {
    global $db;

    // Delete the given task
    $stmt = $db->prepare('DELETE FROM tasks WHERE `id` = ? AND `todo_list` IN (SELECT `id` FROM todo_lists WHERE `user` = ?);');
    $db->beginTransaction();
    $stmt->execute(array($id, $_SESSION['userId']));
    $db->commit();
}
?>
```

Now we need to create a standalone script which will display the info for each task. The reason why we are doing this is so that we can also have a form to update each task when we click a task's Edit button. Create `html/views/task-info.php` with the following content:
```php
<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Is the task data already loaded?
if(!isset($task)) {
    // Load the task data
    $task = getTask($objectId);
}
?>
<div class="row m-1" id="task<?php echo $task['id']; ?>">
    <div class="col card bg-white">
        <div class="card-body row">
            <div class="col-7 nav-link">
                <div><?php echo $task['name']; ?></div>
                <div class="text-secondary"><?php echo (new Datetime($task['due_date']))->format("m/d/Y h:m A"); ?></div>
            </div>
            <div class="col-2 m-1 row">
                <button class="col btn btn-warning" 
                    hx-get="/tasks/<?php echo $task['id']; ?>/update/"
                    hx-swap="outerHTML" hx-target="#task<?php echo $task['id']; ?>">
                    Edit
                </button>
            </div>
            <form class="col m-1 row" 
                hx-post="/tasks/<?php echo $task['id']; ?>/delete/"
                hx-indicator="taskDeleteBtn<?php echo $task['id']; ?>">
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                <button class="col btn btn-danger">
                    <span class="spinner-border spinner-border-sm htmx-indicator"
                        id="taskDeleteBtn<?php echo $task['id']; ?>">
                        <span class="visually-hidden">Loading...</span>
                    </span>
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>
```

Next we need to create `html/views/task-update.php` with the following content:
```php
<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Is the request a POST request
if($method === 'POST') {
    // Validate form data
    if(!isset($_POST['name']) ||
        !isset($_POST['dueDate'])) {
        // Send 400 page
        require_once(__DIR__ . '/400.php');
        exit();
    }

    $name    = $_POST['name'];
    $dueDate = $_POST['dueDate'];
    $errors = array();

    if(strlen($name) > 64) {
        $errors[] = 'Task names must be no more than 64 characters long.';
    }

    // Is the form valid?
    if(!count($errors)) {
        // Update the task
        if(updateTask($objectId, $name, $dueDate)) {
            // Redirect to task info page
            header("Location: /tasks/$objectId/");
            exit();
        }

        // Handle errors
        $errors[] = 'Failed to update task.';
    }
}

// Get task info
$task = getTask($objectId);
?>
<div class="row m-1" id="taskUpdateForm<?php echo $objectId; ?>">
    <div class="col card bg-light">
        <div class="card-body">
            <form hx-post="/tasks/<?php echo $objectId; ?>/update/"
                hx-swap="outerHTML" hx-target="#taskUpdateForm<?php echo $objectId; ?>"
                hx-indicator="#taskSaveBtn">
                <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                <div class="row m-1">
                    <label class="col-2" for="name">Name:</label>
                    <div class="col-10"><input class="form-control" id="name" type="text" 
                        name="name" value="<?php echo $task['name']; ?>" 
                        maxlength="64" required/></div>
                </div>
                <div class="row m-1">
                    <label class="col-2" for="dueDate">Due Date:</label>
                    <div class="col-10"><input class="form-control" id="dueDate" 
                        type="datetime-local" name="dueDate" 
                        value="<?php echo $task['due_date']; ?>" required/></div>
                </div>
                <div class="row m-1 mt-4 justify-content-end">
                    <button class="col-2 m-1 btn btn-success">
                        <span class="spinner-border spinner-border-sm htmx-indicator"
                            id="taskSaveBtn">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                        Save
                    </button>
                    <button class="col-2 m-1 btn btn-danger" 
                        hx-get="/tasks/<?php echo $objectId; ?>/"
                        hx-swap="outerHTML" 
                        hx-target="#taskUpdateForm<?php echo $objectId; ?>">
                        <span class="spinner-border spinner-border-sm htmx-indicator">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

We also need to create `html/views/task-delete.php` with the following content:
```php
<?php
// Load task functions
require_once(__DIR__ . '/../inc/task.php');

// Get the todo list the task is associated with so we know which page to 
// redirect to
$todoList = getTask($objectId)['todo_list'];

// Delete the given task and redirect to the todo list details page
deleteTask($objectId);
header("HX-Redirect: /todo-lists/$todoList/");
exit();
?>
```

Now that we have created the smaller views we need, let's create `html/views/tasks-view.php` with the following content:
```php
<div id="tasksView">
    <?php
    // Load task functions
    require_once(__DIR__ . '/../inc/task.php');

    // Get all the tasks associated with the todo list
    $page = getTasks($objectId);

    // Are there any tasks to display?
    if(count($page['tasks'])) {
        // Display all tasks
        foreach($page['tasks'] as $task) {
            require(__DIR__ . '/task-info.php');
        }
    } else {
        ?>
        <div>No tasks.</div>
        <?php
    }
    ?>
    <div class="row m-1 mt-4 justify-content-center">
        <?php
        // Is there a previous URL?
        if($page['prevUrl'] === null) {
            ?>
            <button class="col-2 m-1 btn btn-secondary">Previous</button>
            <?php
        } else {
            ?>
            <button class="col-2 m-1 btn btn-primary" 
                hx-get="<?php echo $page['prevUrl']; ?>" 
                hx-swap="outerHTML" hx-target="#tasksView"
                hx-push-url="<?php echo $page['prevUrl']; ?>">Previous</button>
            <?php
        }

        // Is there a next URL?
        if($page['nextUrl'] === null) {
            ?>
            <button class="col-2 m-1 btn btn-secondary">Next</button>
            <?php
        } else {
            ?>
            <button class="col-2 m-1 btn btn-primary" 
                hx-get="<?php echo $page['nextUrl']; ?>" 
                hx-swap="outerHTML" hx-target="#tasksView"
                hx-push-url="<?php echo $page['nextUrl']; ?>">Next</button>
            <?php
        }
        ?>
    </div>
</div>
```

And next we can modify `html/views/todo-list-details.php` like this:
```php
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
    <div class="col-6 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">New Task</h1>
            <hr/>
            <?php require_once(__DIR__ . '/task-create.php') ?>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-6 m-1 card bg-light">
        <div class="card-body">
            <h1 class="card-title">Tasks</h1>
            <hr/>
            <?php require_once(__DIR__ . '/tasks-view.php') ?>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

We also need to modify `html/router.php` like this:
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
    '/todo-lists/create/' => __DIR__ . '/views/todo-list-create.php',
    '/tasks/create/'      => __DIR__ . '/views/task-create.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// Is the requested resource at a dynamic path?
$dynamicPaths = array(
    'todo-lists' => array(
        ''       => __DIR__ . '/views/todo-list-details.php',
        'update' => __DIR__ . '/views/todo-list-update.php',
        'delete' => __DIR__ . '/views/todo-list-delete.php'
    ),
    'tasks'      => array(
        ''       => __DIR__ . '/views/task-info.php',
        'update' => __DIR__ . '/views/task-update.php',
        'delete' => __DIR__ . '/views/task-delete.php'
    )
);
$matches = array();

if(preg_match('#^/([^/]+)/([0-9]+)(?:/([^/]+))?/#', $path, $matches)) {
    // Extract the object type, id, and action before passing them to the matching view
    $objectType = $matches[1];
    $objectId = $matches[2];
    $objectAction = isset($matches[3]) ? $matches[3] : '';

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

If you visit the details page for a todo list now, you should see something like this:
![tasks view](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/17-tasks_view.png?raw=true)

Clicking the Edit button on any task should show a form for editing the task. And clicking either Save or Cancel should hide the form:
![task update form](https://github.com/DylanCheetah/php-simple-todo/blob/main/lessons/screenshots/18-task_update_form.png?raw=true)

Lastly, clicking the Delete button on any task should delete it.
