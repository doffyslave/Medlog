<?php
session_start();
require 'Database/connection.php';

// Check login
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];

// ✅ ONLY GET LOGGED-IN USER VISITS
$stmt = $conn->prepare("
    SELECT 
        visits.visit_id,
        visits.visit_date,
        visits.complaint,
        visits.recorded_by,
        GROUP_CONCAT(medicines.medicine_name SEPARATOR ', ') AS medicines_used
    FROM visits
    LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
    LEFT JOIN medicines ON treatments.med_id = medicines.med_id
    WHERE visits.user_id = ?
    GROUP BY visits.visit_id
    ORDER BY visits.visit_date DESC
");
$stmt->execute([$user_id]);

$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Visits | MedLog</title>

<link rel="stylesheet" href="CSS/layout.css">
<link rel="stylesheet" href="CSS/visits.css">

<style>
    .no-data {
        text-align: center;
        padding: 20px;
        color: gray;
    }
</style>
</head>

<body>

<div class="dashboard">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

<?php include 'includes/header.php'; ?>

<section class="content">

<div class="header-row">
    <div>
        <h1>My Visits</h1>
        <p>Your clinic visit history</p>
    </div>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Visit Date</th>
<th>Complaint</th>
<th>Medicines Given</th>
<th>Recorded By</th>
</tr>
</thead>

<tbody>

<?php if (!empty($visits)): ?>
    <?php foreach ($visits as $visit): ?>
    <tr>
        <td><?= htmlspecialchars($visit['visit_date']) ?></td>
        <td><?= htmlspecialchars($visit['complaint']) ?></td>
        <td><?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?></td>
        <td><?= htmlspecialchars($visit['recorded_by']) ?></td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="4" class="no-data">No visit records found.</td>
    </tr>
<?php endif; ?>

</tbody>
</table>
</div>

</section>
</main>
</div>

</body>
</html>