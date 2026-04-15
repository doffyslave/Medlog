<?php
require 'connection.php';

try {
    // Insert stock (incoming supply)
    $stmt = $conn->prepare("
        INSERT INTO stocks (med_id, quantity, expiration_date)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $_POST['med_id'],
        $_POST['quantity'],
        $_POST['expiration_date'] ?: null
    ]);

    // Update total quantity in medicines table
    $update = $conn->prepare("
        UPDATE medicines
        SET total_quantity = total_quantity + ?
        WHERE med_id = ?
    ");

    $update->execute([
        $_POST['quantity'],
        $_POST['med_id']
    ]);

    header("Location: ../stocks.php");
    exit;

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}