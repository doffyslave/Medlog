<?php
session_start();
require 'Database/connection.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];

$stmt = $conn->prepare("
    SELECT 
        visits.*, 
        GROUP_CONCAT(CONCAT(medicines.medicine_name, ' (', treatments.quantity, ')') SEPARATOR ', ') AS medicines_used
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

<link rel="stylesheet" href="Css/layout.css">
<link rel="stylesheet" href="Css/visits.css">

<style>
.no-data {
    text-align: center;
    margin-top: 20px;
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

<!-- 🔥 CARD LIST -->
<div class="visit-grid">

<?php if (!empty($visits)): ?>
<?php foreach ($visits as $visit): ?>

<div class="visit-card" onclick='openViewModal(<?= json_encode($visit) ?>)'>

    <div class="visit-header">
        <span>Visit Record</span>
        <span class="visit-meta">
            <?= date("M d, Y h:i A", strtotime($visit['visit_date'])) ?>
        </span>
    </div>

    <div class="visit-body">
        <p><strong>Complaint:</strong> <?= htmlspecialchars($visit['complaint']) ?></p>

        <p><strong>Treatment:</strong> 
            <?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?>
        </p>

        <p><strong>Notes:</strong> 
            <?= htmlspecialchars($visit['notes'] ?? 'None') ?>
        </p>

        <span class="badge">
            <?= htmlspecialchars($visit['recorded_by']) ?>
        </span>
    </div>

</div>

<?php endforeach; ?>
<?php else: ?>
<p class="no-data">No visit records found.</p>
<?php endif; ?>

</div>

</section>
</main>
</div>

<!-- 🔥 VIEW MODAL -->
<div id="viewModal" class="modal">
<div class="modal-content">
<span class="closeView">&times;</span>
<h2>Visit Details</h2>
<div id="viewContent"></div>
</div>
</div>

<script>
const viewModal = document.getElementById("viewModal");

document.querySelector(".closeView").onclick = () => {
    viewModal.classList.remove("show");
};

window.onclick = (e) => {
    if (e.target === viewModal) viewModal.classList.remove("show");
};

function openViewModal(data) {
    document.getElementById("viewContent").innerHTML = `
        <p><strong>Date:</strong> ${data.visit_date}</p>
        <p><strong>Recorded By:</strong> ${data.recorded_by}</p>
        <hr>
        <p><strong>Complaint:</strong> ${data.complaint}</p>
        <p><strong>Treatment:</strong> ${data.medicines_used || 'None'}</p>
        <p><strong>Notes:</strong> ${data.notes || 'None'}</p>
    `;
    viewModal.classList.add("show");
}
</script>

</body>
</html>