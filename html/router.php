<?php
// Set content security policy and enable HSTS
header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; upgrade-insecure-requests;");
header('Strict-Transport-Security: max-age=63072000');

// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path   = explode('?', $_SERVER['REQUEST_URI'])[0];

// Is the requested resource a static file?
if(preg_match('#^/php-simple-todo/static/#', $path)) {
    return false;
}

// Set session cookie params
session_set_cookie_params(array(
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $_SERVER['HTTP_HOST'],
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
));
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

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
    '/php-simple-todo/'                   => __DIR__ . '/views/home.php',
    '/php-simple-todo/accounts/sign-up/'  => __DIR__ . '/views/sign-up.php',
    '/php-simple-todo/accounts/sign-in/'  => __DIR__ . '/views/sign-in.php',
    '/php-simple-todo/accounts/sign-out/' => __DIR__ . '/views/sign-out.php',
    '/php-simple-todo/accounts/delete/'   => __DIR__ . '/views/delete-account.php',
    '/php-simple-todo/todo-lists/create/' => __DIR__ . '/views/todo-list-create.php',
    '/php-simple-todo/tasks/create/'      => __DIR__ . '/views/task-create.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// Is the requested resource at a dynamic path?
$dynamicPaths = array(
    'todo-lists' => array(
        ''       => __DIR__ . '/views/todo-list-details.php',
        'info'   => __DIR__ . '/views/todo-list-info.php',
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

if(preg_match('#^/php-simple-todo/([^/]+)/([0-9]+)(?:/([^/]+))?/#', $path, $matches)) {
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
