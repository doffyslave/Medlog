<?php
require 'database/connection.php';

$stmt = $pdo->prepare("UPDATE stocks SET name=?, category=?, unit=?, expiration_date=?, min_stock=? WHERE stocks_id=?");

$stmt->execute([
    $_POST['name'],
    $_POST['category'],
    $_POST['unit'],
    $_POST['expiration_date'] ?: null,
    $_POST['min_stock'],
    $_POST['id']
]);

header("Location: stocks.php");