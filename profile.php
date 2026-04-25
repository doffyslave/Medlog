<?php
session_start();
include 'Database/connection.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch visit summary
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_visits, MAX(visit_date) as last_visit 
    FROM visits 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$visit = $stmt->fetch(PDO::FETCH_ASSOC);

// ===== GENERATE STUDENT ID =====
$email = $userData['email'];

preg_match('/\.(\d+)@/', $email, $matches);
$studentNumber = $matches[1] ?? null;

$studentID = $studentNumber 
    ? '02-000' . substr($studentNumber, 0, 1) . '-' . substr($studentNumber, 1)
    : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile | MedLog</title>

<link rel="stylesheet" href="CSS/layout.css">
<link rel="stylesheet" href="CSS/dashboard.css">
<link rel="stylesheet" href="CSS/profile.css"> <!-- 🔥 NEW -->

</head>

<body>

<div class="dashboard">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

<?php include 'includes/header.php'; ?>

<div class="profile-container">

    <!-- 🔹 PROFILE HEADER -->
    <div class="profile-card header-card">
        <img src="Images/UserIcon.jpg" alt="Profile">

        <div>
            <h2><?= htmlspecialchars($userData['name']) ?></h2>
            <p class="role"><?= ucfirst($userData['role']) ?></p>
            <span class="student-id"><?= $studentID ?></span>
        </div>
    </div>

    <!-- 🔹 PERSONAL INFO -->
    <div class="profile-card">
        <h3>Personal Information</h3>

        <div class="info-grid">

            <div class="info-item">
                <span>Email</span>
                <p><?= htmlspecialchars($userData['email']) ?></p>
            </div>

            <div class="info-item">
                <span>Course</span>
                <p><?= htmlspecialchars($userData['course'] ?? 'N/A') ?></p>
            </div>

            <div class="info-item">
                <span>Year Level</span>
                <p><?= htmlspecialchars($userData['year_level'] ?? 'N/A') ?></p>
            </div>

            <div class="info-item">
                <span>Role</span>
                <p><?= ucfirst($userData['role']) ?></p>
            </div>

        </div>
    </div>

    <!-- 🔹 VISIT SUMMARY -->
    <div class="profile-card">
        <h3>My Visits</h3>

        <div class="visit-summary">
            <div>
                <span>Total Visits</span>
                <h2><?= $visit['total_visits'] ?? 0 ?></h2>
            </div>

            <div>
                <span>Last Visit</span>
                <p>
                    <?= $visit['last_visit'] 
                        ? date("M d, Y h:i A", strtotime($visit['last_visit'])) 
                        : 'No visits yet' ?>
                </p>
            </div>
        </div>

        <a href="my_visits.php" class="view-btn">View All Visits →</a>
    </div>

</div>

</main>
</div>

</body>
</html>