<?php
// Connect to the database
require_once(__DIR__ . '/db.php');


// User Functions
// ==============
function createUser(string $username, string $password): bool {
    global $db;

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Create a user account
    try {
        $stmt = $db->prepare('INSERT INTO users(`username`, `password`) VALUES (?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($username, $passwordHash));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function signIn(string $username, string $password): bool {
    global $db;

    // Get the ID and password hash of the given user
    $stmt = $db->prepare('SELECT `id`, `password` FROM users WHERE `username` = ?;');
    $stmt->execute(array($username));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(!count($rows)) {
        return false;
    }

    $user = $rows[0];

    // Verify the password
    if(!password_verify($password, $user['password'])) {
        return false;
    }

    // Store the user's ID in their session
    $_SESSION["userId"] = $user['id'];
    return true;
}


function signOut(): void {
    // Clear all session variables
    session_unset();
}


function deleteUser(int $userId): void {
    global $db;

    // Delete the user account
    $stmt = $db->prepare('DELETE FROM users WHERE `id` = ?;');
    $db->beginTransaction();
    $stmt->execute(array($userId));
    $db->commit();

    // Sign out
    signOut();
}
?>
