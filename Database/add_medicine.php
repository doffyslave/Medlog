<?php
require 'connection.php';

$stmt = $conn->prepare("
    INSERT INTO medicines (medicine_name, total_quantity)
    VALUES (?, 0)
");

$stmt->execute([$_POST['medicine_name']]);

header("Location: ../medicines.php");
exit;