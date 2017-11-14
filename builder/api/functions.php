<?php

/*GENERIC FUNCTIONS */
function newMessage($code, $text) {
    $message = (object) [
        'code' => $code,
        'text' => $text,
    ];
    return json_encode($message);
}

/* DATABASE */
/*
function query($servername, $port, $username, $password, $dbname, $sql) {
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    if ($conn->connect_error)
        die(newMessage(-1, "Connection failed: ".$conn->connect_error));
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}

function builder_query($sql) {
    $servername = "dwb.aldodaquino.com";
    $port = 8000;
    $username = "root";
    $password = "r00t";
    $dbname = "builder";
    return query($servername, $port, $username, $password, $dbname, $sql);
}*/

function query($sql) {
    $servername = "dwb.aldodaquino.com";
    $port = 8000;
    $username = "root";
    $password = "r00t";
    $dbname = "builder";
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    if ($conn->connect_error)
        die(newMessage(-1, "Connection failed: ".$conn->connect_error));
    $result = $conn->query($sql);
    $conn->close();
    return $result;
}

/* FETCH QUERY RESULT:
$row = mysqli_fetch_array($result)                  // $row is an array with all the column content of a row
$rows = mysqli_fetch_all($result, [MYSQLI_BOTH])    // $rows is an array with all the rows
$rows = mysqli_fetch_all($result, [MYSQLI_ASSOC])
*/


/* USERS, LOGIN and SESSION */
function signUp($username, $password, $email) {
    $password = md5($password);
    $sql = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";
    if(query($sql) !== true) return false;
    session_start();
    $_SESSION['username'] = $username;
    session_commit();
    return true;
}

function login($username, $password) {
    $result = query("SELECT password FROM users WHERE username='$username'");
    if(!$result) return false;
    $result = mysqli_fetch_array($result)['password'];
    $password = md5($password);
    if($result !== $password) return false;
    session_start();
    $_SESSION['username'] = $username;
    session_commit();
    return true;
}

function getUsername() {
    session_start();
    return $_SESSION['username'];
}

/* WEBSITES LIST */
function addWebsiteToDatabase($username, $domain, $port, $webserver, $php) {
    $sql = "INSERT INTO websites (username, domain, port, webserver, php)".
        "VALUES ('$username', '$domain', '$port', '$webserver', '$php')";
    return query($sql);
}

function getWebsiteList($username) {
    $sql = "SELECT * FROM websites WHERE username='$username'";
    return query($sql);
}

function getPort() {
    $start_port = 8000;
    $finish_port = 8999;
    $port_to_exclude = array(8000, 8080, 8888);	// builder-mysql, builder and phpmyadmin

    $port = mysqli_fetch_assoc(query("SELECT MIN(port) AS port FROM websites"))['port']; // last port used
    if ($port == null) $port = $start_port; // there is no active website
    else $port++;    // last port used + 1 = next port number
    while (in_array($port, $port_to_exclude)) $port++;    // if the port is reserved increment again
    if ($port > $finish_port) {
        for ($i = $start_port; $i <= $finish_port; $i++)    // check if there is a port that is not in use
            if(query("SELECT COUNT(1) FROM websites WHERE port = '$i'") > 0) {
                $port = $i;
                break;
            }
        if ($port > $finish_port) { // if there is no ports free in absolute
            error_log("All the ports are in use, cannot allocate another port.");
            return null;
        }
    }
    return $port;
}

/* COPIED FROM WWW/INDEX:PHP */

$sites_folder = "/sites";

function recursive_copy($src, $dst) {
    $dir = opendir($src);
    $old_umask = umask(0);	// Maybe dangerous?
    mkdir($dst, 0777);
    while(($file = readdir($dir)) !== false) {
        if(($file != '.') && ($file != '..')) {
            if(is_dir($src.'/'.$file)) recursive_copy ($src.'/'.$file, $dst.'/'.$file);
            else if(copy($src.'/'.$file, $dst.'/'.$file) == false) die("Error in copying the default folder.");
        }
    }
    umask($old_umask);
    if($old_umask != umask()) die("Error while changing back the umask. We suggest to rebuild and relaunch the container.");
    closedir($dir);
}