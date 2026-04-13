<?php
require 'connection.php';

$stmt = $conn->prepare("
INSERT INTO inventory (med_id, category, quantity, unit, expiration_date, min_stock)
VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $_POST['med_id'],
    $_POST['category'],
    $_POST['quantity'],
    $_POST['unit'],
    $_POST['expiration_date'] ?: null,
    $_POST['min_stock']
]);

header("Location: ../inventory.php");
exit;