<?php 


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$name = "localhost";
$username = "root";
$password = "";
$db_name = "CSM-System";

// Establish the connection
$conn = mysqli_connect($name, $username, $password, $db_name);

// Check the connection
if($conn){
    // echo 'Connected to the database successfully'; 
} else {
    echo "Failed to connect to the database"; 
}

?>
