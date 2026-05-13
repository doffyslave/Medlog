<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../stocks.php');
    exit();
}

$id = (int) ($_POST['id'] ?? 0);
if ($id < 1) {
    echo 'Invalid stock record.';
    exit();
}

// Historical log: only expiration_date may be corrected.
$expRaw = trim((string) ($_POST['expiration_date'] ?? ''));
$expiration = $expRaw !== '' ? $expRaw : null;

$stmt = $conn->prepare('UPDATE stocks SET expiration_date = ? WHERE stock_id = ?');
$stmt->execute([$expiration, $id]);

header('Location: ../stocks.php');
exit();
