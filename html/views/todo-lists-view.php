<div id="todoListsView">
    <?php
    // Load todo list functions
    require_once(__DIR__ . '/../inc/todo-list.php');

    // Get a page of results
    $page = getTodoLists();

    // Are there any todo lists to show?
    if(count($page['todoLists'])) {
        // Show all todo lists on the page
        foreach($page['todoLists'] as $todoList) {
            ?>
            <div class="row m-1">
                <div class="col card bg-white">
                    <div class="card-body row">
                        <a class="col-9 nav-link" href="/todo-lists/<?php echo $todoList['id']; ?>/">
                            <?php echo $todoList['name']; ?></a>
                        <form class="col-3 row" hx-post="/todo-lists/<?php echo $todoList['id']; ?>/delete/"
                            hx-indicator="#todoListDeleteBtn<?php $todoList['id']; ?>">
                            <input type="hidden" name="csrfToken" value="<?php echo csrfToken(); ?>"/>
                            <button class="col btn btn-danger">
                                <span class="spinner-border spinner-border-sm htmx-indicator"
                                    id="todoListDeleteBtn<?php $todoList['id']; ?>">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="row">
            <div class="col">No todo lists found.</div>
        </div>
        <?php
    }
    ?>
    <div class="row m-1 mt-4 justify-content-center">
        <?php
        // Is there a previous URL?
        if($page['prevUrl'] === null) {
            ?>
            <button class="col-2 m-1 btn btn-secondary">Previous</button>
            <?php
        } else {
            ?>
            <button class="col-2 m-1 btn btn-primary" hx-get="<?php echo $page['prevUrl']; ?>" 
                hx-swap="outerHTML" hx-target="#todoListsView" 
                hx-push-url="<?php echo $page['prevUrl']; ?>">Previous</button>
            <?php
        }
        ?>
        <?php
        // Is there a next URL?
        if($page['nextUrl'] === null) {
            ?>
            <button class="col-2 m-1 btn btn-secondary">Next</button>
            <?php
        } else {
            ?>
            <button class="col-2 m-1 btn btn-primary" hx-get="<?php echo $page['nextUrl']; ?>"
                hx-swap="outerHTML" hx-target="#todoListsView"
                hx-push-url="<?php echo $page['nextUrl']; ?>">Next</button>
            <?php
        }
        ?>
    </div>
</div>
