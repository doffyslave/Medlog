<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$role = strtolower(trim((string) ($user['role'] ?? 'guest')));

require 'Database/connection.php';

$user_id = $user['user_id'];


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

// ================= DASHBOARD FILTER =================
if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $_SESSION['dashboard_selected_date'] = $_GET['date'];
}

$selectedDate = $_SESSION['dashboard_selected_date'] ?? date('Y-m-d');

$activeTab = $_GET['tab'] ?? ($_SESSION['dashboard_active_tab'] ?? 'visits');
if (!in_array($activeTab, ['visits', 'appointments'], true)) {
    $activeTab = 'visits';
}
$_SESSION['dashboard_active_tab'] = $activeTab;

// ================= SAFE FUNCTION =================
function getSingle($conn, $query)
{
    try {
        $result = $conn->query($query);
        return $result ? ($result->fetch()['total'] ?? 0) : 0;
    } catch (Exception $e) {
        return 0;
    }
}

function medlog_appt_status_class($apSt)
{
    if ($apSt === 'Approved') {
        return 'approved';
    }
    if ($apSt === 'Rejected') {
        return 'rejected';
    }
    if ($apSt === 'Cancelled') {
        return 'cancelled';
    }
    if ($apSt === 'Completed') {
        return 'completed';
    }
    if ($apSt === 'Missed') {
        return 'missed';
    }
    if ($apSt === 'Rescheduled') {
        return 'rescheduled';
    }
    return 'pending';
}

function medlog_fetch_admin_day_data($conn, $selectedDate)
{
    $recentVisits = [];
    $dayAppointments = [];
    $lowStockItems = [];
    $lowStockToShow = [];

    try {
        $q = $conn->prepare("
            SELECT v.complaint, v.visit_date, u.name
            FROM visits v
            JOIN users u ON v.user_id = u.user_id
            WHERE DATE(v.visit_date) = ?
            ORDER BY v.visit_date DESC
            LIMIT 20
        ");
        $q->execute([$selectedDate]);
        $recentVisits = $q ? $q->fetchAll() : [];
    } catch (Exception $e) {
    }

    try {
        $q = $conn->prepare("
            SELECT a.appointment_time, a.reason, a.status, u.name
            FROM appointments a
            JOIN users u ON a.user_id = u.user_id
            WHERE a.appointment_date = ?
            ORDER BY STR_TO_DATE(a.appointment_time, '%h:%i %p') ASC
            LIMIT 20
        ");
        $q->execute([$selectedDate]);
        $dayAppointments = $q ? $q->fetchAll() : [];
    } catch (Exception $e) {
    }

    try {
        $q = $conn->query("
            SELECT medicine_name, total_quantity
            FROM medicines
            ORDER BY total_quantity ASC
            LIMIT 5
        ");
        $lowStockItems = $q ? $q->fetchAll() : [];
    } catch (Exception $e) {
    }

    foreach ($lowStockItems as $item) {
        if ((int) $item['total_quantity'] <= 10) {
            $lowStockToShow[] = $item;
        }
    }

    $calTs = strtotime($selectedDate);
    $calYear = (int) date('Y', $calTs);
    $calMonth = (int) date('m', $calTs);

    return [
        'recentVisits' => $recentVisits,
        'dayAppointments' => $dayAppointments,
        'lowStockToShow' => $lowStockToShow,
        'dayVisitCount' => count($recentVisits),
        'dayApptCount' => count($dayAppointments),
        'busiestLabel' => count($recentVisits) >= count($dayAppointments) ? 'Walk-in visits' : 'Appointments',
        'busiestCount' => max(count($recentVisits), count($dayAppointments)),
        'calYear' => $calYear,
        'calMonth' => $calMonth,
        'calMonthName' => date('F Y', $calTs),
        'daysInMonth' => (int) date('t', $calTs),
        'firstWeekday' => (int) date('N', strtotime("$calYear-$calMonth-01")),
        'selectedDay' => (int) date('j', $calTs),
        'prevMonthDate' => date('Y-m-d', strtotime("$calYear-$calMonth-01 -1 month")),
        'nextMonthDate' => date('Y-m-d', strtotime("$calYear-$calMonth-01 +1 month")),
        'isTodaySelected' => ($selectedDate === date('Y-m-d')),
    ];
}

function medlog_admin_header_labels($selectedDate, $isTodaySelected)
{
    $headerDateLabel = date('l, F j, Y', strtotime($selectedDate));
    $headerContext = $isTodaySelected
        ? "Today's operations"
        : 'Operations for ' . date('M j, Y', strtotime($selectedDate));

    return [
        'subtitle' => $headerContext . ' · ' . $headerDateLabel,
        'chip' => date('M j, Y', strtotime($selectedDate)),
        'chipDay' => $isTodaySelected ? 'Today' : date('D', strtotime($selectedDate)),
    ];
}

function medlog_render_visits_panel(array $recentVisits)
{
    ob_start();
    if ($recentVisits): ?>
        <ul class="activity-feed__list">
            <?php foreach ($recentVisits as $visit): ?>
                <li class="activity-feed__item">
                    <span class="activity-feed__dot activity-feed__dot--visit" aria-hidden="true"></span>
                    <div class="activity-feed__content">
                        <strong class="activity-feed__name"><?= htmlspecialchars($visit['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="activity-feed__reason"><?= htmlspecialchars($visit['complaint'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <time class="activity-feed__time" datetime="<?= date('c', strtotime($visit['visit_date'])) ?>">
                        <?= date('h:i A', strtotime($visit['visit_date'])) ?>
                    </time>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="empty-state-card">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <p>No clinic activity scheduled for this date.</p>
            <span>Walk-in visits will appear here when recorded.</span>
        </div>
    <?php endif;
    return ob_get_clean();
}

function medlog_render_appts_panel(array $dayAppointments)
{
    ob_start();
    if ($dayAppointments): ?>
        <ul class="activity-feed__list">
            <?php foreach ($dayAppointments as $appointment):
                $statusClass = medlog_appt_status_class($appointment['status'] ?? '');
                ?>
                <li class="activity-feed__item">
                    <span class="activity-feed__dot activity-feed__dot--appt" aria-hidden="true"></span>
                    <div class="activity-feed__content">
                        <strong class="activity-feed__name"><?= htmlspecialchars($appointment['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="activity-feed__reason"><?= htmlspecialchars($appointment['reason'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="activity-feed__meta">
                        <span class="activity-feed__time"><?= htmlspecialchars($appointment['appointment_time'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($appointment['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="empty-state-card">
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            <p>No appointments booked for this date.</p>
            <span>Scheduled slots will show in this feed.</span>
        </div>
    <?php endif;
    return ob_get_clean();
}

function medlog_render_insights_mosaic($dayVisitCount, $dayApptCount, $busiestLabel, $busiestCount, $totalLowStock, $selectedDate)
{
    ob_start(); ?>
    <div class="clinical-insights__mosaic">
        <div class="insight-feature insight-feature--primary">
            <span class="insight-feature__eyebrow">Featured · busiest channel</span>
            <strong class="insight-feature__value"><?= htmlspecialchars($busiestLabel, ENT_QUOTES, 'UTF-8') ?></strong>
            <p class="insight-feature__detail"><?= (int) $busiestCount ?> recorded on <?= date('M j', strtotime($selectedDate)) ?></p>
            <div class="insight-feature__bar" aria-hidden="true">
                <span style="width: <?= min(100, max(8, $busiestCount * 12)) ?>%"></span>
            </div>
        </div>
        <div class="insight-stack">
            <div class="insight-mini">
                <span class="insight-mini__label">Walk-ins</span>
                <strong class="insight-mini__value"><?= (int) $dayVisitCount ?></strong>
            </div>
            <div class="insight-mini insight-mini--soft">
                <span class="insight-mini__label">Appointments</span>
                <strong class="insight-mini__value"><?= (int) $dayApptCount ?></strong>
            </div>
        </div>
        <div class="insight-strip<?= $totalLowStock > 0 ? ' insight-strip--warn' : '' ?>">
            <div class="insight-strip__copy">
                <span class="insight-strip__label">Inventory</span>
                <strong><?= (int) $totalLowStock ?> low-stock <?= $totalLowStock === 1 ? 'item' : 'items' ?></strong>
            </div>
            <span class="insight-strip__hint"><?= $totalLowStock > 0 ? 'Review restock list below' : 'Levels look healthy' ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function medlog_render_calendar_days(array $cal, $selectedDate)
{
    ob_start();
    for ($blank = 1; $blank < $cal['firstWeekday']; $blank++): ?>
        <span class="calendar-widget__day calendar-widget__day--empty"></span>
    <?php endfor;
    for ($day = 1; $day <= $cal['daysInMonth']; $day++):
        $dayDate = sprintf('%04d-%02d-%02d', $cal['calYear'], $cal['calMonth'], $day);
        $isSelected = ($day === $cal['selectedDay']);
        $isToday = ($dayDate === date('Y-m-d'));
        ?>
        <button type="button" class="calendar-widget__day<?= $isSelected ? ' is-selected' : '' ?><?= $isToday ? ' is-today' : '' ?>"
            data-date="<?= htmlspecialchars($dayDate, ENT_QUOTES, 'UTF-8') ?>"><?= $day ?></button>
    <?php endfor;
    return ob_get_clean();
}

function medlog_render_low_stock(array $lowStockToShow)
{
    ob_start();
    if ($lowStockToShow): ?>
        <ul class="stock-alert-card__list">
            <?php foreach ($lowStockToShow as $item):
                $qty = (int) $item['total_quantity'];
                ?>
                <li class="stock-alert-card__item">
                    <span class="stock-alert-card__name"><?= htmlspecialchars($item['medicine_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="stock-alert-card__qty<?= $qty <= 5 ? ' stock-alert-card__qty--critical' : '' ?>"><?= $qty ?> left</span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="empty-state-card empty-state-card--compact">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <p>All medicines are adequately stocked.</p>
        </div>
    <?php endif;
    return ob_get_clean();
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

    } catch (Exception $e) {
    }
}


// ================= ADMIN DATA =================
$recentVisits = [];
$dayAppointments = [];
$lowStockToShow = [];
$dayVisitCount = 0;
$dayApptCount = 0;
$busiestLabel = 'Walk-in visits';
$busiestCount = 0;
$calYear = (int) date('Y');
$calMonth = (int) date('m');
$calMonthName = date('F Y');
$daysInMonth = (int) date('t');
$firstWeekday = (int) date('N');
$selectedDay = (int) date('j');
$prevMonthDate = date('Y-m-d');
$nextMonthDate = date('Y-m-d');
$isTodaySelected = true;
$adminHeaderLabels = ['subtitle' => '', 'chip' => '', 'chipDay' => ''];

if ($role === 'admin') {
    $adminDay = medlog_fetch_admin_day_data($conn, $selectedDate);
    $recentVisits = $adminDay['recentVisits'];
    $dayAppointments = $adminDay['dayAppointments'];
    $lowStockToShow = $adminDay['lowStockToShow'];
    $dayVisitCount = $adminDay['dayVisitCount'];
    $dayApptCount = $adminDay['dayApptCount'];
    $busiestLabel = $adminDay['busiestLabel'];
    $busiestCount = $adminDay['busiestCount'];
    $calYear = $adminDay['calYear'];
    $calMonth = $adminDay['calMonth'];
    $calMonthName = $adminDay['calMonthName'];
    $daysInMonth = $adminDay['daysInMonth'];
    $firstWeekday = $adminDay['firstWeekday'];
    $selectedDay = $adminDay['selectedDay'];
    $prevMonthDate = $adminDay['prevMonthDate'];
    $nextMonthDate = $adminDay['nextMonthDate'];
    $isTodaySelected = $adminDay['isTodaySelected'];
    $adminHeaderLabels = medlog_admin_header_labels($selectedDate, $isTodaySelected);
}

$isAdmin = ($role === 'admin');

if ($isAdmin && isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $labels = medlog_admin_header_labels($selectedDate, $isTodaySelected);
    echo json_encode([
        'ok' => true,
        'date' => $selectedDate,
        'subtitle' => $labels['subtitle'],
        'chip' => $labels['chip'],
        'chipDay' => $labels['chipDay'],
        'activityDesc' => date('M j, Y', strtotime($selectedDate)),
        'insightsDate' => date('M j', strtotime($selectedDate)),
        'visitsHtml' => medlog_render_visits_panel($recentVisits),
        'appointmentsHtml' => medlog_render_appts_panel($dayAppointments),
        'insightsHtml' => medlog_render_insights_mosaic($dayVisitCount, $dayApptCount, $busiestLabel, $busiestCount, $totalLowStock, $selectedDate),
        'lowStockHtml' => medlog_render_low_stock($lowStockToShow),
        'calendarMonth' => $calMonthName,
        'calendarDaysHtml' => medlog_render_calendar_days([
            'calYear' => $calYear,
            'calMonth' => $calMonth,
            'daysInMonth' => $daysInMonth,
            'firstWeekday' => $firstWeekday,
            'selectedDay' => $selectedDay,
        ], $selectedDate),
        'prevMonthDate' => $prevMonthDate,
        'nextMonthDate' => $nextMonthDate,
        'opsSummary' => (int) $dayVisitCount . ' visits · ' . (int) $dayApptCount . ' appointments today',
    ]);
    exit;
}

ob_start();
?>
<div class="status-inline">

    <div class="inline-badge <?= $isAdmin ? 'clickable' : '' ?> <?= strtolower($clinicStatus) ?>" <?= $isAdmin ? 'onclick="openModal(\'clinicModal\')"' : '' ?>>
        <i class="fas fa-clinic-medical"></i>
        <span class="badge"><?= htmlspecialchars($clinicStatus, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="inline-badge <?= $isAdmin ? 'clickable' : '' ?> <?= strtolower($nurseStatus) ?>" <?= $isAdmin ? 'onclick="openModal(\'nurseModal\')"' : '' ?>>
        <i class="fas fa-user-nurse"></i>
        <span class="badge"><?= htmlspecialchars($nurseStatus, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="calendar-box" onclick="openCalendar()">
        <input class="badge" type="date" id="calendarFilter"
            value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>">
    </div>

</div>
<?php
$__dashboardHeaderActions = ob_get_clean();

if (!$isAdmin) {
    $medlogPageHeader = [
        'title' => 'Dashboard',
        'subtitle' => "Welcome back! Here's your clinic overview.",
        'icon' => 'dashboard',
        'class' => 'medlog-page-header--dashboard',
        'actions' => $__dashboardHeaderActions,
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedLog Dashboard</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="Css/dashboard.css">
    <link rel="stylesheet" href="Css/layout.css?v=20260519-dock-circle-lock">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body<?= $role === 'student' ? ' class="medlog-student-shell"' : ($isAdmin ? ' class="medlog-admin-dashboard-body"' : '') ?>>

    <div class="dashboard">

        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">

            <?php include 'includes/header.php'; ?>

            <section class="content">

                <?php if (!$isAdmin): ?>
                    <?php include 'includes/medlog-page-header.php'; ?>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>

                    <header class="dash-command-header" id="dashCommandHeader">
                        <div class="dash-command-header__lead">
                            <span class="dash-command-header__eyebrow">STI School Clinic · MedLog</span>
                            <h1 class="dash-command-header__title">Clinic Command Center</h1>
                            <p class="dash-command-header__subtitle" id="dashHeaderSubtitle"><?= htmlspecialchars($adminHeaderLabels['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="dash-command-header__visual" aria-hidden="true">
                            <div class="dash-command-header__orb"></div>
                            <i class="fas fa-heart-pulse"></i>
                        </div>
                        <div class="dash-command-header__meta">
                            <div class="dash-command-header__chips">
                                <span class="date-chip" id="dashDateChip">
                                    <span class="date-chip__day" id="dashDateChipDay"><?= htmlspecialchars($adminHeaderLabels['chipDay'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="date-chip__date" id="dashDateChipDate"><?= htmlspecialchars($adminHeaderLabels['chip'], ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <span class="ops-badge" id="dashOpsBadge"><?= (int) $dayVisitCount ?> visits · <?= (int) $dayApptCount ?> appointments today</span>
                            </div>
                            <div class="dash-command-header__status">
                                <button type="button" class="header-status-chip <?= strtolower($clinicStatus) ?>" onclick="openModal('clinicModal')">
                                    <i class="fas fa-clinic-medical"></i>
                                    <span>Clinic <strong><?= htmlspecialchars($clinicStatus, ENT_QUOTES, 'UTF-8') ?></strong></span>
                                </button>
                                <button type="button" class="header-status-chip <?= strtolower($nurseStatus) ?>" onclick="openModal('nurseModal')">
                                    <i class="fas fa-user-nurse"></i>
                                    <span>Nurse <strong><?= htmlspecialchars($nurseStatus, ENT_QUOTES, 'UTF-8') ?></strong></span>
                                </button>
                            </div>
                        </div>
                    </header>

                    <div class="admin-dashboard" id="adminDashboard" data-selected-date="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>" data-active-tab="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="admin-dashboard__metrics">
                            <article class="metric-card metric-card--visits">
                                <div class="metric-card__icon" aria-hidden="true"><i class="fas fa-heartbeat"></i></div>
                                <div class="metric-card__body">
                                    <span class="metric-card__value"><?= (int) $totalVisits ?></span>
                                    <span class="metric-card__label">Visits this month</span>
                                </div>
                            </article>
                            <article class="metric-card metric-card--patients">
                                <div class="metric-card__icon" aria-hidden="true"><i class="fas fa-user-graduate"></i></div>
                                <div class="metric-card__body">
                                    <span class="metric-card__value"><?= (int) $totalPatients ?></span>
                                    <span class="metric-card__label">Registered patients</span>
                                </div>
                            </article>
                            <article class="metric-card metric-card--stock<?= $totalLowStock > 0 ? ' metric-card--alert' : '' ?>">
                                <div class="metric-card__icon" aria-hidden="true"><i class="fas fa-pills"></i></div>
                                <div class="metric-card__body">
                                    <span class="metric-card__value"><?= (int) $totalLowStock ?></span>
                                    <span class="metric-card__label">Low stock items</span>
                                </div>
                            </article>
                            <article class="metric-card metric-card--records">
                                <div class="metric-card__icon" aria-hidden="true"><i class="fas fa-file-medical"></i></div>
                                <div class="metric-card__body">
                                    <span class="metric-card__value"><?= (int) $totalRecords ?></span>
                                    <span class="metric-card__label">Total visit records</span>
                                </div>
                            </article>
                        </div>

                        <div class="admin-dashboard__grid">

                            <div class="admin-dashboard__main">

                                <article class="dashboard-card activity-feed activity-feed--primary">
                                    <header class="dashboard-card__head tabs-header">
                                        <div>
                                            <h2 class="dashboard-card__title">Activity timeline</h2>
                                            <p class="dashboard-card__desc" id="activityFeedDesc">Clinic flow for <?= date('M j, Y', strtotime($selectedDate)) ?></p>
                                        </div>
                                        <div class="tabs" role="tablist" aria-label="Activity type">
                                            <button type="button" role="tab" class="tab-btn <?= $activeTab === 'visits' ? 'active' : '' ?>"
                                                data-tab="visits" id="tab-visits" aria-selected="<?= $activeTab === 'visits' ? 'true' : 'false' ?>"
                                                aria-controls="panel-visits">Visits</button>
                                            <button type="button" role="tab" class="tab-btn <?= $activeTab === 'appointments' ? 'active' : '' ?>"
                                                data-tab="appointments" id="tab-appointments" aria-selected="<?= $activeTab === 'appointments' ? 'true' : 'false' ?>"
                                                aria-controls="panel-appointments">Appointments</button>
                                        </div>
                                    </header>

                                    <div id="panel-visits" class="agenda-panel activity-feed__panel <?= $activeTab === 'visits' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tab-visits">
                                        <?= medlog_render_visits_panel($recentVisits) ?>
                                    </div>

                                    <div id="panel-appointments" class="agenda-panel activity-feed__panel <?= $activeTab === 'appointments' ? 'active' : '' ?>" role="tabpanel" aria-labelledby="tab-appointments">
                                        <?= medlog_render_appts_panel($dayAppointments) ?>
                                    </div>
                                </article>

                                <div class="admin-dashboard__lower">
                                    <article class="dashboard-card clinical-insights" id="clinicalInsightsCard">
                                        <header class="dashboard-card__head">
                                            <h2 class="dashboard-card__title">Clinical insights</h2>
                                            <p class="dashboard-card__desc" id="insightsDesc">Snapshot for <?= date('M j', strtotime($selectedDate)) ?></p>
                                        </header>
                                        <div id="insightsMosaic">
                                            <?= medlog_render_insights_mosaic($dayVisitCount, $dayApptCount, $busiestLabel, $busiestCount, $totalLowStock, $selectedDate) ?>
                                        </div>
                                    </article>

                                    <div class="admin-dashboard__lower-secondary">
                                        <nav class="dashboard-card quick-actions" aria-label="Quick actions">
                                            <h2 class="dashboard-card__title">Quick actions</h2>
                                            <div class="quick-actions__grid">
                                                <a href="patients.php" class="quick-actions__link"><i class="fas fa-users"></i><span>Patients</span></a>
                                                <a href="visits.php" class="quick-actions__link"><i class="fas fa-stethoscope"></i><span>Visits</span></a>
                                                <a href="appointments.php" class="quick-actions__link"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
                                                <a href="stocks.php" class="quick-actions__link"><i class="fas fa-boxes-stacked"></i><span>Inventory</span></a>
                                                <a href="reports.php" class="quick-actions__link quick-actions__link--wide"><i class="fas fa-chart-line"></i><span>Reports</span></a>
                                            </div>
                                        </nav>

                                        <article class="dashboard-card stock-alert-card stock-alert-card--compact" id="lowStockCard">
                                            <header class="dashboard-card__head">
                                                <h2 class="dashboard-card__title">Low stock</h2>
                                                <p class="dashboard-card__desc">≤10 units</p>
                                            </header>
                                            <div id="lowStockContent">
                                                <?= medlog_render_low_stock($lowStockToShow) ?>
                                            </div>
                                        </article>
                                    </div>
                                </div>

                            </div>

                            <aside class="admin-dashboard__aside">

                                <article class="dashboard-card calendar-widget" id="calendarWidget">
                                    <header class="calendar-widget__head">
                                        <h2 class="dashboard-card__title">Calendar</h2>
                                        <div class="calendar-widget__nav">
                                            <button type="button" class="calendar-widget__arrow" data-date="<?= htmlspecialchars($prevMonthDate, ENT_QUOTES, 'UTF-8') ?>" id="calPrevMonth" aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>
                                            <span class="calendar-widget__month" id="calendarMonthLabel"><?= htmlspecialchars($calMonthName, ENT_QUOTES, 'UTF-8') ?></span>
                                            <button type="button" class="calendar-widget__arrow" data-date="<?= htmlspecialchars($nextMonthDate, ENT_QUOTES, 'UTF-8') ?>" id="calNextMonth" aria-label="Next month"><i class="fas fa-chevron-right"></i></button>
                                        </div>
                                    </header>
                                    <div class="calendar-widget__weekdays" aria-hidden="true">
                                        <span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span>
                                    </div>
                                    <div class="calendar-widget__days" id="calendarDays">
                                        <?= medlog_render_calendar_days([
                                            'calYear' => $calYear,
                                            'calMonth' => $calMonth,
                                            'daysInMonth' => $daysInMonth,
                                            'firstWeekday' => $firstWeekday,
                                            'selectedDay' => $selectedDay,
                                        ], $selectedDate) ?>
                                    </div>
                                    <input type="date" id="calendarFilter" class="calendar-widget__picker"
                                        value="<?= htmlspecialchars($selectedDate, ENT_QUOTES, 'UTF-8') ?>" aria-label="Jump to date">
                                </article>

                                <article class="dashboard-card status-panel">
                                    <header class="dashboard-card__head">
                                        <h2 class="dashboard-card__title">Live clinic status</h2>
                                        <p class="dashboard-card__desc">Operational control</p>
                                    </header>
                                    <p class="status-panel__clock" id="clinicLiveClock" aria-live="polite"></p>
                                    <div class="status-panel__controls">
                                        <button type="button" class="status-panel__row clickable <?= strtolower($clinicStatus) ?>"
                                            onclick="openModal('clinicModal')">
                                            <span class="status-panel__icon"><i class="fas fa-clinic-medical"></i></span>
                                            <span class="status-panel__copy">
                                                <span class="status-panel__label">Clinic</span>
                                                <span class="status-panel__value"><?= htmlspecialchars($clinicStatus, ENT_QUOTES, 'UTF-8') ?></span>
                                            </span>
                                            <i class="fas fa-pen status-panel__edit" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="status-panel__row clickable <?= strtolower($nurseStatus) ?>"
                                            onclick="openModal('nurseModal')">
                                            <span class="status-panel__icon"><i class="fas fa-user-nurse"></i></span>
                                            <span class="status-panel__copy">
                                                <span class="status-panel__label">Nurse</span>
                                                <span class="status-panel__value"><?= htmlspecialchars($nurseStatus, ENT_QUOTES, 'UTF-8') ?></span>
                                            </span>
                                            <i class="fas fa-pen status-panel__edit" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </article>

                            </aside>
                        </div>
                    </div>

                <?php endif; ?>


                <!-- ðŸ”µ STUDENT DASHBOARD -->
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

    <!-- CLINIC MODAL -->
    <div id="clinicModal" class="modal">
        <div class="modal-content">
            <h3>Update Clinic Status</h3>

            <form method="POST">
                <select name="clinic_status">
                    <option <?= $clinicStatus == 'Open' ? 'selected' : '' ?>>Open</option>
                    <option <?= $clinicStatus == 'Closed' ? 'selected' : '' ?>>Closed</option>
                </select>

                <input type="hidden" name="nurse_status" value="<?= $nurseStatus ?>">

                <button type="submit" name="update_status" class="btn-primary">Save</button>
            </form>

            <span class="close" onclick="closeModal('clinicModal')">&times;</span>
        </div>
    </div>

    <!-- NURSE MODAL -->
    <div id="nurseModal" class="modal">
        <div class="modal-content">
            <h3>Update Nurse Status</h3>

            <form method="POST">
                <select name="nurse_status">
                    <option <?= $nurseStatus == 'Available' ? 'selected' : '' ?>>Available</option>
                    <option <?= $nurseStatus == 'Lunch' ? 'selected' : '' ?>>Lunch</option>
                    <option <?= $nurseStatus == 'Offline' ? 'selected' : '' ?>>Offline</option>
                </select>

                <input type="hidden" name="clinic_status" value="<?= $clinicStatus ?>">

                <button type="submit" name="update_status" class="btn-primary">Save</button>
            </form>

            <span class="close" onclick="closeModal('nurseModal')">&times;</span>
        </div>
    </div>
    <script>
        function openModal(id) {
            document.getElementById(id).style.display = "flex";
        }

        function closeModal(id) {
            document.getElementById(id).style.display = "none";
        }

        // close when clicking outside
        window.onclick = function (e) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (e.target === modal) {
                    modal.style.display = "none";
                }
            });
        }

        function openCalendar() {
            const input = document.getElementById('calendarFilter');
            if (!input) return;
            input.focus();
            if (input.showPicker) {
                input.showPicker();
            } else {
                input.click();
            }
        }

        function updateClinicClock() {
            const el = document.getElementById('clinicLiveClock');
            if (!el) return;
            const now = new Date();
            el.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        updateClinicClock();
        setInterval(updateClinicClock, 1000);

        (function initAdminDashboard() {
            const root = document.getElementById('adminDashboard');
            if (!root) return;

            const panelVisits = document.getElementById('panel-visits');
            const panelAppointments = document.getElementById('panel-appointments');
            const tabButtons = root.querySelectorAll('.tab-btn[data-tab]');
            const calendarInput = document.getElementById('calendarFilter');
            let activeTab = root.dataset.activeTab || 'visits';
            let loadingDate = false;

            function getActiveTab() {
                return activeTab;
            }

            function setActiveTab(tab, updateUrl = true) {
                activeTab = tab;
                root.dataset.activeTab = tab;

                tabButtons.forEach(btn => {
                    const on = btn.dataset.tab === tab;
                    btn.classList.toggle('active', on);
                    btn.setAttribute('aria-selected', on ? 'true' : 'false');
                });

                panelVisits.classList.toggle('active', tab === 'visits');
                panelAppointments.classList.toggle('active', tab === 'appointments');
                panelVisits.hidden = tab !== 'visits';
                panelAppointments.hidden = tab !== 'appointments';

                if (updateUrl) {
                    const params = new URLSearchParams(window.location.search);
                    params.set('date', root.dataset.selectedDate || '');
                    params.set('tab', tab);
                    history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
                }
            }

            tabButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    setActiveTab(this.dataset.tab, true);
                });
            });

            document.getElementById('calendarDays')?.addEventListener('click', function (e) {
                const btn = e.target.closest('.calendar-widget__day[data-date]');
                if (btn) loadDashboardDate(btn.dataset.date);
            });

            async function loadDashboardDate(date) {
                if (!date || loadingDate) return;
                loadingDate = true;
                const scrollY = window.scrollY;
                root.classList.add('is-loading');

                try {
                    const params = new URLSearchParams({
                        ajax: '1',
                        date: date,
                        tab: getActiveTab(),
                    });
                    const res = await fetch(`dashboard.php?${params.toString()}`, {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) throw new Error('Failed to load');
                    const data = await res.json();
                    if (!data.ok) throw new Error('Invalid response');

                    root.dataset.selectedDate = data.date;
                    panelVisits.innerHTML = data.visitsHtml;
                    panelAppointments.innerHTML = data.appointmentsHtml;
                    document.getElementById('insightsMosaic').innerHTML = data.insightsHtml;
                    document.getElementById('lowStockContent').innerHTML = data.lowStockHtml;
                    document.getElementById('calendarDays').innerHTML = data.calendarDaysHtml;
                    document.getElementById('calendarMonthLabel').textContent = data.calendarMonth;
                    document.getElementById('calPrevMonth').dataset.date = data.prevMonthDate;
                    document.getElementById('calNextMonth').dataset.date = data.nextMonthDate;
                    document.getElementById('dashHeaderSubtitle').textContent = data.subtitle;
                    document.getElementById('dashDateChipDay').textContent = data.chipDay;
                    document.getElementById('dashDateChipDate').textContent = data.chip;
                    document.getElementById('dashOpsBadge').textContent = data.opsSummary;
                    document.getElementById('activityFeedDesc').textContent = 'Clinic flow for ' + data.activityDesc;
                    document.getElementById('insightsDesc').textContent = 'Snapshot for ' + data.insightsDate;

                    if (calendarInput) calendarInput.value = data.date;

                    document.querySelectorAll('#calendarDays .calendar-widget__day').forEach(el => {
                        el.classList.toggle('is-selected', el.dataset.date === data.date);
                    });

                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('date', data.date);
                    urlParams.set('tab', getActiveTab());
                    history.replaceState({}, '', `${window.location.pathname}?${urlParams.toString()}`);

                    window.scrollTo(0, scrollY);
                } catch (err) {
                    console.error(err);
                } finally {
                    loadingDate = false;
                    root.classList.remove('is-loading');
                }
            }

            document.getElementById('calPrevMonth')?.addEventListener('click', function () {
                loadDashboardDate(this.dataset.date);
            });
            document.getElementById('calNextMonth')?.addEventListener('click', function () {
                loadDashboardDate(this.dataset.date);
            });

            if (calendarInput) {
                calendarInput.addEventListener('change', function () {
                    loadDashboardDate(this.value);
                });
            }

            setActiveTab(activeTab, false);
        })();

    </script>
</body>

</html>









