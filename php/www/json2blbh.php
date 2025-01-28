<?php
require_once __DIR__."/../lib/blobhive/blobhive.php";

$json_text = file_get_contents('php://input');
$json_obj = json_decode($json_text, true);


header('Content-Type: application/json');
echo json_encode($json_obj);

?>
