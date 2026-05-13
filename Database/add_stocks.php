<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../stocks.php');
    exit();
}

$med_id = (int) ($_POST['med_id'] ?? 0);
$rawQty = (int) ($_POST['quantity'] ?? 0);
$type = isset($_POST['transaction_type']) ? trim((string) $_POST['transaction_type']) : 'stock_in';

if ($med_id < 1 || $rawQty < 1) {
    echo 'Error: Select a medicine and enter a quantity of at least 1.';
    exit();
}

if ($type === 'adjustment') {
    $quantity = -abs($rawQty);
} else {
    $quantity = abs($rawQty);
}

$expiration = isset($_POST['expiration_date']) && trim((string) $_POST['expiration_date']) !== ''
    ? trim((string) $_POST['expiration_date'])
    : null;

// Optional notes/reason: UI only unless stocks table gains a notes column.
// $_POST['movement_notes'] intentionally not persisted.

try {
    $colsStmt = $conn->query('SHOW COLUMNS FROM stocks');
    $columnNames = $colsStmt ? $colsStmt->fetchAll(PDO::FETCH_COLUMN) : [];
    $hasCreatedAt = is_array($columnNames) && in_array('created_at', $columnNames, true);

    $conn->beginTransaction();

    $stmt = $conn->prepare('SELECT total_quantity FROM medicines WHERE med_id = ?');
    $stmt->execute([$med_id]);
    $medRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medRow) {
        throw new Exception('Medicine not found.');
    }

    $currentTotal = (int) $medRow['total_quantity'];
    $projected = $currentTotal + $quantity;
    if ($projected < 0) {
        throw new Exception('Adjustment exceeds available on-hand quantity.');
    }

    if ($hasCreatedAt) {
        $stmt = $conn->prepare('
            INSERT INTO stocks (med_id, quantity, expiration_date, created_at)
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$med_id, $quantity, $expiration]);
    } else {
        $stmt = $conn->prepare('
            INSERT INTO stocks (med_id, quantity, expiration_date)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$med_id, $quantity, $expiration]);
    }

    $stmt = $conn->prepare('
        UPDATE medicines
        SET total_quantity = total_quantity + ?
        WHERE med_id = ?
    ');
    $stmt->execute([$quantity, $med_id]);

    $conn->commit();

    header('Location: ../stocks.php');
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
