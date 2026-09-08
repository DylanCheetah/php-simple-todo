<?php
// Connect to the database
require_once(__DIR__ . '/db.php');

// Task Functions
// ==============
function createTask(int $todoList, string $name, string $dueDate): bool {
    global $db;

    // Create the task
    try {
        $stmt = $db->prepare('INSERT INTO tasks(`todo_list`, `name`, `due_date`) VALUES (?, ?, ?);');
        $db->beginTransaction();
        $stmt->execute(array($todoList, $name, $dueDate));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function getTasks(int $todoList): array {
    global $db;

    // Calculate page offset
    $offset = isset($_GET['start']) ? (int)$_GET['start'] : 0;

    // Get the next page of tasks on the given todo list
    $stmt = $db->prepare('SELECT `id`, `name`, `due_date` FROM tasks WHERE `todo_list` IN (SELECT `id` FROM todo_lists WHERE `id` = ? AND `user` = ?) ORDER BY `name` LIMIT 11 OFFSET ?;');
    $stmt->bindValue(1, $todoList, PDO::PARAM_INT);
    $stmt->bindValue(2, $_SESSION['userId'], PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tasks = $stmt->fetchAll();

    // Are there previous/next pages of tasks?
    $hasPrevPage = $offset >= 10;
    $hasNextPage = false;

    if(count($tasks) == 11) {
        // Remove the 11th task and set the next page flag
        unset($tasks[10]);
        $hasNextPage = true;
    }

    // Generate previous/next page URLs
    $prevOffset = $offset - 10;
    $nextOffset = $offset + 10;
    $prevUrl = $hasPrevPage ? "/todo-lists/$todoList/?start=$prevOffset" : null;
    $nextUrl = $hasNextPage ? "/todo-lists/$todoList/?start=$nextOffset" : null;

    // Return task data
    return array(
        'prevUrl' => $prevUrl,
        'nextUrl' => $nextUrl,
        'tasks'   => $tasks
    );
}


function getTask(int $id): array {
    global $db;

    // Get the task with the given ID
    $stmt = $db->prepare('SELECT `id`, `todo_list`, `name`, `due_date` FROM tasks WHERE `id` = ? AND `todo_list` IN (SELECT `id` FROM todo_lists WHERE `user` = ?);');
    $stmt->execute(array($id, $_SESSION['userId']));
    return $stmt->fetchAll()[0];
}


function updateTask(int $id, string $name, string $dueDate): bool {
    global $db;

    // Update the given task
    try {
        $stmt = $db->prepare('UPDATE tasks SET `name` = ?, `due_date` = ? WHERE `id` = ? AND `todo_list` IN (SELECT `id` FROM todo_lists WHERE `user` = ?);');
        $db->beginTransaction();
        $stmt->execute(array($name, $dueDate, $id, $_SESSION['userId']));
        $db->commit();
    } catch(PDOException $e) {
        return false;
    }

    return true;
}


function deleteTask(int $id): void {
    global $db;

    // Delete the given task
    $stmt = $db->prepare('DELETE FROM tasks WHERE `id` = ? AND `todo_list` IN (SELECT `id` FROM todo_lists WHERE `user` = ?);');
    $db->beginTransaction();
    $stmt->execute(array($id, $_SESSION['userId']));
    $db->commit();
}
?>
