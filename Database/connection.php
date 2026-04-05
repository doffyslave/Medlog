<?php 
$servername = 'localhost';
$user_id = 'root';
$password = '';

try{
    $conn = new PDO("mysql:host=$servername;dbname=medlog_db", $user_id, $password);

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\Exception $e) {
    $error_message = $e->getMessage();
}