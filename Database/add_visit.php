<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_POST['user_id'];
    $complaint = $_POST['complaint'];
    $notes = $_POST['notes'] ?? null;
    session_start();
    $recorded_by = $_SESSION['user']['name'];

    $med_id = $_POST['med_id'];
    $quantity = $_POST['quantity'];

    try {
        $conn->beginTransaction();

        // CHECK STOCK FIRST
        $stmt = $conn->prepare("
            SELECT total_quantity FROM medicines WHERE med_id = ?
        ");
        $stmt->execute([$med_id]);
        $medicine = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$medicine || $medicine['total_quantity'] < $quantity) {
            throw new Exception("Not enough stock!");
        }

        // INSERT INTO visits
        $stmt = $conn->prepare("
        INSERT INTO visits (user_id, visit_date, complaint, recorded_by, notes)
        VALUES (?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([$user_id, $complaint, $recorded_by, $notes]);

        $visit_id = $conn->lastInsertId();

        // INSERT INTO treatments
        $stmt = $conn->prepare("
            INSERT INTO treatments (visit_id, med_id, quantity)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$visit_id, $med_id, $quantity]);

        // DEDUCT STOCK
        $stmt = $conn->prepare("
            UPDATE medicines 
            SET total_quantity = total_quantity - ? 
            WHERE med_id = ?
        ");
        $stmt->execute([$quantity, $med_id]);

        $conn->commit();

        header("Location: ../visits.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}