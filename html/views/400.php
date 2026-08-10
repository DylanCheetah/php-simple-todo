<?php
$viewName = 'Bad Request';
http_response_code(400);
require_once(__DIR__ . '/../inc/page-header.php');
?>
<h1>400 Bad Request</h1>
<p>The request sent to the server was invalid.</p>
<?php require_once(__DIR__ . '/../inc/page-footer.php'); ?>
