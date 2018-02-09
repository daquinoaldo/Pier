<?php
require_once "functions.php";

$username = getUsername();
if (empty($username)) die(newMessage(-1, "Not logged in."));

// Domain
if (empty($_POST['domain'])) die(newMessage(-2, "Missing domain."));
$domain = htmlentities($_POST['domain'], ENT_QUOTES);

// Port
$port = getPort();
if (empty($port)) {
    error_log("Error in ".__FILE__." at line ".__LINE__.":".
    "all the ports are in use, cannot allocate another port. Consider increment the port range.");
    die(newMessage(-3, "All the ports are in use, cannot allocate another port. ".
        "Contact the administrator."));
}

// Webserver type
if (empty($_POST['webserver'])) die(newMessage(-4, "Missing webserver type."));
$webserver = htmlentities($_POST['webserver'], ENT_QUOTES);
if ($webserver != "apache" && $webserver != "nginx")
    die(newMessage(-5, "$webserver is not a supported webserver. ".
        "Supported web servers are Apache and Nginx"));

// PHP
$php = false;
if (!empty($_POST['php']) && $_POST['php'] === "true") $php = true;
error_log("PHPHPHPHPHPHP".$php);
// Add websites informations in database
$id = addWebsiteToDatabase($username, $domain, $port, $webserver, $php);
if ($id < 0) {
    error_log("Error in ".__FILE__." at line ".__LINE__.": error when writing to database. ".
        "username = \"$username\", domain = \"$domain\", port = \"$port\", webserver = \"$webserver\", php = \"$php\".");
    die(newMessage(-6, "Error when writing to database."));
}

if (!builderRun($id)) {
    error_log("Error in ".__FILE__." at line ".__LINE__.": cannot run the configuration with id $id");
    die(newMessage(-7,"Cannot run the configuration. Please delete it and try again."));
}

echo newMessage(-10, "Website created.");