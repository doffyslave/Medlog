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
   KPI DATA
======================= */
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM visits
    WHERE visit_date BETWEEN ? AND ?
");
$stmt->execute([$start, $end]);
$totalVisits = $stmt->fetchColumn();

$totalPatients = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

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
$stmt = $conn->prepare("
    SELECT DATE(visit_date) as date, COUNT(*) as total
    FROM visits
    WHERE visit_date BETWEEN ? AND ?
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
");
$stmt->execute([$start, $end]);
$visitsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

$lowStockItems = $conn->query("
    SELECT medicine_name, total_quantity 
    FROM medicines 
    ORDER BY total_quantity ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Reports</title>

<link rel="stylesheet" href="/Medlog/Css/layout.css">
<link rel="stylesheet" href="/Medlog/Css/dashboard.css">
<link rel="stylesheet" href="/Medlog/Css/reports.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
<div class="grid-3">
    <div class="card stat">
        <i class="fas fa-users"></i>
        <div>
            <h2><?= $totalPatients ?></h2>
            <p>Total Patients</p>
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

<!-- CHARTS -->
<div class="grid-2">

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

<!-- TABLES / LISTS -->
<div class="grid-2">

    <!-- RECENT VISITS -->
    <div class="card">
        <h3>Recent Visits</h3>

        <?php foreach ($recentVisits as $row): ?>
        <div class="list-item">
            <div>
                <strong><?= $row['name'] ?></strong>
                <p><?= $row['complaint'] ?></p>
            </div>

            <span class="visit-meta">
                <?= date("M d, Y", strtotime($row['visit_date'])) ?><br>
                <?= date("h:i A", strtotime($row['visit_date'])) ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- LOW STOCK -->
    <div class="card">
        <h3>Medicine Inventory</h3>

        <?php if (empty($lowStockItems)): ?>
            <div class="list-item">No low stock items</div>
        <?php else: ?>

            <?php foreach ($lowStockItems as $item): ?>
                <?php $qty = (int)$item['total_quantity']; ?>

                <div class="list-item danger-bg">
                    <div>
                        <strong><?= htmlspecialchars($item['medicine_name']) ?></strong>
                    </div>

                    <div>
                        <?= $qty ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</div>

</section>
</main>
</div>

<!-- CHARTS -->
<script>
new Chart(document.getElementById('visitsChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($visitsData, 'date')) ?>,
        datasets: [{
            label: 'Visits',
            data: <?= json_encode(array_column($visitsData, 'total')) ?>,
            borderColor: '#2563eb',
            tension: 0.3,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

new Chart(document.getElementById('illnessChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($illnessData, 'complaint')) ?>,
        datasets: [{
            label: 'Cases',
            data: <?= json_encode(array_column($illnessData, 'total')) ?>,
            backgroundColor: '#ef4444'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});

new Chart(document.getElementById('medChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($topMeds, 'medicine_name')) ?>,
        datasets: [{
            label: 'Usage Count',
            data: <?= json_encode(array_column($topMeds, 'total_used')) ?>,
            backgroundColor: '#3b82f6'
        }]
    },
    options: {
        indexAxis: 'y', // 🔥 makes it horizontal
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>