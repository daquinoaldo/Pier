<?php

function newMessage($code, $text) {
    $message = (object) [
        'code' => $code,
        'text' => $text,
    ];
    return json_encode($message);
}

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

function addUser($username, $password, $email) {
    $password = md5($password);
    $sql = "INSERT INTO users (username, password, email) VALUES ('$username', '$password', '$email')";
    return query($sql);
}

function getUserList() {
    $sql = "SELECT * FROM users";
    return query($sql);
}

function addWebsite($username, $domain) {
    $sql = "INSERT INTO websites (username, domain) VALUES ('$username', '$domain')";
    return query($sql);
}

function getWebsiteList($username) {
    $sql = "SELECT * FROM websites WHERE username='$username'";
    return query($sql);
}

function encodeQuery($result) {
    if(is_bool($result)) return json_encode($result);
    $rows = array();
    while($r = mysqli_fetch_assoc($result))
        $rows[] = $r;
    return json_encode($rows);
}

function login() {
    if(empty($_POST['username']) || empty($_POST['password']))
        die(newMessage(-1, "Username or password empty."));
    $username = htmlentities($_POST['username'], ENT_QUOTES);
    $password = htmlentities($_POST['password'], ENT_QUOTES);
    if(!validateUser($username, $password)) die(newMessage(-2, "Username or password wrong."));
    session_start();
    $_SESSION["username"] = $username;
}