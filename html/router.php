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
