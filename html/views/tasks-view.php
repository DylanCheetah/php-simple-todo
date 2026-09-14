<div id="tasksView">
    <?php
    // Load task functions
    require_once(__DIR__ . '/../inc/task.php');

    // Get all the tasks associated with the todo list
    $page = getTasks($objectId);

    // Are there any tasks to display?
    if(count($page['tasks'])) {
        // Display all tasks
        foreach($page['tasks'] as $task) {
            require(__DIR__ . '/task-info.php');
        }
    } else {
        ?>
        <div>No tasks.</div>
        <?php
    }
    ?>
    <div class="row m-1 mt-4 justify-content-center">
        <?php
        // Is there a previous URL?
        if($page['prevUrl'] === null) {
            ?>
            <button class="col-lg-2 col-md-3 col-sm-12 m-1 btn btn-secondary">Previous</button>
            <?php
        } else {
            ?>
            <button class="col-lg-2 col-md-3 col-sm-12 m-1 btn btn-primary" 
                hx-get="<?php echo $page['prevUrl']; ?>" 
                hx-swap="outerHTML" hx-target="#tasksView"
                hx-push-url="<?php echo $page['prevUrl']; ?>">Previous</button>
            <?php
        }

        // Is there a next URL?
        if($page['nextUrl'] === null) {
            ?>
            <button class="col-lg-2 col-md-3 col-sm-12 m-1 btn btn-secondary">Next</button>
            <?php
        } else {
            ?>
            <button class="col-lg-2 col-md-3 col-sm-12 m-1 btn btn-primary" 
                hx-get="<?php echo $page['nextUrl']; ?>" 
                hx-swap="outerHTML" hx-target="#tasksView"
                hx-push-url="<?php echo $page['nextUrl']; ?>">Next</button>
            <?php
        }
        ?>
    </div>
</div>
