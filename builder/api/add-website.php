<?php
require_once "functions.php";

$username = getUsername();
if(empty($username)) die(newMessage(-1, "Not logged in."));

// Domain
if (empty($_POST['domain'])) die(newMessage(-2, "Missing domain."));
$domain = htmlentities($_POST['domain'], ENT_QUOTES);

// Port
$port = getPort();
if (empty($port)) die(newMessage(-3, "All the ports are in use, cannot allocate another port.".
    "Consider increment the port range."));

// Webserver type
if (empty($_POST['webserver'])) die(newMessage(-4, "Missing webserver type."));
$webserver = htmlentities($_POST['webserver'], ENT_QUOTES);
if ($webserver != "apache" && $webserver != "nginx")
    die(newMessage(-5, "$webserver is not a supported webserver. ".
        "Supported web servers are Apache and Nginx"));

// PHP
$php = false;
if (!empty($_POST['php']) && $_POST['php'] === "true") $php = true;

// Add websites informations in database
if(!addWebsiteToDatabase($username, $domain, $port, $webserver, $php))
    die(newMessage(-6, "Error when writing to database."));

echo newMessage(0, "Website created.");