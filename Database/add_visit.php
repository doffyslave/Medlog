<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_POST['user_id'];
    $complaint = $_POST['complaint'];
    $treatment = $_POST['treatment'];
    $recorded_by = $_POST['recorded_by'];

    $stmt = $conn->prepare("
        INSERT INTO visits (user_id, visit_date, complaint, treatment, recorded_by)
        VALUES (?, NOW(), ?, ?, ?)
    ");

    $stmt->execute([$user_id, $complaint, $treatment, $recorded_by]);

    header("Location: ../visits.php");
    exit();
}