<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    session_start();

    $user_id = trim((string) ($_POST['user_id'] ?? ''));
    $complaint = trim((string) ($_POST['complaint'] ?? ''));

    $notes = isset($_POST['notes']) && trim($_POST['notes']) !== ''
        ? trim($_POST['notes'])
        : null;

    $recorded_by = $_SESSION['user']['name'] ?? '';

    $medRaw = isset($_POST['med_id']) ? trim((string) $_POST['med_id']) : '';
    $hasMedicine = $medRaw !== '';
    $med_id = $hasMedicine ? (int) $medRaw : 0;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;

    if ($user_id === '' || $complaint === '') {
        echo "Error: Patient and complaint are required.";
        exit();
    }

    try {
        $conn->beginTransaction();

        if ($hasMedicine) {
            if ($med_id < 1 || $quantity < 1) {
                throw new Exception("Select a valid medicine and quantity.");
            }

            $stmt = $conn->prepare("
                SELECT total_quantity FROM medicines WHERE med_id = ?
            ");
            $stmt->execute([$med_id]);
            $medicine = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$medicine || (int) $medicine['total_quantity'] < $quantity) {
                throw new Exception("Not enough stock!");
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO visits (user_id, visit_date, complaint, recorded_by, notes)
            VALUES (?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([$user_id, $complaint, $recorded_by, $notes]);

        $visit_id = $conn->lastInsertId();

        if ($hasMedicine) {
            $stmt = $conn->prepare("
                INSERT INTO treatments (visit_id, med_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$visit_id, $med_id, $quantity]);

            $stmt = $conn->prepare("
                UPDATE medicines
                SET total_quantity = total_quantity - ?
                WHERE med_id = ?
            ");
            $stmt->execute([$quantity, $med_id]);
        }

        $conn->commit();

        header("Location: ../visits.php");
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
    }
}