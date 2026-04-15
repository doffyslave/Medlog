<?php
require 'connection.php';

$stmt = $conn->prepare("
    UPDATE medicines SET medicine_name=? WHERE med_id=?
");

$stmt->execute([
    $_POST['medicine_name'],
    $_POST['id']
]);

header("Location: ../medicines.php");
exit;