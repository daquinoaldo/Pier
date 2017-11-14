<?php
/*
 * DATABASE FUNCTIONS
 * Manage users and database on the websites mysql container
 */

function db_connect() {
    $host = "dwb.aldodaquino.com:3306";
    $root_pw = "r00t";
    $link = mysqli_connect($host, "root", $root_pw);
    if (!$link) {
        error_log("Cannot connect to mysql at host $host.");
        return null;
    }
    return $link;
}

function db_query($sql) {
    $link = db_connect();
    $result = mysqli_query($link, $sql);
    mysqli_close($link);
    return $result;
}

function db_create_user ($username, $password) {
    $link = db_connect();
    // Create user
    $sql = "CREATE USER IF NOT EXISTS '$username'@'%' IDENTIFIED BY '$password'";
    $result = mysqli_query($link, $sql);
    if (!$result) {
        error_log("Cannot create user $username.");
        mysqli_close($link);
        return false;
    }
    // Flush privileges
    $sql = "FLUSH PRIVILEGES";
    $result = mysqli_query($link, $sql);
    if (!$result) {
        error_log("Error in flushing privileges of user $username.");
        mysqli_close($link);
        return false;
    }
    mysqli_close($link);
    return true;
}

/*function db_rename_user ($old_username, $new_username) {
    if (!db_query("RENAME USER '$old_username' TO '$new_username'")) {
        error_log("Cannot rename $old_username in $new_username.");
        return false;
    }
    return true;
}*/

function db_create_database ($username, $database) {
    $link= db_connect();
    // Create database
    $sql = "CREATE DATABASE `$database`";
    $result = mysqli_query($link, $sql);
    if (!$result) {
        error_log("Cannot create database $database.");
        mysqli_close($link);
        return false;
    }
    // Grant privileges
    $sql = "GRANT ALL PRIVILEGES ON `$database`.* TO '$username'@'%'";
    $result = mysqli_query($link, $sql);
    if (!$result) {
        error_log("Error in granting privileges to the user $username.");
        mysqli_close($link);
        return false;
    }
    mysqli_close($link);
    return true;
}

function db_create_user_database ($username, $password, $database) {
    if(!db_create_user($username, $password)) return false;
    return db_create_database($username, $database);
}