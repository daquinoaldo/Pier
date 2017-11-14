<?php
require_once "functions.php";

$username = getUsername();
if(empty($username)) die(newMessage(-1, "Not logged in."));

$result = getWebsiteList($_SESSION['username']);
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo newMessage(0, $rows);