<?php
require_once "functions.php";

echo json_encode(mysqli_fetch_array(exec_bool("SELECT * FROM websites WHERE id='1'"))['webserver']);