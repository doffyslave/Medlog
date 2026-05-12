<?php
include 'connection.php';

if (!isset($_GET['id'])) {
    echo "No patient selected.";
    exit();
}

$user_id = $_GET['id'];

/* ===== GET USER + STATS ===== */
$stmt = $conn->prepare("
SELECT 
    u.*,
    COUNT(v.visit_id) AS total_visits,
    MAX(v.visit_date) AS last_visit
FROM users u
LEFT JOIN visits v ON u.user_id = v.user_id
WHERE u.user_id = ?
GROUP BY u.user_id
");

$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Patient not found.";
    exit();
}

/* ===== GET VISIT HISTORY ===== */
$visitsStmt = $conn->prepare("
SELECT * FROM visits
WHERE user_id = ?
ORDER BY visit_date DESC
");

$visitsStmt->execute([$user_id]);
$visits = $visitsStmt->fetchAll(PDO::FETCH_ASSOC);

$appointmentCount = null;
try {
    $appointmentStmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE user_id = ?");
    $appointmentStmt->execute([$user_id]);
    $appointmentCount = (int) $appointmentStmt->fetchColumn();
} catch (Exception $e) {
    $appointmentCount = null;
}

$displayName = htmlspecialchars($user['name']);
$displayRole = ucfirst($user['role']);
$displayStatus = ucfirst($user['status']);
$displayEmail = htmlspecialchars($user['email']);
$displayStudentId = htmlspecialchars($user['student_id'] ?? 'N/A');
$displayCourse = htmlspecialchars($user['course'] ?: 'N/A');
$displayYear = htmlspecialchars($user['year_level'] ?: 'N/A');
$totalVisits = (int) ($user['total_visits'] ?? 0);
$lastVisitDisplay = $user['last_visit'] ? date("M d, Y", strtotime($user['last_visit'])) : "No visits yet";
$avatar = strtoupper(substr(trim($user['name']), 0, 1));
?>

<aside class="patient-drawer">
    <div class="drawer-head">
        <div class="drawer-identity">
            <div class="drawer-avatar"><?= $avatar ?></div>
            <div class="drawer-identity-meta">
                <h2><?= $displayName ?></h2>
                <div class="drawer-badges">
                    <span class="drawer-badge role"><?= $displayRole ?></span>
                    <span class="drawer-badge status <?= strtolower($displayStatus) ?>"><?= $displayStatus ?></span>
                </div>
            </div>
        </div>
        <button onclick="closeProfile()" class="close-profile" title="Close panel" aria-label="Close panel">✕</button>
    </div>

    <section class="drawer-section">
        <h3>Profile Information</h3>
        <div class="drawer-info-grid">
            <article class="drawer-info-item">
                <span>Email</span>
                <strong><?= $displayEmail ?></strong>
            </article>
            <article class="drawer-info-item">
                <span>Student ID</span>
                <strong><?= $displayStudentId ?></strong>
            </article>
            <article class="drawer-info-item">
                <span>Course</span>
                <strong><?= $displayCourse ?></strong>
            </article>
            <article class="drawer-info-item">
                <span>Year Level</span>
                <strong><?= $displayYear ?></strong>
            </article>
        </div>
    </section>

    <section class="drawer-section">
        <h3>Visit Analytics</h3>
        <div class="drawer-metrics">
            <article class="metric-card">
                <span>Total Visits</span>
                <strong><?= $totalVisits ?></strong>
            </article>
            <article class="metric-card">
                <span>Last Visit</span>
                <strong><?= htmlspecialchars($lastVisitDisplay) ?></strong>
            </article>
            <article class="metric-card">
                <span>Appointments</span>
                <strong><?= $appointmentCount !== null ? $appointmentCount : 'N/A' ?></strong>
            </article>
        </div>
    </section>

    <section class="drawer-section">
        <h3>Recent Activity</h3>
        <?php if (count($visits) > 0): ?>
            <div class="activity-list">
                <?php foreach (array_slice($visits, 0, 6) as $visit): ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-body">
                            <strong><?= date("M d, Y", strtotime($visit['visit_date'])) ?></strong>
                            <p><?= htmlspecialchars($visit['complaint']) ?></p>
                            <small><?= htmlspecialchars($visit['treatment']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-activity">No visit records yet.</p>
        <?php endif; ?>
    </section>

    <div class="drawer-actions">
        <a href="visits.php?user_id=<?= (int) $user_id ?>" class="drawer-action primary">
            View Full Visits
        </a>
        <button type="button" class="drawer-action secondary" onclick="closeProfile()">
            Close
        </button>
    </div>
</aside>