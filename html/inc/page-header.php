<?php require_once(__DIR__ . '/../inc/user.php'); ?>
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
        <div class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
            <div class="container-fluid">
                <a class="navbar-brand" href="/">Simple Todo</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <?php
                        // Is the user signed in?
                        if(!isSignedIn()) {
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/accounts/sign-in/">Sign In</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/accounts/sign-up/">Sign Up</a>
                            </li>
                            <?php
                        } else {
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/accounts/sign-out/">Sign Out</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/accounts/delete/">Delete Account</a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row justify-center">
                <div class="col alert alert-info"><strong>Notice:</strong> This website is provided for 
                    demonstration purposes only. <strong>Do not use personal information on any of the 
                    forms.</strong> We use cookies for authentication and security purposes only. IP 
                    logging is used for security and traffic monitoring only.</strong></div>
            </div>
