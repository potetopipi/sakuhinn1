<?php
// フラグを返す
$flag = "flag{puyoteto_is_fun}";
header('Content-Type: application/json');
echo json_encode(['flag' => $flag]);
?>
