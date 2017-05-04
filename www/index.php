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
$port_to_exlude = array(8080, 8888);
$sites_folder = "/sites";


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

if(isset($_POST['submit'])) {
	// Die if missing informations
	if(empty($_POST['domain']) || empty($_POST['webserver'])) die("Domain and Web server field are required. Reaload the page and try again.");
	if($_POST['mysql'] && (empty($_POST['mysqldb']) || empty($_POST['mysqlpw']))) die("MySQL database and password needed. Reaload the page and try again.");
	
	// Parsing parameters and updating port
	$domain = htmlentities($_POST['domain'], ENT_QUOTES);
	$webserver = htmlentities($_POST['webserver'], ENT_QUOTES);	//Someone can edit html, is not that difficult...
	$port = getPort($port_to_exlude);
	$mysqldb = htmlentities($_POST['mysqldb'], ENT_QUOTES);
	$mysqlpw = htmlentities($_POST['mysqlpw'], ENT_QUOTES);
	
	// WEBSERVER SWITCH
	// $ugly_options = "2>log/$domain.error 1>log/$domain.log &";
	$ugly_options = "2>/dev/null 1>/dev/null &";	//TODO: fix
	switch ($webserver) {
		case "apache":
			if($_POST['php']) {
				$image = "php:7.0-apache";
				$volume = "-v $sites_folder/$domain:/var/www/html/";
				recursive_copy("$sites_folder/test_php/", "$sites_folder/$domain/");
			}	else {
				$image = "httpd:2.4";
				$volume = "-v $sites_folder/$domain:/usr/local/apache2/htdocs/";
				recursive_copy("$sites_folder/test_html/", "$sites_folder/$domain/");
			}
			break;
		case "nginx":
			$image = "nginx";
			$volume = "-v $sites_folder/$domain:/usr/share/nginx/html:ro";
			recursive_copy("$sites_folder/test_html/", "$sites_folder/$domain/");
			break;
		case "wordpress":
			$image = "wordpress";
			$other_options = "-e WORDPRESS_DB_PASSWORD=$mysqlpw --link $domain-db:mysql";
			$db_command = "sudo docker run --name $domain-db -e MYSQL_ROOT_PASSWORD=$mysqlpw -e MYSQL_DATABASE=wordpress -d mysql:5.7 $ugly_options && sleep 30 &&";
			break;
	}
	// Build the bash command
	if (isset($image)) $bash_command = "$db_command sudo docker run --name $domain -e VIRTUAL_HOST=$domain -p $port:80 $volume $other_options $image $ugly_options";
	
	// Prepare the folder
	//recursive_copy("$sites_folder/default/", "$sites_folder/$domain/");
	
	// Run the docker command and build the website
	$output = shell_exec($bash_command);
	//exec($bash_command, $output);
	
	// REPORT PAGE ?>
	Your web space is ready! <a href="http://<?php echo $domain; ?>">Visit it!</a>
	<br><br>
	Bash command:<br>
	<div class="log"><?php echo $bash_command; ?></div>
	<br><br>
	Log:<br>
	<div class="log"><?php echo json_encode($output); ?></div>
	
<?php } else { // DEFAULT PAGE	?>
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
		</fieldset>
		<br><br>
		<fieldset id="mysqlfieldset" style="display: none;">
			<legend>MySQL settings</legend>
			<label for="mysqldb">MySQL database name:</label>
			<input type="text" id="mysqldb" name="mysqldb" disabled>
			<br>
			<label for="mysqlpw">MySQL password:</label>
			<input type="password" id="mysqlpw" name="mysqlpw" disabled>
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
	mysqlfieldset = document.getElementById("mysqlfieldset");
	mysqldb = document.getElementById("mysqldb");
	mysqlpw = document.getElementById("mysqlpw");
	function enableSQL() {
		mysqldb.disabled = false;
		mysqldb.required = true;
		mysqlpw.disabled = false;
		mysqlpw.required = true;
		mysqlfieldset.style.display = "block";
	}
	function disableSQL() {
		mysqlfieldset.style.display = "none";
		mysqldb.disabled = true;
		mysqldb.required = false;
		mysqlpw.disabled = true;
		mysqlpw.required = false;
	}
	function radioHandler() {
		wordpress = document.getElementById("wordpress");
		if(!apache.checked) {		//nginx and wordpress		TODO: and maybe also apache?
			php.checked = false;
			mysql.checked = false;
		}
		if(wordpress.checked) {
			enableSQL();
			mysqldb.disabled = true;
			mysqldb.value = "wordpress";
		} else {
			disableSQL();
		}
	}
	function mysqlHandler() {
		if (mysql.checked) {
			apache.checked = true;
			php.checked = true;
			mysqldb.value = "";
			enableSQL();
		}	else disableSQL();
	}
	function phpHandler() {
		if (php.checked) {
			apache.checked = true;
			if (!mysql.checked) disableSQL();
		}
		else {
			mysql.checked = false;
			disableSQL();
		}
	}
	</script>
<?php } ?>
</body>
</html>
