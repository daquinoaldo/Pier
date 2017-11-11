<?php
require_once "functions.php";

session_start();
if(empty($_SESSION['username'])) die(newMessage(-1, "Not logged in."));

$result = getWebsiteList($_SESSION['username']);
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

echo newMessage(0, $rows);