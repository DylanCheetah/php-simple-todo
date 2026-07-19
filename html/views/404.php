<?php
$viewName = 'Not Found';
http_response_code(404);
require_once(__DIR__ . '/../inc/page-header.php');
?>
<h1>404 Not Found</h1>
<p>The requested resource was not found on this server.</p>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
