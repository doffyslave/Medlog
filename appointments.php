<?php
session_start();
require 'Database/connection.php';

if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['user_id'];


// ================= ADD =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $stmt = $conn->prepare("
        INSERT INTO appointments (user_id, appointment_date, appointment_time, reason)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $_POST['appointment_date'],
        $_POST['appointment_time'],
        $_POST['reason']
    ]);

    header("Location: appointments.php");
    exit();
}


// ================= ADMIN UPDATE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $role === 'admin') {

    // Get current status
    $check = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ?");
    $check->execute([$_POST['appointment_id']]);
    $currentStatus = $check->fetchColumn();

    // ❌ Do NOT allow editing cancelled
    if ($currentStatus !== 'Cancelled') {

        // ❌ Admin cannot set Cancelled
        $allowed = ['Pending', 'Approved', 'Rejected'];

        if (in_array($_POST['status'], $allowed)) {
            $stmt = $conn->prepare("
                UPDATE appointments 
                SET status = ?, admin_note = ?
                WHERE appointment_id = ?
            ");
            $stmt->execute([
                $_POST['status'],
                $_POST['admin_note'] ?? null,
                $_POST['appointment_id']
            ]);
        }
    }

    header("Location: appointments.php");
    exit();
}


// ================= STUDENT CANCEL =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment']) && $role === 'student') {
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = 'Cancelled'
        WHERE appointment_id = ? AND user_id = ?
    ");
    $stmt->execute([
        $_POST['appointment_id'],
        $user_id
    ]);

    header("Location: appointments.php");
    exit();
}


// ================= FETCH =================
if ($role === 'admin') {
    $stmt = $conn->query("
        SELECT a.*, u.name 
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        ORDER BY 
            FIELD(a.status, 'Pending', 'Approved', 'Rejected', 'Cancelled'),
            a.created_at DESC
    ");
    $appointments = $stmt->fetchAll();
} else {
    $stmt = $conn->prepare("
        SELECT * FROM appointments 
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments</title>

    <link rel="stylesheet" href="/Medlog/Css/layout.css">
    <link rel="stylesheet" href="/Medlog/Css/dashboard.css">
    <link rel="stylesheet" href="/Medlog/Css/appointments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

<div class="dashboard">

<?php include 'includes/sidebar.php'; ?>

<main class="main-content">

<?php include 'includes/header.php'; ?>

<section class="content">

<h1>Appointments</h1>

<!-- ================= STUDENT FORM ================= -->
<?php if ($role === 'student'): ?>
<div class="card">
    <h3>Request Appointment</h3>

    <form method="POST" class="appointment-form">
        <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required>
        <input type="time" name="appointment_time" required>
        <textarea name="reason" placeholder="Describe your concern..." required></textarea>

        <button type="submit" name="add_appointment" class="btn-primary">
            Request Appointment
        </button>
    </form>
</div>
<?php endif; ?>


<!-- ================= LIST ================= -->
<div class="card">
<h3><?= $role === 'admin' ? 'All Appointments' : 'My Appointments' ?></h3>

<?php if (empty($appointments)): ?>
    <p class="empty-state">
        <?= $role === 'admin' ? 'No appointment requests yet.' : 'You have no appointments yet.' ?>
    </p>
<?php endif; ?>

<?php foreach ($appointments as $a): ?>

<?php
$statusClass = 'badge-pending';
if ($a['status'] === 'Approved') $statusClass = 'badge-approved';
if ($a['status'] === 'Rejected') $statusClass = 'badge-rejected';
if ($a['status'] === 'Cancelled') $statusClass = 'badge-cancelled';

$dateFormatted = date("M d, Y", strtotime($a['appointment_date']));
$timeFormatted = date("h:i A", strtotime($a['appointment_time']));
?>

<div class="appointment-card <?= $a['status'] === 'Cancelled' ? 'cancelled-card' : '' ?>">

    <!-- LEFT -->
    <div class="appointment-left">

        <?php if ($role === 'admin'): ?>
            <strong><?= htmlspecialchars($a['name']) ?></strong>
        <?php endif; ?>

        <div><?= $dateFormatted ?> | <?= $timeFormatted ?></div>
        <div><?= htmlspecialchars($a['reason']) ?></div>

        <?php if (!empty($a['admin_note'])): ?>
            <div><strong>Note:</strong> <?= htmlspecialchars($a['admin_note']) ?></div>
        <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div class="appointment-right">

        <span class="badge <?= $statusClass ?>">
            <?= $a['status'] ?>
        </span>

        <!-- STUDENT CANCEL -->
        <?php if ($role === 'student' && $a['status'] === 'Pending'): ?>
        <form method="POST">
            <input type="hidden" name="appointment_id" value="<?= $a['appointment_id'] ?>">
            <button type="submit" name="cancel_appointment" class="btn-danger">
                Cancel
            </button>
        </form>
        <?php endif; ?>

        <!-- ADMIN CONTROL -->
        <?php if ($role === 'admin' && $a['status'] !== 'Cancelled'): ?>
        <form method="POST" class="admin-actions">
            <input type="hidden" name="appointment_id" value="<?= $a['appointment_id'] ?>">

            <select name="status">
                <option value="Pending" <?= $a['status']=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Approved" <?= $a['status']=='Approved'?'selected':'' ?>>Approved</option>
                <option value="Rejected" <?= $a['status']=='Rejected'?'selected':'' ?>>Rejected</option>
            </select>

            <input 
                type="text" 
                name="admin_note" 
                placeholder="Optional note"
                value="<?= htmlspecialchars($a['admin_note'] ?? '') ?>"
            >

            <button type="submit" name="update_status" class="btn-primary">
                Update
            </button>
        </form>
        <?php endif; ?>

    </div>

</div>

<?php endforeach; ?>

</div>

</section>
</main>
</div>

</body>
</html>