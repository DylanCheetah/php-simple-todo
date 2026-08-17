<?php
// CSRF Functions
// ==============
function csrfToken(): string {
    // Check if there is a CSRF token associated with the current session
    if(!isset($_SESSION['csrfToken'])) {
        // Generate new CSRF token and store it in the current session
        $_SESSION['csrfToken'] = random_bytes(32);
    }

    // Mask the CSRF token with random salt and return it
    $salt = random_bytes(32);
    $token = $_SESSION['csrfToken'] ^ $salt;
    $token = $token . $salt;
    return bin2hex($token);
}


function verifyCsrfToken(): bool {
    // Check if the CSRF token is present in the form POST data
    if(!isset($_POST['csrfToken'])) {
        return false;
    }

    // Get salt and original token
    $token = hex2bin($_POST['csrfToken']);
    $salt = substr($token, 32, 32);
    $token = substr($token, 0, 32) ^ $salt;

    // Verify token
    return hash_equals($_SESSION['csrfToken'], $token);
}
?>
