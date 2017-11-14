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


function getPort($port_to_exlude) {
	$port = -1;
	$handle = fopen("port", "r+");
	if($handle) {
		if (($line = fgets($handle)) == false) die("Error: cant't read the port file located in the www folder");
		$port = intval($line);
		$port++;
		while(in_array($port, $port_to_exlude)) $port++;
		fseek($handle, 0);
		if(fputs($handle, $port, strlen($port)) == false) die("Error: can't write the port file located in the www folder");
		fclose($handle);
	} else die("Error: cant't open the port file located in the www folder");
	if($port==-1) die("Error getting port.");
	return $port;
}

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

function create_ftp ($username, $password, $home) {
	$bash_command = "sudo docker exec ftp bash /add-user.sh $username $password $home";
	$output = shell_exec($bash_command);
}

function create_sql ($mysql_rootpw, $database, $username, $password) {
	$link = mysqli_connect("172.17.0.1:3306", "root", "$mysql_rootpw");
	if (!$link) die('Error in connecting to mysql.');
	// Create database
	$sql = "CREATE DATABASE `$database`";
	$result = mysqli_query($link, $sql);
	if (!$result) die('Error in creating database.');
	// Create user
	$sql = "CREATE USER '$username'@'%' IDENTIFIED BY '$password'";
	$result = mysqli_query($link, $sql);
	// Flush privileges
	$sql = "FLUSH PRIVILEGES";
	$result = mysqli_query($link, $sql);
	if (!$result) die('Error in flushing privileges.');
	// Grant privileges
	$sql = "GRANT ALL PRIVILEGES ON `$database`.* TO '$username'@'%'";
	$result = mysqli_query($link, $sql);		
	if (!$result) die('Error in granting privileges to new user.');
}

if(isset($_POST['submit'])) {
	// Die if missing informations
	if(empty($_POST['domain']) || empty($_POST['webserver']) || empty($_POST['password'])) die("Domain, Web server and password fields are required. Reaload the page and try again.");
	
	// Parsing parameters and updating port
	$domain = htmlentities($_POST['domain'], ENT_QUOTES);
	$parsed_domain = str_replace("-", "_", str_replace(".", "_", $domain));
	$webserver = htmlentities($_POST['webserver'], ENT_QUOTES);	//Someone can edit html, is not that difficult...
	$port = getPort($port_to_exlude);
	$password = htmlentities($_POST['password'], ENT_QUOTES);
	
	// WEBSERVER SWITCH
	//$ugly_options = "2>log/".$parsed_domain.".error 1>log/".$parsed_domain.".log &";
	$ugly_options = "2>/dev/null 1>/dev/null &";	//TODO: fix
	switch ($webserver) {
		case "apache":
			if($_POST['mysql']) {
				$image = "daquinoaldo/php:apache-mysql";
				$volume = "-v $sites_folder/$parsed_domain:/var/www/site/";
				create_sql($mysql_rootpw, $parsed_domain, $parsed_domain, $password);
				recursive_copy("$sites_folder/test_sql/", "$sites_folder/$parsed_domain/");
				//$other_options = "--link mysql:mysql";
			} else if($_POST['php']) {
				$image = "php:apache";
				$volume = "-v $sites_folder/$parsed_domain:/var/www/html/";
				recursive_copy("$sites_folder/test_php/", "$sites_folder/$parsed_domain/");
			}	else {
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
	create_ftp ("$parsed_domain", "$password", "$sites_folder/$parsed_domain");
	
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
	} else { // DEFAULT PAGE	?>
	<form method="post">
		<label for="domain">Domain:</label>
		<input type="text" name="domain" id="domain" placeholder="example.com" autofocus required><br>
		<br>
		<fieldset id="webserver">
			<legend>Web server</legend>
			<label for="nginx">Nginx</label>
			<input type="radio" id="nginx" name="webserver" value="nginx" checked required onclick="radioHandler()">
			<br><br>
			<label for="apache">Apache</label>
			<input type="radio" id="apache" name="webserver" value="apache" onclick="radioHandler()">
			With:
			<label for="php">PHP</label>
			<input type="checkbox" id="php" name="php" onclick="phpHandler()">
			<label for="mysql">MySQL</label>
			<input type="checkbox" id="mysql" name="mysql" onclick="mysqlHandler()">
			<br><br>
			<label for="wordpress">Wordpress</label>
			<input type="radio" id="wordpress" name="webserver" value="wordpress" onclick="radioHandler()">
			Please note that Wordpress setup requires about 30 seconds.
			<br><br>
			<label for="password">Admin Password:</label>
			<input type="password" id="password" name="password" required>
			Password for FTP access and phpMyAdmin (where exist). The username is your domain in parsed form ("." and "-" are replaced with "_" like this: mydomain_com)
		</fieldset>
		<br>
		<input type="hidden" name="submit">
		<input type="submit">
	</form>
	<script>
	apache = document.getElementById("apache");
	nginx = document.getElementById("nginx");
	php = document.getElementById("php");
	mysql = document.getElementById("mysql");
	function radioHandler() {
		if(!apache.checked) {		// you cannot have PHP and/or mySQL without Apache
			php.checked = false;
			mysql.checked = false;
		}
	}
	function mysqlHandler() {
		if (mysql.checked) {		// Apache with PHP required for mySQL
			apache.checked = true;
			php.checked = true;
		}
	}
	function phpHandler() {
		if (php.checked) apache.checked = true;		// PHP requires Apache
		else mysql.checked = false;		// you cannot have mySQL without PHP
	}
	</script>
<?php } ?>
</body>
</html>
