<?php
$host = "sql312.epizy.com";             
$user = "if0_39295639";                 
$pass = "Snackworld003";         
$db   = "if0_39295639_snackworld";      

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
