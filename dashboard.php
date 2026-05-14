<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];

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
$lowStockItems = [];
$lowStockToShow = [];

if ($role === 'admin') {
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
    $lowStockToShow = [];
    foreach ($lowStockItems as $item) {
        if ((int) $item['total_quantity'] <= 10) {
            $lowStockToShow[] = $item;
        }
    }
}

$isAdmin = ($role === 'admin');
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
$medlogPageHeader = [
    'title' => 'Dashboard',
    'subtitle' => "Welcome back! Here's your clinic overview.",
    'icon' => 'dashboard',
    'class' => 'medlog-page-header--dashboard',
    'actions' => $__dashboardHeaderActions,
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedLog Dashboard</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="Css/dashboard.css">
    <link rel="stylesheet" href="Css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body<?= $role === 'student' ? ' class="medlog-student-shell"' : '' ?>>

    <div class="dashboard">

        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">

            <?php include 'includes/header.php'; ?>

            <section class="content">

                <?php include 'includes/medlog-page-header.php'; ?>

                <!-- 🔴 ADMIN DASHBOARD -->
                <?php if ($role === 'admin'): ?>

                    <div class="grid-4">

                        <div class="card stat danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <h2><?= $totalLowStock ?></h2>
                                <p>Low Stock</p>
                            </div>
                        </div>

                        <div class="card stat">
                            <i class="fas fa-heartbeat"></i>
                            <div>
                                <h2>
                                    <?= $totalVisits ?>
                                </h2>
                                <p>Visits</p>
                            </div>
                        </div>

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



                    </div>

                    <div class="grid-2">

                        <div class="card">
                            <div class="tabs-header">
                                <h3>Agenda</h3>
                                <div class="tabs">
                                    <a class="tab-btn <?= $activeTab === 'visits' ? 'active' : '' ?>" data-tab="visits"
                                        href="?date=<?= urlencode($selectedDate) ?>&tab=visits">
                                        Visits
                                    </a>
                                    <a class="tab-btn <?= $activeTab === 'appointments' ? 'active' : '' ?>"
                                        data-tab="appointments"
                                        href="?date=<?= urlencode($selectedDate) ?>&tab=appointments">
                                        Appointments
                                    </a>
                                </div>
                            </div>

                            <!-- VISITS PANEL -->
                            <div id="panel-visits" class="agenda-panel <?= $activeTab === 'visits' ? 'active' : '' ?>">
                                <?php if ($recentVisits): ?>
                                    <?php foreach ($recentVisits as $visit): ?>
                                        <div class="list-item">
                                            <div>
                                                <strong><?= htmlspecialchars($visit['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <p><?= htmlspecialchars($visit['complaint'], ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                            <div class="visit-meta">
                                                <div><?= date("M d, Y", strtotime($visit['visit_date'])) ?></div>
                                                <div class="visit-time"><?= date("h:i A", strtotime($visit['visit_date'])) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="empty-state">No visits on this day.</p>
                                <?php endif; ?>
                            </div>

                            <!-- APPOINTMENTS PANEL -->
                            <div id="panel-appointments"
                                class="agenda-panel <?= $activeTab === 'appointments' ? 'active' : '' ?>">
                                <?php if ($dayAppointments): ?>
                                    <?php foreach ($dayAppointments as $appointment): ?>
                                        <?php
                                        $statusClass = 'pending';
                                        $apSt = $appointment['status'] ?? '';
                                        if ($apSt === 'Approved')
                                            $statusClass = 'approved';
                                        elseif ($apSt === 'Rejected')
                                            $statusClass = 'rejected';
                                        elseif ($apSt === 'Cancelled')
                                            $statusClass = 'cancelled';
                                        elseif ($apSt === 'Completed')
                                            $statusClass = 'completed';
                                        elseif ($apSt === 'Missed')
                                            $statusClass = 'missed';
                                        elseif ($apSt === 'Rescheduled')
                                            $statusClass = 'rescheduled';
                                        ?>
                                        <div class="list-item">
                                            <div>
                                                <strong><?= htmlspecialchars($appointment['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <p><?= htmlspecialchars($appointment['reason'], ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                            <div class="visit-meta">
                                                <div><?= htmlspecialchars($appointment['appointment_time'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div class="status-pill <?= $statusClass ?>">
                                                    <?= htmlspecialchars($appointment['status'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="empty-state">No appointments on this day.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="tabs-header">
                                <h3>Low Stock</h3>
                            </div>
                            <?php if ($lowStockToShow): ?>
                                <?php foreach ($lowStockToShow as $item):
                                    $qty = (int) $item['total_quantity'];
                                    ?>

                                    <div class="list-item danger-bg">
                                        <div>
                                            <strong><?= htmlspecialchars($item['medicine_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </div>
                                        <div><?= $qty ?></div>
                                    </div>

                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="empty-state">No medicines are low on stock.</p>
                            <?php endif; ?>

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

        const calendarInput = document.getElementById('calendarFilter');
        calendarInput.addEventListener('change',function () {
            const params = new URLSearchParams(window.location.search);
            params.set('date',this.value);
            if (!params.get('tab')) {
                params.set('tab','visits');
            }
            window.location.search = params.toString();
        });

        function openCalendar() {
            const input = document.getElementById('calendarFilter');

            // focus is important for some browsers
            input.focus();

            if (input.showPicker) {
                input.showPicker();
            } else {
                input.click(); // fallback
            }
        }
        document.addEventListener('DOMContentLoaded',function () {
            const tabButtons = document.querySelectorAll('.tabs .tab-btn[data-tab]');
            const panelVisits = document.getElementById('panel-visits');
            const panelAppointments = document.getElementById('panel-appointments');

            if (!tabButtons.length || !panelVisits || !panelAppointments) return;

            function setActiveTab(tab,updateUrl = true) {
                tabButtons.forEach(btn => {
                    btn.classList.toggle('active',btn.dataset.tab === tab);
                });

                panelVisits.classList.toggle('active',tab === 'visits');
                panelAppointments.classList.toggle('active',tab === 'appointments');

                if (updateUrl) {
                    const params = new URLSearchParams(window.location.search);
                    params.set('tab',tab);
                    history.replaceState({},'',`${window.location.pathname}?${params.toString()}`);
                }
            }

            tabButtons.forEach(btn => {
                btn.addEventListener('click',function (e) {
                    e.preventDefault(); // stop full page reload
                    setActiveTab(this.dataset.tab,true);
                });
            });
        });

    </script>
</body>

</html>