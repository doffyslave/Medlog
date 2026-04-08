<?php
require 'Database/connection.php';

// get visit id
if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$visit_id = $_GET['id'];

// fetch visit data
$stmt = $conn->prepare("
    SELECT visits.*, users.name 
    FROM visits
    JOIN users ON visits.user_id = users.user_id
    WHERE visits.visit_id = ?
");
$stmt->execute([$visit_id]);
$visit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visit) {
    die("Visit not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Slip</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            color: #000;
        }

        .container {
            max-width: 700px;
            margin: auto;
            border: 1px solid #000;
            padding: 30px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .field {
            margin-bottom: 15px;
        }

        .label {
            font-weight: bold;
        }

        .signature {
            margin-top: 50px;
            text-align: right;
        }

        .print-btn {
            margin-top: 20px;
            display: block;
        }

        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <h2>STI College Davao Clinic</h2>

    <div class="field">
        <span class="label">Patient Name:</span>
        <?= htmlspecialchars($visit['name']) ?>
    </div>

    <div class="field">
        <span class="label">Date:</span>
        <?= date("F d, Y h:i A", strtotime($visit['visit_date'])) ?>
    </div>

    <div class="field">
        <span class="label">Complaint:</span>
        <?= htmlspecialchars($visit['complaint']) ?>
    </div>

    <div class="field">
        <span class="label">Treatment:</span>
        <?= htmlspecialchars($visit['treatment']) ?>
    </div>

    <div class="field">
        <span class="label">Recommendation:</span><br>
        The student is advised to rest and is excused from class.
    </div>

    <div class="signature">
        ___________________________<br>
        <?= htmlspecialchars($visit['recorded_by']) ?>
    </div>

    <button class="print-btn" onclick="window.print()">🖨 Print</button>

</div>

</body>
</html>