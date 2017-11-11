<?php
require_once "functions.php";

echo json_encode(mysqli_fetch_all(query("SELECT * FROM users"), MYSQLI_ASSOC));