<?php
require 'connection.php';

if (isset($_GET['id'])) {

    $visit_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM visits WHERE visit_id = ?");
    $stmt->execute([$visit_id]);

    header("Location: ../visits.php");
    exit();
}
?>