<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $visit_id = $_POST['visit_id'];
    $user_id = $_POST['user_id'];
    $visit_date = $_POST['visit_date'];
    $complaint = $_POST['complaint'];
    $treatment = $_POST['treatment'];
    $recorded_by = $_POST['recorded_by'];

    $stmt = $conn->prepare("
        UPDATE visits 
        SET user_id = ?, visit_date = ?, complaint = ?, treatment = ?, recorded_by = ?
        WHERE visit_id = ?
    ");

    $stmt->execute([$user_id, $visit_date, $complaint, $treatment, $recorded_by, $visit_id]);

    header("Location: ../visits.php");
    exit();
}
?>