<html>
<head>
	<title>Docker Webserver Builder</title>
</head>
<body>
	<style>
	body {
		margin-left: 20%;
		margin-right: 20%;
	}
	.log {
		font-family: monospace;
		border: 1px solid #000;
		background: #8e8e8e;
	}
	</style>

<?php

// Default configurations
$start_port = 8001;
$finish_port = 8999;
$port_to_exlude = array(8080, 8888);	//builder and phpmyadmin
$sites_folder = "/sites";
$mysql_rootpw = "r00t";
$DEBUG = true;

function create_ftp ($username, $password, $home) {
	$bash_command = "sudo docker exec ftp bash /add-user.sh $username $password $home";
	$output = shell_exec($bash_command);
}

if(isset($_POST['submit'])) {
    // Die if missing informations
    if (empty($_POST['domain']) || empty($_POST['webserver']) || empty($_POST['password'])) die("Domain, Web server and password fields are required. Reaload the page and try again.");

    // Parsing parameters and updating port
    $domain = htmlentities($_POST['domain'], ENT_QUOTES);
    $parsed_domain = str_replace("-", "_", str_replace(".", "_", $domain));
    $webserver = htmlentities($_POST['webserver'], ENT_QUOTES);    //Someone can edit html, is not that difficult...
    $port = getPort($port_to_exlude);
    $password = htmlentities($_POST['password'], ENT_QUOTES);

    // WEBSERVER SWITCH
    //$ugly_options = "2>log/".$parsed_domain.".error 1>log/".$parsed_domain.".log &";
    $ugly_options = "2>/dev/null 1>/dev/null &";    //TODO: fix
    switch ($webserver) {
        case "apache":
            if ($_POST['mysql']) {
                $image = "daquinoaldo/php:apache-mysql";
                $volume = "-v $sites_folder/$parsed_domain:/var/www/site/";
                create_sql($mysql_rootpw, $parsed_domain, $parsed_domain, $password);
                recursive_copy("$sites_folder/test_sql/", "$sites_folder/$parsed_domain/");
                //$other_options = "--link mysql:mysql";
            } else if ($_POST['php']) {
                $image = "php:apache";
                $volume = "-v $sites_folder/$parsed_domain:/var/www/html/";
                recursive_copy("$sites_folder/test_php/", "$sites_folder/$parsed_domain/");
            } else {
                $image = "httpd:2.4";
                $volume = "-v $sites_folder/$parsed_domain:/usr/local/apache2/htdocs/";
                recursive_copy("$sites_folder/test_html/", "$sites_folder/$parsed_domain/");
            }
            break;
        case "nginx":
            $image = "nginx";
            $volume = "-v $sites_folder/$parsed_domain:/usr/share/nginx/html:ro";
            recursive_copy("$sites_folder/test_html/", "$sites_folder/$parsed_domain/");
            break;
        case "wordpress":
            $image = "wordpress";
            $other_options = "-e WORDPRESS_DB_PASSWORD=$password --link $domain-db:mysql";
            $db_command = "sudo docker run --name $domain-db -e MYSQL_ROOT_PASSWORD=$password -e MYSQL_DATABASE=wordpress -d mysql:5.7 $ugly_options& sleep 30 &&";
            break;
        default:
            $bash_command = "echo \"Error\"";
    }

    // Prepare the ftp user
    create_ftp("$parsed_domain", "$password", "$sites_folder/$parsed_domain");

    // Build the bash command
    if (isset($image)) $bash_command = "$db_command sudo docker run --name $domain -e VIRTUAL_HOST=$domain -p $port:80 $volume $other_options $image $ugly_options";

    // Run the docker command and build the website
    $output = shell_exec($bash_command);

    // REPORT PAGE ?>
    Your web space is ready! <a href="http://<?php echo $domain; ?>">Visit it!</a>
    <?php if ($DEBUG) { ?>
        <br><br>
        Bash command:<br>
        <div class="log"><?php echo $bash_command; ?></div>
        <br><br>
        Log:<br>
        <div class="log"><?php echo json_encode($output); ?></div>

    <?php }
}?>
</body>
</html>
