<?php
require_once __DIR__."/../lib/blobbulb/blobbulb.php";

$json_text = file_get_contents('php://input');
$json_obj = json_decode($json_text, true);

header('Content-Type: application/json');
echo bllb\object2bllb($json_obj);

?>
