<?php

/* LOGS and MESSAGES */
function newMessage($code, $text) {
    $message = (object) [
        'code' => $code,
        'text' => $text,
    ];
    return json_encode($message);
}

/* FILE MANAGER */
function recursive_copy($src, $dst) {
    $dir = opendir($src);
    if (!$dir) {
        error_log("Can't open $src.");
        return false;
    }
    $old_umask = umask(0);	// Maybe dangerous?
    if (umask() != 0) {
        error_log("Can't set the umask to 0. The umask value is ".umask().".");
        return false;
    }
    mkdir($dst, 0777);
    while($file = readdir($dir) !== false) {
        if($file != '.' && $file != '..') {
            if(is_dir($src.'/'.$file)) recursive_copy ($src.'/'.$file, $dst.'/'.$file);
            else if(copy($src.'/'.$file, $dst.'/'.$file) == false) {
                error_log("Error in copying the default folder.");
                return false;
            }
        }
    }
    umask($old_umask);
    if ($old_umask != umask()) {
        error_log("Error while changing back the umask. The umask value is ".umask().".");
        return false;
    }
    closedir($dir);
    return true;
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
    session_start();
    return $_SESSION['username'];
}

/* WEBSITES LIST */
function addWebsiteToDatabase($username, $domain, $port, $webserver, $php) {
    $sql = "INSERT INTO websites (username, domain, port, webserver, php)".
        "VALUES ('$username', '$domain', '$port', '$webserver', '$php')";
    if(!query($sql)) return -1;
    return mysqli_fetch_array(query("SELECT SCOPE_IDENTITY()"))[0];
}

function getWebsitesList($username) {
    $sql = "SELECT * FROM websites WHERE username='$username'";
    return query($sql);
}

/* PORTS */
function getPort() {
    $start_port = 8000;
    $finish_port = 8999;
    $port_to_exclude = array(8000, 8080, 8888);	// builder-mysql, builder and phpmyadmin

    $port = mysqli_fetch_array(query("SELECT MIN(port) AS port FROM websites"))['port']; // last port used
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

/* DOCKER */
function dockerRun ($name, $domain, $port, $volume, $image, $options) {
    echo "DOCKER RUN";
    if(!empty($volume)) $volume = "-v ".$volume;
    echo shell_exec("sudo docker run --name $name -e VIRTUAL_HOST=$domain -p $port:80 $volume $options $image");
}

function ftpAddUser ($username, $password, $home) {
    echo "FTP ADD USER";
    echo shell_exec("sudo docker exec ftp bash /add-user.sh $username $password $home");
}

function builderRun($id) {
    $sites_folder = "/sites";

    $website = mysqli_fetch_array(query("SELECT * FROM websites WHERE id='$id'"));

    recursive_copy("$sites_folder/test_html/", "$sites_folder/".$website['id']."/");    // can return false
    $volume = "$sites_folder/".$website['id'].":/var/www/site/";

    switch ($website['webserver']) {
        case "apache":
            $image = "httpd";
            break;
        case "nginx":
            $image = "nginx";
            break;
        default:
            error_log("Unknown web server ".$website['webserver']);
            return false;
            break;
    }
    dockerRun($website['id'], $website['domain'], $website['port'], $volume, $image, "");
    return true;
}