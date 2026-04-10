<?php
require 'database/connection.php';

$stmt = $pdo->prepare("UPDATE inventory SET name=?, category=?, quantity=?, unit=?, expiration_date=?, min_stock=? WHERE inventory_id=?");

$stmt->execute([
    $_POST['name'],
    $_POST['category'],
    $_POST['quantity'],
    $_POST['unit'],
    $_POST['expiration_date'] ?: null,
    $_POST['min_stock'],
    $_POST['id']
]);

header("Location: inventory.php");