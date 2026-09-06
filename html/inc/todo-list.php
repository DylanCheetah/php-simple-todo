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
    $prevUrl = $hasPrevPage ? "/?start={$prevOffset}" : null;
    $nextUrl = $hasNextPage ? "/?start={$nextOffset}" : null;

    // Return todo list page
    return array(
        'prevUrl'   => $prevUrl,
        'nextUrl'   => $nextUrl,
        'todoLists' => $todoLists
    );
}


function deleteTodoList(int $id): void {
    global $db;

    // Delete the todo list
    $stmt = $db->prepare('DELETE FROM todo_lists WHERE user = ? AND id = ?;');
    $db->beginTransaction();
    $stmt->execute(array($_SESSION['userId'], $id));
    $db->commit();
}
?>
