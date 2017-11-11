<?php
require_once "functions.php";

session_start();
if(empty($_SESSION['username'])) die(newMessage(-1, "Not logged in."));

echo newMessage(0, $_SESSION["username"]);