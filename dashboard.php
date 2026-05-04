<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit();
}

$user = $_SESSION['user'];

require 'Database/connection.php';


// ================= STATUS =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
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


// ================= KPI =================
$totalPatients = getSingle($conn, "SELECT COUNT(*) as total FROM users WHERE role='student'");
$totalVisits = getSingle($conn, "SELECT COUNT(*) as total FROM visits WHERE MONTH(visit_date)=MONTH(CURRENT_DATE())");
$totalRecords = getSingle($conn, "SELECT COUNT(*) as total FROM visits");
$totalLowStock = getSingle($conn, "SELECT COUNT(*) as total FROM medicines WHERE total_quantity <= 10");


// ================= RECENT VISITS =================
$recentVisits = [];
try {
    $q = $conn->query("
        SELECT 
            v.complaint, 
            v.visit_date,
            u.name 
        FROM visits v
        JOIN users u ON v.user_id = u.user_id
        ORDER BY v.visit_date DESC
        LIMIT 5
    ");
    $recentVisits = $q ? $q->fetchAll() : [];
} catch (Exception $e) {}


// ================= LOW STOCK =================
$lowStockItems = [];
try {
    $q = $conn->query("
        SELECT medicine_name, total_quantity 
        FROM medicines 
        ORDER BY total_quantity ASC
        LIMIT 5
    ");
    $lowStockItems = $q ? $q->fetchAll() : [];
} catch (Exception $e) {}


// ================= CHART DATA =================

// Monthly visits
$months = [];
$totals = [];

$q = $conn->query("
    SELECT MONTH(visit_date) as month, COUNT(*) as total
    FROM visits
    GROUP BY MONTH(visit_date)
");

while ($row = $q->fetch()) {
    $months[] = $row['month'];
    $totals[] = $row['total'];
}


// Illness distribution
$illnessLabels = [];
$illnessCounts = [];

$q = $conn->query("
    SELECT complaint, COUNT(*) as total
    FROM visits
    GROUP BY complaint
    LIMIT 5
");

while ($row = $q->fetch()) {
    $illnessLabels[] = $row['complaint'];
    $illnessCounts[] = $row['total'];
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

</div>

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

<?php foreach ($lowStockItems as $item): ?>

<?php
$qty = (int) $item['total_quantity'];

if ($qty > 10) {
    continue; // skip non-low-stock items
}

if ($qty <= 0) {
    $status = 'Out of Stock';
    $class = 'badge-low';
} else {
    $status = 'Low Stock';
    $class = 'badge-low';
}
?>

<div class="list-item danger-bg">
    <div>
        <strong><?= $item['medicine_name'] ?></strong>
        <span class="<?= $class ?>"></span>
    </div>

    <div>
        <?= $qty ?>
    </div>
</div>

<?php endforeach; ?>

</div>

</div>

</section>
</main>
</div>


<!-- 🔥 CHART JS -->
<script>
new Chart(document.getElementById('visitsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Visits',
            data: <?= json_encode($totals) ?>
        }]
    }
});

new Chart(document.getElementById('illnessChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($illnessLabels) ?>,
        datasets: [{
            data: <?= json_encode($illnessCounts) ?>
        }]
    }
});
</script>

</body>
</html>