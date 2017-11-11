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
    return $_SESSION['username'];
}

/* WEBSITES */
function addWebsite($username, $domain) {
    $sql = "INSERT INTO websites (username, domain) VALUES ('$username', '$domain')";
    return query($sql);
}

function getWebsiteList($username) {
    $sql = "SELECT * FROM websites WHERE username='$username'";
    return query($sql);
}