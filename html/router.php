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
