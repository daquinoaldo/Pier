<?php
$servername = "172.17.0.1:3306";
$username = "mysql_aldodaquino_ml";
$password = "password";
$dbname = $username;

// Create connection
echo "Connecting to database... ";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
else echo "Connected.<br><br>";

// Create table
echo "Creating table... ";
$sql = "CREATE TABLE MyGuests (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, firstname VARCHAR(30) NOT NULL, lastname VARCHAR(30) NOT NULL, email VARCHAR(50), reg_date TIMESTAMP)";
if($_GET['debug']) echo "SQL query: $sql";
if ($conn->query($sql) === TRUE) echo "Table MyGuests created successfully.<br><br>";
else die("Error: " . $conn->error);

// Insert
echo "Inserting a record into MyGuest... ";
$sql = "INSERT INTO MyGuests (firstname, lastname, email)
VALUES ('John', 'Doe', 'john@example.com')";
if($_GET['debug']) echo "SQL query: $sql";
if ($conn->query($sql) === TRUE) echo "New record created successfully.<br><br>";
else die("Error: " . $conn->error);

// Select
echo "Selecting id and name from MyGuest... Espected 1 result: \"ID: 1 - Name: John Doe\"";
$sql = "SELECT id, firstname, lastname FROM MyGuests";
if($_GET['debug']) echo "SQL query: $sql";
$result = $conn->query($sql);
if ($result->num_rows > 0)
	while($row = $result->fetch_assoc())
		echo "ID: " . $row['id'] . " - Name: " . $row['firstname'] . " " . $row['lastname'];
else echo "0 results";

$conn->close();
?>
