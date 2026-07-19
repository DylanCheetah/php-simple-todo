# Lesson 02: Project Setup

Now that we have installed the software we will need, we can begin setting up our project structure. First, create a folder for your project. I will be naming mine `php-simple-todo`. Next, open your project folder in Visual Studio Code. Then create an `html` folder inside your project folder. Afterwards, create a `html/static/` folder. At this point, your project structure should look like this:
```
php-simple-todo/
    html/
        static/
```

Next we need to create a router script for our project. Create `html/router.php` with the following content:
```php
<?php
// Get the request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path   = explode('?', $_SERVER['REQUEST_URI'])[0];

// Is the requested resource a static file?
if(preg_match('#^/static/#', $path)) {
    return false;
}

// Print debug info
header('Content-Type: text/plain');
echo "Request Method: $method\n";
echo "Request Path: $path\n";
?>
```

First, our router script will store the request method in a variable called `$method`. Then it will use the `explode` method to remove the query string from the request URI and store it into a variable called `$path`. Next it will use a regular expression to determine if the requested resource is a file located in the `html/static/` folder. If the requested resource is a static file we will return `false` to indicate that the resource should be served as a static file. Otherwise, we will continue processing the request in our router script. For now we will set the "Content-Type" header to "text/plain" and print out some debug info. To test our router script we need to click Terminal > New Terminal and execute the following command:
```sh
php -S 127.0.0.1:8000 -t html html/router.php
```

If you visit http://127.0.0.1:8000/ in a web browser, you should see this:
*screenshot*

If you try visiting http://127.0.0.1:8000/static/css/theme.css or any other resource in `/static/` you will see that the page changes to a 404 page:
*screenshot*

Visiting any resource not in `/static/` will yield a page with our debug info. Now that we know this part of our router works, let's create a view for our homepage. To simplify things we will first create a page header and page footer which we can use for all of our views. Create a `html/inc/` folder. Then create `html/inc/page-header.php` with the following content:
```php
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Simple Todo - <?php echo $viewName; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    </head>
    <body>
```

Next, create `html/inc/page-footer.php` with the following content:
```php
    </body>
</html>
```

Now we need to create a `html/views/` folder. Afterwards, create `html/views/home.php` with the following content:
```php
<?php
$viewName = 'Home';
require_once(__DIR__ . '/../inc/page-header.php');
?>
<h1>Hello World!</h1>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

In our home view we start by setting `$viewName` to the name of our view. Then we use the `require_once` function to insert the contents of our page header template. Notice that our page header template uses `$viewName` to build part of the page title. Afterwards we place the main content of our view. And then we use the `require_once` function to insert the contents of our page footer template. We will also need a view for when the requested resource isn't found. Create `html/views/404.php` with the following content:
```php
<?php
$viewName = 'Not Found';
http_response_code(404);
require_once(__DIR__ . '/../inc/page-header.php');
?>
<h1>404 Not Found</h1>
<p>The requested resource was not found on this server.</p>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
```

Notice that our 404 view sets the HTTP response code to 404 before inserting the page header. Any changes to the response headers must be made before sending the response body. Now we need to modify `html/router.php` like this:
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
    '/' => __DIR__ . '/views/home.php'
);

if(isset($staticPaths[$path])) {
    require_once($staticPaths[$path]);
    exit();
}

// 404 Not Found
require_once(__DIR__ . '/views/404.php');
?>
```

To route each request to the correct view, we will create an array which maps each static path to the path of its corresponding view. We can use the `isset` function to check if our static paths array contains the requested path. If it does, we can simply insert the contents of the view and exit. Otherwise, we will insert the contents of our 404 view.

If we visit http://127.0.0.1:8000/ now, we will see this:
*screenshot*

And if we visit a URL which doesn't exist such as http://127.0.0.1:8000/accounts/login/, we will see this:
*screenshot*
