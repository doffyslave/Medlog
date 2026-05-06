<?php
session_start();
require 'Database/connection.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$visit_id = $_GET['id'];
$user = $_SESSION['user'];

$stmt = $conn->prepare("
    SELECT 
        visits.*, 
        users.name,
        GROUP_CONCAT(CONCAT(medicines.medicine_name, ' (', treatments.quantity, ')') SEPARATOR ', ') AS medicines_used
    FROM visits
    JOIN users ON visits.user_id = users.user_id
    LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
    LEFT JOIN medicines ON treatments.med_id = medicines.med_id
    WHERE visits.visit_id = ?
    GROUP BY visits.visit_id
");
$stmt->execute([$visit_id]);
$visit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visit) {
    die("Visit not found.");
}

if ($user['role'] === 'student' && $visit['user_id'] != $user['user_id']) {
    die("Unauthorized access.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Medical Certificate</title>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 40px;
    color: #000;
}

.container {
    max-width: 750px;
    margin: auto;
    border: 2px solid #000;
    padding: 30px;
}

/* 🔥 HEADER */
.header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* 🔥 LOGO STYLE */
.logo img {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

/* fallback if no logo */
.logo-placeholder {
    width: 80px;
    height: 80px;
    border: 1px dashed #aaa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #888;
}

.title {
    text-align: center;
    flex: 1;
}

.title h2 {
    margin: 0;
}

.subtitle {
    font-size: 14px;
}

.divider {
    margin: 20px 0;
    border-top: 1px solid #000;
}

.field {
    margin-bottom: 12px;
}

.label {
    font-weight: bold;
}

.notice {
    margin-top: 25px;
    line-height: 1.6;
    text-align: justify;
}

.signature {
    margin-top: 60px;
    text-align: right;
}

.print-btn {
    margin-top: 20px;
}

@media print {
    .print-btn {
        display: none;
    }
}
</style>
</head>

<body onload="window.print()">

<div class="container">

<div class="header">

    <!-- 🔥 LEFT LOGO (STI) -->
    <div class="logo">
        <img src="Images/sti-logo.png" alt="STI Logo"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="logo-placeholder" style="display:none;">STI LOGO</div>
    </div>

    <!-- 🔥 TITLE -->
    <div class="title">
        <h2>STI College Davao Clinic</h2>
        <div class="subtitle">Official Visit Certification</div>
    </div>

    <!-- 🔥 RIGHT LOGO (YOUR LOGO) -->
    <div class="logo">
        <img src="Images/medlog-logo.png" alt="MedLog Logo"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="logo-placeholder" style="display:none;">MEDLOG</div>
    </div>

</div>

<div class="divider"></div>

<div class="field">
    <span class="label">Patient Name:</span>
    <?= htmlspecialchars($visit['name']) ?>
</div>

<div class="field">
    <span class="label">Date of Visit:</span>
    <?= date("F d, Y h:i A", strtotime($visit['visit_date'])) ?>
</div>

<div class="field">
    <span class="label">Complaint:</span>
    <?= htmlspecialchars($visit['complaint']) ?>
</div>

<div class="field">
    <span class="label">Treatment Provided:</span>
    <?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?>
</div>

<?php if (!empty($visit['notes'])): ?>
<div class="field">
    <span class="label">Remarks:</span>
    <?= htmlspecialchars($visit['notes']) ?>
</div>
<?php endif; ?>

<div class="notice">
    This is to certify that the above-named individual visited the STI College Davao Clinic on the date and time indicated above for medical consultation and appropriate care.

    This document is issued upon request as proof of clinic visitation and may be presented as a valid excuse for absence or late attendance, subject to institutional policies and verification if necessary.
</div>

<div class="signature">
    ___________________________<br>
    <?= htmlspecialchars($visit['recorded_by']) ?><br>
    Clinic Staff / Nurse
</div>

<button class="print-btn" onclick="window.print()">🖨 Print Again</button>

</div>

</body>
</html>