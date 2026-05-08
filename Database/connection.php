<?php

// Detect environment
$isLocalhost = in_array($_SERVER['SERVER_NAME'], [
    'localhost',
    '127.0.0.1'
]);

if ($isLocalhost) {
    // LOCAL PC
    $servername = 'localhost';
    $dbname     = 'medlog_db';
    $user_id    = 'root';
    $password   = '';
} else {
    // HOSTING / VPS
    $servername = 'localhost';
    $dbname     = 'medlog_db';
    $user_id    = 'budo_root';
    $password   = 'puta143';
}

try {

    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $user_id,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage());

}
?>