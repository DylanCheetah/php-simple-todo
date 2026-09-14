# Lesson 09: Deployment

Once you have finished your todo list website you can deploy it to a hosting provider or self-host it if you wish. The exact steps to do so will vary based on the host and the software you use to serve the website, but there are a few things you will need to do regardless of where you choose to host it.

First, you need to export the structure of your database as an SQL script by executing the following command in a terminal:
```sh
mariadb-dump -u root -p php_simple_todo > php_simple_todo.sql
```

On Windows you will need to start a MariaDB command prompt like you did in lesson 3 in order to execute the above command. The resulting SQL script can be executed on your production database to recreate the structure of the tables needed by your todo list website.

In a production environment, always use a web server such as Apache or Nginx to serve your website. Do not use the PHP development server in a production environment.

Next you need to configure a content security policy, enable HSTS, and set the content type options by adding the following to the top of your router script:
```php
header("Content-Security-Policy: default-src 'none'; style-src 'self' 'sha256-faU7yAF8NxuMTNEwVmBz+VcYeIoBQ2EMHW3WaVxCvnk='; script-src 'self'; img-src 'self' data:; form-action 'self'; connect-src 'self'; frame-ancestors 'none'; upgrade-insecure-requests;");
header('Strict-Transport-Security: max-age=63072000');
header('X-Content-Type-Options: nosniff');
```

You also need to enable cookie security by adding the following above where you start a session in your router script:
```php
session_set_cookie_params(array(
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
));
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
```
