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
    '/' => __DIR__ . '/views/home.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// 404 Not Found
require_once(__DIR__ . '/views/404.php');
?>
