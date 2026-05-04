<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit();
}

require 'Database/connection.php';

/* =======================
   DATE FILTER
======================= */
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-t');

/* =======================
   KPI DATA (FILTERED)
======================= */

// Visits
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM visits
    WHERE visit_date BETWEEN ? AND ?
");
$stmt->execute([$start, $end]);
$totalVisits = $stmt->fetchColumn();

// Patients
$totalPatients = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Low stock (aggregated)
$totalLowStock = $conn->query("
    SELECT COUNT(*) FROM (
        SELECT SUM(quantity) as total_quantity
        FROM stocks
        GROUP BY med_id
        HAVING total_quantity <= 10
    ) as lowstock
")->fetchColumn();

/* =======================
   CHART DATA
======================= */

// Visits over time
$stmt = $conn->prepare("
    SELECT DATE(visit_date) as date, COUNT(*) as total
    FROM visits
    WHERE visit_date BETWEEN ? AND ?
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
");
$stmt->execute([$start, $end]);
$visitsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Illness
$stmt = $conn->prepare("
    SELECT complaint, COUNT(*) as total
    FROM visits
    WHERE visit_date BETWEEN ? AND ?
    GROUP BY complaint
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$start, $end]);
$illnessData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Most used medicines
$topMeds = $conn->query("
    SELECT m.medicine_name, SUM(t.quantity) as total_used
    FROM treatments t
    JOIN medicines m ON t.med_id = m.med_id
    GROUP BY t.med_id
    ORDER BY total_used DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   TABLE DATA
======================= */

// Recent visits
$stmt = $conn->prepare("
    SELECT v.visit_date, v.complaint, u.name
    FROM visits v
    JOIN users u ON v.user_id = u.user_id
    WHERE v.visit_date BETWEEN ? AND ?
    ORDER BY v.visit_date DESC
    LIMIT 5
");
$stmt->execute([$start, $end]);
$recentVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock list (correct)
try {
    $lowStockItems = $conn->query("
        SELECT medicine_name, total_quantity 
        FROM medicines 
        ORDER BY total_quantity ASC
        LIMIT 5
    ");
    $lowStockItems = $lowStockItems ? $lowStockItems->fetchAll() : [];
} catch (Exception $e) {
    $lowStockItems = [];
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Reports</title>

<link rel="stylesheet" href="Css/layout.css">
<link rel="stylesheet" href="Css/dashboard.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.section { margin-top: 25px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.full { grid-column: span 2; }
.filter-bar { margin-bottom: 20px; display:flex; gap:10px; }
.badge-low { color:#fff; background:#ef4444; padding:3px 8px; border-radius:5px; }
.badge-ok { color:#fff; background:#22c55e; padding:3px 8px; border-radius:5px; }
</style>

</head>

<body>

<div class="dashboard">
<?php include 'includes/sidebar.php'; ?>

<main class="main-content">
<?php include 'includes/header.php'; ?>

<section class="content">

<h1>Reports</h1>
<p class="subtitle">Analytics & trends (filtered)</p>

<!-- FILTER -->
<form method="GET" class="filter-bar">
    <input type="date" name="start" value="<?= $start ?>">
    <input type="date" name="end" value="<?= $end ?>">
    <button type="submit">Apply</button>
</form>

<!-- KPI -->
<div class="kpi-row">
    <div class="card kpi"><h2><?= $totalPatients ?></h2><p>Total Patients</p></div>
    <div class="card kpi"><h2><?= $totalVisits ?></h2><p>Visits (Filtered)</p></div>
    <div class="card kpi danger"><h2><?= $totalLowStock ?></h2><p>Low Stock</p></div>
</div>

<!-- CHARTS -->
<div class="section grid-2">

    <div class="card chart">
        <h3>Visits Over Time</h3>
        <canvas id="visitsChart"></canvas>
    </div>

    <div class="card chart">
        <h3>Top Illnesses</h3>
        <canvas id="illnessChart"></canvas>
    </div>

    <div class="card chart full">
        <h3>Most Used Medicines</h3>
        <canvas id="medChart"></canvas>
    </div>

</div>

<!-- TABLES -->
<div class="section grid-2">

    <div class="card">
        <h3>Recent Visits</h3>
        <table>
            <tr><th>Name</th><th>Date</th><th>Complaint</th></tr>
            <?php foreach($recentVisits as $row): ?>
            <tr>
                <td><?= $row['name'] ?></td>
                <td><?= $row['visit_date'] ?></td>
                <td><?= $row['complaint'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h3>Medicine Inventory Status</h3>
        <table>
            <tr><th>Medicine</th><th>Qty</th><th>Status</th></tr>
                <?php if (empty($lowStockItems)): ?>
                    <li>No low stock items</li>
                <?php else: ?>
                    <?php foreach ($lowStockItems as $item): ?>
                    <li>
                        <strong><?= htmlspecialchars($item['medicine_name']) ?></strong>
                        <span><?= $item['total_quantity'] ?> left</span>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
        </table>
    </div>

</div>

</section>
</main>
</div>

<!-- CHARTS -->
<script>

// Visits chart
new Chart(document.getElementById('visitsChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($visitsData, 'date')) ?>,
        datasets: [{
            label: 'Visits',
            data: <?= json_encode(array_column($visitsData, 'total')) ?>,
            borderColor: '#2563eb'
        }]
    }
});

// Illness chart
new Chart(document.getElementById('illnessChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($illnessData, 'complaint')) ?>,
        datasets: [{
            label: 'Cases',
            data: <?= json_encode(array_column($illnessData, 'total')) ?>,
            backgroundColor: '#ef4444'
        }]
    }
});

// Medicine usage chart
new Chart(document.getElementById('medChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($topMeds, 'medicine_name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($topMeds, 'total_used')) ?>
        }]
    }
});

</script>

</body>
</html>