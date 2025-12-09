
<?php

$host="localhost";
$user="root";
$pass=""; // Tiyakin na ito ang tama
$db="login"; // Tiyakin na ito ang tamang database name
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){
    die("Failed to connect DB: " . $conn->connect_error);
}
?>