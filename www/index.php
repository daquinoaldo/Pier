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
	#log {
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
	$error_message = "Error: cant't read the port file located in the www folder";
	$handle = fopen("port", "r+");
	if($handle) {
		if (($line = fgets($handle)) == false) die($error_message);
		$port = intval($line);
		$port++;
		while(in_array($port, $port_to_exlude)) $port++;
		fseek($handle, 0);
		if(fputs($handle, $port, strlen($port)) == false) die("Error: can't write the port file located in the www folder");
		fclose($handle);
	} else die($error_message);
	if($port==-1) die("Error getting port.");
	return $port;
}

function recurse_copy($src, $dst) { 
	$dir = opendir($src);
	$old_umask = umask(0);	// Maybe dangerous?
	mkdir($dst, 0777); 
	while(($file = readdir($dir)) !== false) {
		if(($file != '.') && ($file != '..')) {
			if(is_dir($src.'/'.$file)) recurse_copy ($src.'/'.$file, $dst.'/'.$file); 
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
	
	// Parsing parameters and updating port
	$domain = htmlentities($_POST['domain'], ENT_QUOTES);
	$webserver = htmlentities($_POST['webserver'], ENT_QUOTES);	//Someone can edit html, is not that difficult...
	$port = getPort($port_to_exlude);
	$mysqldb = htmlentities($_POST['mysqldb'], ENT_QUOTES);
	$mysqlpw = htmlentities($_POST['mysqlpw'], ENT_QUOTES);
	
	// Build the bash command
	// WEBSERVER SWITCH
/*	if ($webserver == "nginx") {
		$bash_command = "sudo docker run --name $domain -p $port:80 -v $sites_folder/$domain:/usr/share/nginx/html:ro -e VIRTUAL_HOST=$domain nginx 2>/dev/null 1>/dev/null &";
	} */
	switch ($webserver) {
		case "nginx":
			$bash_command = "sudo docker run --name $domain -e VIRTUAL_HOST=$domain -p $port:80 -v $sites_folder/$domain:/usr/share/nginx/html:ro nginx 2>/dev/null 1>/dev/null &";		//TODO: maybe 2>/dev/null is not a good idea... remember to change it in all cases!
			break;
		case "apache":
			$bash_command = "sudo docker run --name $domain -e VIRTUAL_HOST=$domain -p $port:80 -v $sites_folder/$domain:/usr/local/apache2/htdocs/ httpd:2.4 2>/dev/null 1>/dev/null &";
			break;
		case "wordpress":
			$bash_command = "sudo docker run --name $domain-db -e MYSQL_ROOT_PASSWORD=$mysqlpw -e MYSQL_DATABASE=wordpress -d mysql:5.7 2>/dev/null 1>/dev/null && sleep 30 && sudo docker run --name $domain -e VIRTUAL_HOST=$domain -p $port:80 -e WORDPRESS_DB_PASSWORD=$mysqlpw --link $domain-db:mysql wordpress 2>/dev/null 1>/dev/null &";
			break;
}
	
	// Prepare the folder
	recurse_copy($sites_folder."/default/", $sites_folder."/".$domain."/");
	
	// Run the docker command and build the website
	$output = shell_exec($bash_command);
	//exec($bash_command, $output);
	
	// REPORT PAGE ?>
	Your web space is ready! <a href="http://<?php echo $domain; ?>">Visit it!</a>
	Log:<br>
	<div id="log"><?php echo $output; ?></div>
	
<?php } else { // DEFAULT PAGE	?>
	<form method="post">
		<label for="domain">Domain:</label>
		<input type="text" name="domain" id="domain" placeholder="example.com" autofocus required><br>
		<br>
		<fieldset id="webserver">
			<legend>Web server:</legend>
			<label for="nginx">Nginx:</label>
			<input type="radio" id="nginx" name="webserver" value="nginx" checked required onclick="radioHandler()">
			<label for="apache">Apache:</label>
			<input type="radio" id="apache" name="webserver" value="apache" onclick="radioHandler()">
			With:
			<label for="php">PHP:</label>
			<input type="checkbox" id="php" name="php" value="true">
			<label for="mysql">MySQL:</label>
			<input type="checkbox" id="mysql" name="mysql" value="true">
			<br><br>
			<label for="wordpress">Wordpress:</label>
			<input type="radio" id="wordpress" name="webserver" value="wordpress" onclick="radioHandler()">
			<label for="mysqldb">MySQL database name:</label>
			<input type="text" id="mysqldb" name="mysqldb" value="wordpress" disabled>
			<label for="mysqlpw">MySQL password:</label>
			<input type="password" id="mysqlpw" name="mysqlpw" disabled>
			Please note that Wordpress setup requires about 30 seconds.
		</fieldset>
		<br>
		<input type="hidden" name="submit">
		<input type="submit">
	</form>
	<script>
	function radioHandler() {
		if(document.getElementById("wordpress").checked) {
			mysqlpw = document.getElementById("mysqlpw");
			mysqlpw.disabled = false;
			mysqlpw.required = true;
		}
		else {
			mysqlpw = document.getElementById("mysqlpw");
			mysqlpw.disabled = true;
			mysqlpw.required = false;
		}
	}
	</script>
<?php } ?>
</body>
</html>