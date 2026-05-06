<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['user_id'];

require 'Database/connection.php';


// ================= STATUS =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $role === 'admin') {
    $clinic = $_POST['clinic_status'];
    $nurse = $_POST['nurse_status'];

    $stmt = $conn->prepare("UPDATE clinic_status SET clinic_status=?, nurse_status=? WHERE id=1");
    $stmt->execute([$clinic, $nurse]);
}

$statusData = $conn->query("SELECT * FROM clinic_status WHERE id=1")->fetch();
$clinicStatus = $statusData['clinic_status'] ?? 'Open';
$nurseStatus = $statusData['nurse_status'] ?? 'Available';


// ================= SAFE FUNCTION =================
function getSingle($conn, $query) {
    try {
        $result = $conn->query($query);
        return $result ? ($result->fetch()['total'] ?? 0) : 0;
    } catch (Exception $e) {
        return 0;
    }
}


// ================= ADMIN KPI =================
$totalPatients = getSingle($conn, "SELECT COUNT(*) as total FROM users WHERE role='student'");
$totalVisits = getSingle($conn, "SELECT COUNT(*) as total FROM visits WHERE MONTH(visit_date)=MONTH(CURRENT_DATE())");
$totalRecords = getSingle($conn, "SELECT COUNT(*) as total FROM visits");
$totalLowStock = getSingle($conn, "SELECT COUNT(*) as total FROM medicines WHERE total_quantity <= 10");


// ================= STUDENT DATA =================
$myVisits = [];
$myTotalVisits = 0;
$lastVisit = null;

if ($role === 'student') {
    try {
        $stmt = $conn->prepare("
            SELECT complaint, visit_date 
            FROM visits 
            WHERE user_id = ? 
            ORDER BY visit_date DESC 
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $myVisits = $stmt->fetchAll();

        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM visits WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $myTotalVisits = $stmt->fetch()['total'] ?? 0;

        $stmt = $conn->prepare("
            SELECT visit_date 
            FROM visits 
            WHERE user_id = ? 
            ORDER BY visit_date DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $lastVisit = $stmt->fetch()['visit_date'] ?? null;

    } catch (Exception $e) {}
}


// ================= ADMIN DATA =================
$recentVisits = [];
$lowStockItems = [];

if ($role === 'admin') {
    try {
        $q = $conn->query("
            SELECT v.complaint, v.visit_date, u.name 
            FROM visits v
            JOIN users u ON v.user_id = u.user_id
            ORDER BY v.visit_date DESC
            LIMIT 5
        ");
        $recentVisits = $q ? $q->fetchAll() : [];
    } catch (Exception $e) {}

    try {
        $q = $conn->query("
            SELECT medicine_name, total_quantity 
            FROM medicines 
            ORDER BY total_quantity ASC
            LIMIT 5
        ");
        $lowStockItems = $q ? $q->fetchAll() : [];
    } catch (Exception $e) {}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MedLog Dashboard</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link rel="stylesheet" href="/Medlog/Css/dashboard.css">
<link rel="stylesheet" href="/Medlog/Css/layout.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<div class="dashboard">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

<?php include 'includes/header.php'; ?>

<section class="content">

<h1>Dashboard</h1>
<p class="subtitle">Welcome back! Here's your clinic overview.</p>

<!-- 🔷 SHARED STATUS -->
<div class="grid-3">

    <div class="card status">
        <i class="fas fa-clinic-medical icon"></i>
        <div>
            <p>Clinic Status</p>
            <span class="badge <?= strtolower($clinicStatus) ?>">
                <?= $clinicStatus ?>
            </span>
        </div>
    </div>

    <div class="card status">
        <i class="fas fa-user-nurse icon"></i>
        <div>
            <p>Nurse Status</p>
            <span class="badge <?= strtolower($nurseStatus) ?>">
                <?= $nurseStatus ?>
            </span>
        </div>
    </div>

    <?php if ($role === 'admin'): ?>
    <div class="card">
        <h3>Clinic Control</h3>

        <form method="POST">
            <select name="nurse_status">
                <option <?= $nurseStatus=='Available'?'selected':'' ?>>Available</option>
                <option <?= $nurseStatus=='Lunch'?'selected':'' ?>>Lunch</option>
                <option <?= $nurseStatus=='Offline'?'selected':'' ?>>Offline</option>
            </select>

            <select name="clinic_status">
                <option <?= $clinicStatus=='Open'?'selected':'' ?>>Open</option>
                <option <?= $clinicStatus=='Closed'?'selected':'' ?>>Closed</option>
            </select>

            <button type="submit" name="update_status" class="btn-primary">
                Save
            </button>
        </form>
    </div>
    <?php endif; ?>

</div>

<!-- 🔴 ADMIN DASHBOARD -->
<?php if ($role === 'admin'): ?>

<div class="grid-4">

    <div class="card stat">
        <i class="fas fa-users"></i>
        <div>
            <h2><?= $totalPatients ?></h2>
            <p>Patients</p>
        </div>
    </div>

    <div class="card stat">
        <i class="fas fa-file-medical"></i>
        <div>
            <h2><?= $totalRecords ?></h2>
            <p>Records</p>
        </div>
    </div>

    <div class="card stat">
        <i class="fas fa-heartbeat"></i>
        <div>
            <h2><?= $totalVisits ?></h2>
            <p>Visits</p>
        </div>
    </div>

    <div class="card stat danger">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <h2><?= $totalLowStock ?></h2>
            <p>Low Stock</p>
        </div>
    </div>

</div>

<div class="grid-2">

<div class="card">
<h3>Recent Visits</h3>

<?php foreach ($recentVisits as $visit): ?>
<div class="list-item">
    <div>
        <strong><?= $visit['name'] ?></strong>
        <p><?= $visit['complaint'] ?></p>
    </div>

    <div class="visit-meta">
        <div><?= date("M d, Y", strtotime($visit['visit_date'])) ?></div>
        <div class="visit-time">
            <?= date("h:i A", strtotime($visit['visit_date'])) ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

</div>

<div class="card">
<h3>Low Stock</h3>

<?php foreach ($lowStockItems as $item): 
$qty = (int) $item['total_quantity'];
if ($qty > 10) continue;
?>

<div class="list-item danger-bg">
    <div>
        <strong><?= $item['medicine_name'] ?></strong>
    </div>
    <div><?= $qty ?></div>
</div>

<?php endforeach; ?>

</div>

</div>

<?php endif; ?>


<!-- 🔵 STUDENT DASHBOARD -->
<?php if ($role === 'student'): ?>

<div class="grid-2">

<div class="card stat">
    <i class="fas fa-heartbeat"></i>
    <div>
        <h2><?= $myTotalVisits ?></h2>
        <p>My Visits</p>
    </div>
</div>

<div class="card stat">
    <i class="fas fa-calendar-check"></i>
    <div>
        <h2><?= $lastVisit ? date("M d", strtotime($lastVisit)) : 'N/A' ?></h2>
        <p>Last Visit</p>
    </div>
</div>

</div>

<div class="card">
<h3>My Recent Visits</h3>

<?php if ($myVisits): ?>
<?php foreach ($myVisits as $visit): ?>
<div class="list-item">
    <div>
        <p><?= $visit['complaint'] ?></p>
    </div>
    <div class="visit-meta">
        <?= date("M d, Y h:i A", strtotime($visit['visit_date'])) ?>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
<p>No visit records yet.</p>
<?php endif; ?>

</div>

<?php endif; ?>

</section>
</main>
</div>

</body>
</html>