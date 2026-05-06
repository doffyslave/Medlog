<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require 'Database/connection.php';

$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT 
            visits.*, 
            users.name,
            GROUP_CONCAT(CONCAT(medicines.medicine_name, ' (', treatments.quantity, ')') SEPARATOR ', ') AS medicines_used
        FROM visits
        JOIN users ON visits.user_id = users.user_id
        LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
        LEFT JOIN medicines ON treatments.med_id = medicines.med_id
        WHERE visits.user_id = ?
        GROUP BY visits.visit_id
        ORDER BY visit_date DESC
    ");
    $stmt->execute([$user_id]);

    $userStmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $userStmt->execute([$user_id]);
    $selectedUser = $userStmt->fetch(PDO::FETCH_ASSOC);

} else {
    $stmt = $conn->prepare("
        SELECT 
            visits.*, 
            users.name,
            GROUP_CONCAT(CONCAT(medicines.medicine_name, ' (', treatments.quantity, ')') SEPARATOR ', ') AS medicines_used
        FROM visits
        JOIN users ON visits.user_id = users.user_id
        LEFT JOIN treatments ON visits.visit_id = treatments.visit_id
        LEFT JOIN medicines ON treatments.med_id = medicines.med_id
        GROUP BY visits.visit_id
        ORDER BY visit_date DESC
    ");
    $stmt->execute();
}

$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Visits - MedLog</title>

<link rel="stylesheet" href="Css/layout.css">
<link rel="stylesheet" href="Css/visits.css">

<style>
.visit-grid {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.visit-card {
    background: #fff;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    cursor: pointer;
    transition: 0.2s;
}

.visit-card:hover {
    transform: translateY(-2px);
}

.visit-header {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
}

.visit-meta {
    font-size: 13px;
    color: #64748b;
}

.visit-body {
    margin-top: 10px;
}

.badge {
    background: #e2e8f0;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
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
        <h1>Visits</h1>
        <p>Manage clinic visit records</p>

        <?php if ($user_id && $selectedUser): ?>
            <p style="color:#2563eb;">
                Viewing visits for: <strong><?= htmlspecialchars($selectedUser['name']) ?></strong>
            </p>
            <a href="visits.php" class="filter-btn">← Back</a>
        <?php endif; ?>
    </div>

    <button id="openVisitModal" class="add-btn">+ Add Visit</button>
</div>

<!-- Card vis -->
<div class="visit-grid">

<?php if (!empty($visits)): ?>
<?php foreach ($visits as $visit): ?>

<div class="visit-card" onclick='openViewModal(<?= json_encode($visit) ?>)'>

    <div class="visit-header">
        <span><?= htmlspecialchars($visit['name']) ?></span>
        <span class="visit-meta">
            <?= date("M d, Y h:i A", strtotime($visit['visit_date'])) ?>
        </span>
    </div>

    <div class="visit-body">
        <p><strong>Complaint:</strong> <?= htmlspecialchars($visit['complaint']) ?></p>

        <p><strong>Treatment:</strong> 
            <?= htmlspecialchars($visit['medicines_used'] ?? 'None') ?>
        </p>

        <span class="badge">
            <?= htmlspecialchars($visit['recorded_by']) ?>
        </span>
    </div>

</div>

<?php endforeach; ?>
<?php else: ?>
<p>No visit records found.</p>
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

<!-- ADD VISIT MODAL -->
<div id="visitModal" class="modal">
<div class="modal-content">
<span class="closeVisit">&times;</span>
<h2>Add Visit</h2>

<form action="Database/add_visit.php" method="POST">

<select name="user_id" required>
<option value="">Select Patient</option>
<?php
$users = $conn->query("SELECT user_id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u):
?>
<option value="<?= $u['user_id'] ?>">
<?= htmlspecialchars($u['name']) ?>
</option>
<?php endforeach; ?>
</select>

<input type="text" name="complaint" placeholder="Complaint" required>

<textarea name="notes" placeholder="Notes (optional)..."></textarea>

<select name="med_id" required>
<option value="">Select Medicine</option>
<?php
$meds = $conn->query("SELECT med_id, medicine_name FROM medicines")->fetchAll(PDO::FETCH_ASSOC);
foreach ($meds as $m):
?>
<option value="<?= $m['med_id'] ?>">
<?= htmlspecialchars($m['medicine_name']) ?>
</option>
<?php endforeach; ?>
</select>

<input type="number" name="quantity" placeholder="Quantity" required>

<button type="submit">Save Visit</button>

</form>
</div>
</div>

<script>
const visitModal = document.getElementById("visitModal");
const viewModal = document.getElementById("viewModal");

document.getElementById("openVisitModal").onclick = () => {
    visitModal.classList.add("show");
};

document.querySelector(".closeVisit").onclick = () => {
    visitModal.classList.remove("show");
};

document.querySelector(".closeView").onclick = () => {
    viewModal.classList.remove("show");
};

window.onclick = (e) => {
    if (e.target === visitModal) visitModal.classList.remove("show");
    if (e.target === viewModal) viewModal.classList.remove("show");
};

function openViewModal(data) {
    document.getElementById("viewContent").innerHTML = `
        <p><strong>Patient:</strong> ${data.name}</p>
        <p><strong>Date:</strong> ${data.visit_date}</p>
        <p><strong>Recorded By:</strong> ${data.recorded_by}</p>
        <hr>
        <p><strong>Complaint:</strong> ${data.complaint}</p>
        <p><strong>Treatment:</strong> ${data.medicines_used || 'None'}</p>
        <p><strong>Notes:</strong> ${data.notes || 'None'}</p>

        <br>

        <a href="print_visit.php?id=${data.visit_id}" target="_blank" 
           style="display:inline-block;padding:8px 12px;background:#2563eb;color:white;border-radius:6px;text-decoration:none;">
           🖨 Print Visit
        </a>
    `;

    viewModal.classList.add("show");
}
</script>

</body>
</html>
