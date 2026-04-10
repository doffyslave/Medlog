<?php
require 'database/connection.php';

$stmt = $pdo->prepare("DELETE FROM inventory WHERE inventory_id=?");
$stmt->execute([$_POST['id']]);

header("Location: inventory.php");