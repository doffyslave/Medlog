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
?>

<div class="profile-wrapper">

    <!-- HEADER -->
    <div class="profile-header">
        <h2><?= htmlspecialchars($user['name']) ?></h2>
        <span class="profile-role"><?= ucfirst($user['role']) ?></span>
    </div>

    <!-- BASIC INFO -->
    <div class="profile-section">
        <h4>General Information</h4>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        

        <!-- 🔥 ADD THIS HERE -->
        <p><strong>Student ID:</strong> <?= $user['student_id'] ?? 'N/A' ?></p>

        <p><strong>Course:</strong> <?= $user['course'] ?: 'N/A' ?></p>
        <p><strong>Year Level:</strong> <?= $user['year_level'] ?: 'N/A' ?></p>
        <p><strong>Status:</strong> <?= ucfirst($user['status']) ?></p>
    </div>

    <!-- VISIT STATS -->
    <div class="profile-section">
        <h4>Visit Summary</h4>
        <p><strong>Total Visits:</strong> <?= $user['total_visits'] ?></p>
        <p><strong>Last Visit:</strong> 
            <?= $user['last_visit'] ? date("M d, Y", strtotime($user['last_visit'])) : 'No visits yet' ?>
        </p>
    </div>

    <!-- VISIT HISTORY -->
    <div class="profile-section">
        <h4>Visit History</h4>

        <?php if (count($visits) > 0): ?>
            <div class="visit-list">
                <?php foreach($visits as $visit): ?>
                    <div class="visit-item">
                        <div class="visit-date">
                            <?= date("M d, Y", strtotime($visit['visit_date'])) ?>
                        </div>

                        <div class="visit-info">
                            <strong><?= htmlspecialchars($visit['complaint']) ?></strong><br>
                            <small><?= htmlspecialchars($visit['treatment']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No visit records.</p>
        <?php endif; ?>

    </div>

</div>