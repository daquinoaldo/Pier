<?php
require_once "functions.php";

echo json_encode(mysqli_fetch_array(query("SELECT SCOPE_IDENTITY()"))[0]);