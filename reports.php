<?php
session_start();

$user = $_SESSION['user'] ?? null;
if (!$user || (($user['role'] ?? '') !== 'admin')) {
    header('Location: dashboard.php');
    exit();
}

require 'Database/connection.php';

function reports_valid_date(?string $d): ?string
{
    return ($d !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) ? $d : null;
}

$preset = isset($_GET['preset']) ? preg_replace('/[^a-z0-9_]/', '', strtolower((string) $_GET['preset'])) : '';
if ($preset === 'this_month') {
    $start = date('Y-m-01');
    $end = date('Y-m-t');
} elseif ($preset === 'last_7') {
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-6 days'));
} elseif ($preset === 'last_30') {
    $end = date('Y-m-d');
    $start = date('Y-m-d', strtotime('-29 days'));
} elseif ($preset === 'this_year') {
    $start = date('Y-01-01');
    $end = date('Y-12-31');
} else {
    $start = reports_valid_date($_GET['start'] ?? null) ?? date('Y-m-01');
    $end = reports_valid_date($_GET['end'] ?? null) ?? date('Y-m-t');
}

if ($start > $end) {
    $tmp = $start;
    $start = $end;
    $end = $tmp;
}

$rangeLabel = date('M j, Y', strtotime($start)) . ' – ' . date('M j, Y', strtotime($end));

$patientStatsStmt = $conn->query("
    SELECT
        COUNT(*) AS total_patients,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_count,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) AS student_count,
        SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) AS teacher_count
    FROM users
    WHERE role != 'admin'
");
$patientStats = $patientStatsStmt ? $patientStatsStmt->fetch(PDO::FETCH_ASSOC) : [];

$stmt = $conn->prepare("SELECT COUNT(*) FROM visits WHERE visit_date BETWEEN ? AND ?");
$stmt->execute([$start, $end]);
$totalVisits = (int) $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT v.user_id) FROM visits v
    WHERE v.visit_date BETWEEN ? AND ?
");
$stmt->execute([$start, $end]);
$distinctPatientsSeen = (int) $stmt->fetchColumn();

$totalLowStock = (int) $conn->query("
    SELECT COUNT(*) FROM (
        SELECT SUM(quantity) AS total_quantity
        FROM stocks
        GROUP BY med_id
        HAVING total_quantity <= 10
    ) AS lowstock
")->fetchColumn();

$medCatalog = $conn->query("
    SELECT
        COUNT(*) AS total_skus,
        SUM(CASE WHEN total_quantity <= 0 THEN 1 ELSE 0 END) AS out_count,
        SUM(CASE WHEN total_quantity > 0 AND total_quantity <= 10 THEN 1 ELSE 0 END) AS low_count
    FROM medicines
")->fetch(PDO::FETCH_ASSOC);
$medicineSkuTotal = (int) ($medCatalog['total_skus'] ?? 0);
$medicineOut = (int) ($medCatalog['out_count'] ?? 0);
$medicineLow = (int) ($medCatalog['low_count'] ?? 0);

$stmt = $conn->prepare("
    SELECT status, COUNT(*) AS c
    FROM appointments
    WHERE appointment_date BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$start, $end]);
$appointmentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$appointmentByStatus = [];
$appointmentTotal = 0;
foreach ($appointmentRows as $row) {
    $st = (string) ($row['status'] ?? '');
    $c = (int) ($row['c'] ?? 0);
    $appointmentByStatus[$st] = $c;
    $appointmentTotal += $c;
}
$appointmentPending = (int) ($appointmentByStatus['Pending'] ?? 0);

$stmt = $conn->prepare("
    SELECT DATE(visit_date) AS date, COUNT(*) AS total
    FROM visits
    WHERE visit_date BETWEEN ? AND ?
    GROUP BY DATE(visit_date)
    ORDER BY date ASC
");
$stmt->execute([$start, $end]);
$visitsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT COALESCE(NULLIF(TRIM(complaint), ''), 'Unspecified') AS complaint, COUNT(*) AS total
    FROM visits
    WHERE visit_date BETWEEN ? AND ?
    GROUP BY COALESCE(NULLIF(TRIM(complaint), ''), 'Unspecified')
    ORDER BY total DESC
    LIMIT 8
");
$stmt->execute([$start, $end]);
$illnessData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT
        CASE
            WHEN LOWER(TRIM(COALESCE(u.role, ''))) IN ('student', 'teacher')
                THEN LOWER(TRIM(u.role))
            ELSE 'other'
        END AS role_bucket,
        COUNT(*) AS total
    FROM visits v
    JOIN users u ON v.user_id = u.user_id
    WHERE v.visit_date BETWEEN ? AND ?
    GROUP BY role_bucket
");
$stmt->execute([$start, $end]);
$visitsByRole = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT m.medicine_name, SUM(t.quantity) AS total_used
    FROM treatments t
    JOIN visits v ON t.visit_id = v.visit_id
    JOIN medicines m ON t.med_id = m.med_id
    WHERE v.visit_date BETWEEN ? AND ?
    GROUP BY t.med_id, m.medicine_name
    ORDER BY total_used DESC
    LIMIT 8
");
$stmt->execute([$start, $end]);
$topMeds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT v.visit_date, v.complaint, u.name, u.role AS patient_role
    FROM visits v
    JOIN users u ON v.user_id = u.user_id
    WHERE v.visit_date BETWEEN ? AND ?
    ORDER BY v.visit_date DESC
    LIMIT 6
");
$stmt->execute([$start, $end]);
$recentVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lowStockItems = $conn->query("
    SELECT medicine_name, total_quantity
    FROM medicines
    ORDER BY total_quantity ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$hasVisitActivity = $totalVisits > 0;

$medlogPageHeader = [
    'title' => 'Reports',
    'subtitle' => 'Analytics for roster, visits, scheduling, and pharmacy.',
    'icon' => 'reports',
    'class' => 'medlog-page-header--reports',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — MedLog</title>
    <link rel="stylesheet" href="Css/layout.css">
    <link rel="stylesheet" href="Css/dashboard.css">
    <link rel="stylesheet" href="Css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>

    <div class="dashboard">

        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'includes/header.php'; ?>

            <section class="content reports-page">

                <?php include 'includes/medlog-page-header.php'; ?>

                <nav class="reports-jump" aria-label="Jump to report section">
                    <span class="reports-jump-label">Jump to</span>
                    <a href="#reports-overview">Overview</a>
                    <a href="#reports-clinical">Clinical</a>
                    <a href="#reports-people">Scheduling</a>
                    <a href="#reports-pharmacy">Pharmacy</a>
                    <a href="#reports-lists">Lists</a>
                </nav>

                <div class="reports-filter-panel">
                    <div class="reports-filter-panel-header">Reporting window</div>
                    <form method="GET" id="reportsRangeForm" class="reports-filter-form" aria-label="Report date range">
                        <div class="reports-filter-row">
                            <div class="reports-field">
                                <label for="reportsStart">From</label>
                                <div class="calendar-box" onclick="openCalendarStart()"><input class=" badge"
                                        id="reportsStart" type="date" name="start" required
                                        value="<?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <div class="reports-field">
                                <label for="reportsEnd">To</label>
                                <div class="calendar-box" onclick="openCalendarEnd()"><input class=" badge"
                                        id="reportsEnd" type="date" name="end" required
                                        value="<?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <div class="reports-filter-actions">
                                <button type="submit" class="reports-filter-apply" id="reportsApplyBtn">Apply
                                    range</button>
                            </div>
                        </div>
                        <div class="reports-presets" role="group" aria-label="Quick date ranges">
                            <span>Quick:</span>
                            <a class="reports-preset-link" href="reports.php?preset=this_month"><i
                                    class="fas fa-calendar-day" aria-hidden="true"></i> This month</a>
                            <a class="reports-preset-link" href="reports.php?preset=last_7"><i
                                    class="fas fa-calendar-week" aria-hidden="true"></i> Last 7 days</a>
                            <a class="reports-preset-link" href="reports.php?preset=last_30"><i class="fas fa-calendar"
                                    aria-hidden="true"></i> Last 30 days</a>
                            <a class="reports-preset-link" href="reports.php?preset=this_year"><i
                                    class="fas fa-calendar-days" aria-hidden="true"></i> This year</a>
                        </div>
                    </form>
                </div>

                <div id="reports-overview" class="reports-anchor-target reports-kpi-scroll">
                    <div class="grid-4">
                        <div class="card-reports stat">
                            <i class="fas fa-user-check" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= (int) ($patientStats['active_count'] ?? 0) ?>
                                </h2>
                                <p>Active patients</p>
                                <span class="stat-hint">Non-admin, active (Patients)</span>
                            </div>
                        </div>
                        <div class="card-reports stat">
                            <i class="fas fa-heartbeat" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= $totalVisits ?>
                                </h2>
                                <p>Visits</p>
                                <span class="stat-hint">In selected range</span>
                            </div>
                        </div>
                        <div class="card-reports stat">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= $appointmentTotal ?>
                                </h2>
                                <p>Appointments</p>
                                <span class="stat-hint">Scheduled in range</span>
                            </div>
                        </div>
                        <div class="card-reports stat danger">
                            <i class="fas fa-boxes-stacked" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= $totalLowStock ?>
                                </h2>
                                <p>Low stock SKUs</p>
                                <span class="stat-hint">Stocks ∑ ≤ 10 per medicine</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reports-kpi-scroll">
                    <div class="grid-4">
                        <div class="card-reports stat">
                            <i class="fas fa-user-group" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= $distinctPatientsSeen ?>
                                </h2>
                                <p>Patients seen</p>
                                <span class="stat-hint">Distinct visitors in range</span>
                            </div>
                        </div>
                        <div class="card-reports stat">
                            <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= $appointmentPending ?>
                                </h2>
                                <p>Pending requests</p>
                                <span class="stat-hint">Appointments · Pending</span>
                            </div>
                        </div>
                        <div class="card-reports stat">
                            <i class="fas fa-pills" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= $medicineSkuTotal ?>
                                </h2>
                                <p>Medicine SKUs</p>
                                <span class="stat-hint">
                                    <?= $medicineLow ?> low ·
                                    <?= $medicineOut ?> out
                                </span>
                            </div>
                        </div>
                        <div class="card-reports stat">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            <div>
                                <h2>
                                    <?= (int) ($patientStats['total_patients'] ?? 0) ?>
                                </h2>
                                <p>Registered roster</p>
                                <span class="stat-hint">
                                    <?= (int) ($patientStats['student_count'] ?? 0) ?> students ·
                                    <?= (int) ($patientStats['teacher_count'] ?? 0) ?> teachers
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 id="reports-clinical" class="reports-section-title reports-anchor-target">Clinical activity</h2>
                <div class="grid-2">
                    <div class="card reports-chart-card">
                        <div class="reports-chart-head">
                            <h3>Visit volume</h3>
                            <span class="reports-chart-note">Visits module · daily</span>
                        </div>
                        <?php if (!$hasVisitActivity): ?>
                            <div class="reports-empty-card" role="status">
                                <span class="reports-empty-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
                                <div>No visits in this range. Try a wider window or a quick preset above.</div>
                                <a class="reports-widen-link" href="reports.php?preset=last_30">Try last 30 days</a>
                            </div>
                        <?php else: ?>
                            <div class="reports-chart-canvas-wrap">
                                <canvas id="chartVisitsTrend" height="260" role="img"
                                    aria-label="Line chart of daily visit counts in the selected range"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card reports-chart-card">
                        <div class="reports-chart-head">
                            <h3>Chief complaints</h3>
                            <span class="reports-chart-note">Top reasons · Visits</span>
                        </div>
                        <?php if (!$hasVisitActivity): ?>
                            <div class="reports-empty-card" role="status">
                                <span class="reports-empty-icon" aria-hidden="true"><i
                                        class="fas fa-notes-medical"></i></span>
                                <div>No complaint data in this range.</div>
                                <a class="reports-widen-link" href="reports.php?preset=this_year">Try this year</a>
                            </div>
                        <?php else: ?>
                            <div class="reports-chart-canvas-wrap">
                                <canvas id="chartComplaints" height="260" role="img"
                                    aria-label="Bar chart of top chief complaints by visit count"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <h2 id="reports-people" class="reports-section-title reports-anchor-target">People &amp; scheduling</h2>
                <div class="grid-2">
                    <div class="card reports-chart-card">
                        <div class="reports-chart-head">
                            <h3>Visits by role</h3>
                            <span class="reports-chart-note">Patients roster</span>
                        </div>
                        <?php if (!$hasVisitActivity): ?>
                            <div class="reports-empty-card" role="status">
                                <span class="reports-empty-icon" aria-hidden="true"><i class="fas fa-user-tag"></i></span>
                                <div>No visits in this range.</div>
                                <a class="reports-widen-link" href="reports.php?preset=last_30">Try last 30 days</a>
                            </div>
                        <?php else: ?>
                            <div class="reports-doughnut-wrap reports-chart-canvas-wrap">
                                <canvas id="chartVisitsByRole" height="240" role="img"
                                    aria-label="Doughnut chart of visits by patient role"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card reports-chart-card">
                        <div class="reports-chart-head">
                            <h3>Appointment outcomes</h3>
                            <span class="reports-chart-note">Appointments module</span>
                        </div>
                        <?php if ($appointmentTotal === 0): ?>
                            <div class="reports-empty-card" role="status">
                                <span class="reports-empty-icon" aria-hidden="true"><i
                                        class="fas fa-calendar-xmark"></i></span>
                                <div>No appointments scheduled in this range.</div>
                                <a class="reports-widen-link" href="reports.php?preset=this_year">Try this year</a>
                            </div>
                        <?php else: ?>
                            <div class="reports-doughnut-wrap reports-chart-canvas-wrap">
                                <canvas id="chartAppointmentStatus" height="240" role="img"
                                    aria-label="Doughnut chart of appointment counts by status"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <h2 id="reports-pharmacy" class="reports-section-title reports-anchor-target">Pharmacy</h2>
                <div class="card reports-chart-card reports-chart-card--wide">
                    <div class="reports-chart-head">
                        <h3>Medicines dispensed</h3>
                        <span class="reports-chart-note">Treatments tied to visits in range · Medicines</span>
                    </div>
                    <?php if (empty($topMeds)): ?>
                        <div class="reports-empty-card" role="status">
                            <span class="reports-empty-icon" aria-hidden="true"><i class="fas fa-syringe"></i></span>
                            <div>No treatment rows in this period (or no medicines recorded).</div>
                            <a class="reports-widen-link" href="reports.php?preset=this_year">Try this year</a>
                        </div>
                    <?php else: ?>
                        <div class="reports-chart-canvas-wrap">
                            <canvas id="chartMedicinesUsed" height="280" role="img"
                                aria-label="Horizontal bar chart of top medicines dispensed by quantity"></canvas>
                        </div>
                    <?php endif; ?>
                </div>

                <h2 id="reports-lists" class="reports-section-title reports-anchor-target">Recent activity &amp;
                    inventory</h2>
                <div class="grid-2">
                    <div class="card">
                        <h3>Recent visits</h3>
                        <?php if (empty($recentVisits)): ?>
                            <div class="reports-empty-card reports-empty--inline" role="status">
                                <span class="reports-empty-icon" aria-hidden="true"><i
                                        class="fas fa-clipboard-list"></i></span>
                                <div>No visits in this range.</div>
                                <a class="reports-widen-link" href="reports.php?preset=last_30">Try last 30 days</a>
                            </div>
                        <?php else: ?>
                            <div class="reports-list-stack">
                                <?php foreach ($recentVisits as $row): ?>
                                    <?php
                                    $pr = strtolower(trim((string) ($row['patient_role'] ?? '')));
                                    $roleLabel = $pr !== '' ? ucfirst($pr) : '';
                                    ?>
                                    <div class="list-item">
                                        <div>
                                            <strong>
                                                <?= htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </strong>
                                            <?php if ($roleLabel !== ''): ?>
                                                <span class="reports-role-pill">
                                                    <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endif; ?>
                                            <p>
                                                <?= htmlspecialchars((string) ($row['complaint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </p>
                                        </div>
                                        <span class="visit-meta">
                                            <?= date('M j, Y', strtotime((string) $row['visit_date'])) ?><br>
                                            <?= date('h:i A', strtotime((string) $row['visit_date'])) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3>Top 5 Low Stock Items</h3>
                        <?php if (empty($lowStockItems)): ?>
                            <p class="reports-empty reports-empty--inline">No medicines found.</p>
                        <?php else: ?>
                            <div class="reports-list-stack">
                                <?php foreach ($lowStockItems as $item): ?>
                                    <?php $qty = (int) $item['total_quantity']; ?>
                                    <div class="list-item <?= $qty <= 10 ? 'danger-bg' : '' ?>">
                                        <div>
                                            <strong>
                                                <?= htmlspecialchars((string) $item['medicine_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </strong>
                                        </div>
                                        <div class="reports-qty">
                                            <?= $qty ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </section>
        </main>
    </div>

    <script>
        function openCalendarStart() {
            const input = document.getElementById('reportsStart');

            // focus is important for some browsers
            input.focus();

            if (input.showPicker) {
                input.showPicker();
            } else {
                input.click(); // fallback
            }
        }

        function openCalendarEnd() {
            const input = document.getElementById('reportsEnd');

            // focus is important for some browsers
            input.focus();

            if (input.showPicker) {
                input.showPicker();
            } else {
                input.click(); // fallback
            }
        }

        (function () {
            const form = document.getElementById('reportsRangeForm');
            const btn = document.getElementById('reportsApplyBtn');
            if (form && btn) {
                form.addEventListener('submit',function () {
                    btn.disabled = true;
                    btn.textContent = 'Applying…';
                });
            }

            const ML = { deep: '#1A3D63',blue: '#4A7FA7',mist: '#B3CFE5',ice: '#F6FAFD',coral: '#e85d5d',amber: '#f0a030' };
            Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
            Chart.defaults.color = '#456b80';
            const gridColor = 'rgba(74, 127, 167, 0.12)';
            const commonAxis = { grid: { color: gridColor },ticks: { font: { size: 11 } },border: { display: false } };

            function gradientFill(ctx,topColor,bottomColor) {
                const { chart } = ctx;
                const { ctx: c,chartArea } = chart;
                if (!chartArea) return topColor;
                const g = c.createLinearGradient(0,chartArea.bottom,0,chartArea.top);
                g.addColorStop(0,bottomColor);
                g.addColorStop(1,topColor);
                return g;
            }

            const visitsPoints = <?= json_encode(array_column($visitsData, 'total')) ?>;
            const visitsLabels = <?= json_encode(array_column($visitsData, 'date')) ?>;

            const elTrend = document.getElementById('chartVisitsTrend');
            if (elTrend && visitsLabels.length) {
                new Chart(elTrend,{
                    type: 'line',
                    data: {
                        labels: visitsLabels,
                        datasets: [{
                            label: 'Visits',
                            data: visitsPoints,
                            borderWidth: 2.5,
                            borderColor: ML.blue,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: ML.ice,
                            pointBorderColor: ML.deep,
                            tension: 0.35,
                            fill: true,
                            backgroundColor: (ctx) => gradientFill(ctx,'rgba(74, 127, 167, 0.35)','rgba(246, 250, 253, 0.02)'),
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: 'rgba(10, 25, 49, 0.92)',padding: 12,cornerRadius: 10 },
                        },
                        scales: {
                            x: { ...commonAxis },
                            y: { ...commonAxis,beginAtZero: true,ticks: { precision: 0 } },
                        },
                    },
                });
            }

            const complaintLabels = <?= json_encode(array_column($illnessData, 'complaint')) ?>;
            const complaintVals = <?= json_encode(array_column($illnessData, 'total')) ?>;

            const elBar = document.getElementById('chartComplaints');
            if (elBar && complaintLabels.length) {
                const barGrad = (i) => {
                    const a = [ML.deep,ML.blue,ML.mist,ML.deep,ML.coral,ML.amber];
                    return a[i % a.length];
                };
                new Chart(elBar,{
                    type: 'bar',
                    data: {
                        labels: complaintLabels,
                        datasets: [{
                            label: 'Cases',
                            data: complaintVals,
                            borderRadius: 10,
                            borderSkipped: false,
                            backgroundColor: complaintVals.map((_,i) => barGrad(i)),
                            maxBarThickness: 44,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: 'rgba(10, 25, 49, 0.92)',padding: 12,cornerRadius: 10 },
                        },
                        scales: {
                            x: { ...commonAxis },
                            y: { ...commonAxis,beginAtZero: true,ticks: { precision: 0 } },
                        },
                    },
                });
            }

            const roleLabelsRaw = <?= json_encode(array_column($visitsByRole, 'role_bucket')) ?>;
            const roleVals = <?= json_encode(array_column($visitsByRole, 'total')) ?>;
            const roleLabels = roleLabelsRaw.map((r) => r === 'other' ? 'Other' : r.charAt(0).toUpperCase() + r.slice(1));

            const elRole = document.getElementById('chartVisitsByRole');
            if (elRole && roleVals.length) {
                new Chart(elRole,{
                    type: 'doughnut',
                    data: {
                        labels: roleLabels,
                        datasets: [{
                            data: roleVals,
                            backgroundColor: [ML.blue,ML.deep,ML.mist],
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 10,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom',labels: { boxWidth: 12,padding: 16,font: { size: 12 } } },
                        },
                    },
                });
            }

            const apptStatusOrder = ['Pending','Approved','Rejected','Cancelled'];
            const apptMap = <?= json_encode($appointmentByStatus) ?>;
            const apptLabels = [];
            const apptData = [];
            apptStatusOrder.forEach((s) => {
                const n = apptMap[s] != null ? Number(apptMap[s]) : 0;
                if (n > 0) {
                    apptLabels.push(s);
                    apptData.push(n);
                }
            });

            const elAppt = document.getElementById('chartAppointmentStatus');
            if (elAppt && apptData.length) {
                new Chart(elAppt,{
                    type: 'doughnut',
                    data: {
                        labels: apptLabels,
                        datasets: [{
                            data: apptData,
                            backgroundColor: [ML.amber,ML.blue,ML.coral,'#94a3b8'],
                            borderWidth: 3,
                            borderColor: '#ffffff',
                            hoverOffset: 10,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: { position: 'bottom',labels: { boxWidth: 12,padding: 16,font: { size: 12 } } },
                        },
                    },
                });
            }

            const medLabels = <?= json_encode(array_column($topMeds, 'medicine_name')) ?>;
            const medVals = <?= json_encode(array_column($topMeds, 'total_used')) ?>;

            const elMed = document.getElementById('chartMedicinesUsed');
            if (elMed && medLabels.length) {
                const medGrad = elMed.getContext('2d').createLinearGradient(0,0,elMed.width || 400,0);
                medGrad.addColorStop(0,ML.deep);
                medGrad.addColorStop(1,ML.blue);

                new Chart(elMed,{
                    type: 'bar',
                    data: {
                        labels: medLabels,
                        datasets: [{
                            label: 'Units',
                            data: medVals,
                            borderRadius: 12,
                            borderSkipped: false,
                            backgroundColor: medGrad,
                            maxBarThickness: 28,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: 'rgba(10, 25, 49, 0.92)',padding: 12,cornerRadius: 10 },
                        },
                        scales: {
                            x: { ...commonAxis,beginAtZero: true,ticks: { precision: 0 } },
                            y: { ...commonAxis,ticks: { font: { size: 11 } } },
                        },
                    },
                });
            }
        })();
    </script>

</body>

</html>