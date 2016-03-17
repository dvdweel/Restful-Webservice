<?php
// database gegevens
$servername = "localhost";
$username = "root";
$password = "";
$db = "webservices";


// connectie maken
$conn = new mysqli($servername, $username, $password, $db);

// controleer de connectie
// als de connectie succesvol is: Connected succesfully
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>