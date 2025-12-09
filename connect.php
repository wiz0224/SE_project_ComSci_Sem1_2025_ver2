<?php
// DAPAT ITO ANG PINAKA UNANG LINYA
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Maaari ring gamitin ang: if (!isset($_SESSION)) { session_start(); }

$host="localhost";
$user="root";
$pass="";
$db="login";

$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){
    echo "Failed to connect DB: " . $conn->connect_error;
}
?>