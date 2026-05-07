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


// ================= FIXED TIME SLOTS =================
$timeSlots = [
    "08:00 AM",
    "08:30 AM",
    "09:00 AM",
    "09:30 AM",
    "10:00 AM",
    "10:30 AM",
    "11:00 AM",
    "11:30 AM",
    "01:00 PM",
    "01:30 PM",
    "02:00 PM",
    "02:30 PM",
    "03:00 PM",
    "03:30 PM",
    "04:00 PM"
];


// ================= ADD APPOINTMENT =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {

    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);

    // CHECK SLOT
    $check = $conn->prepare("
        SELECT COUNT(*)
        FROM appointments
        WHERE appointment_date = ?
        AND appointment_time = ?
        AND status IN ('Pending', 'Approved')
    ");

    $check->execute([$date, $time]);

    $slotTaken = $check->fetchColumn();

    if ($slotTaken > 0) {

        $_SESSION['appointment_error'] = "This time slot is already booked.";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO appointments
            (user_id, appointment_date, appointment_time, reason)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $date,
            $time,
            $reason
        ]);

        $_SESSION['appointment_success'] = "Appointment requested successfully.";
    }

    header("Location: appointments.php");
    exit();
}



// ================= ADMIN UPDATE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_status'])
    && $role === 'admin') {

    $appointment_id = $_POST['appointment_id'];

    // CHECK CURRENT STATUS
    $check = $conn->prepare("
        SELECT status
        FROM appointments
        WHERE appointment_id = ?
    ");

    $check->execute([$appointment_id]);

    $currentStatus = $check->fetchColumn();

    // DO NOT EDIT CANCELLED
    if ($currentStatus !== 'Cancelled') {

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
                $appointment_id
            ]);
        }
    }

    header("Location: appointments.php");
    exit();
}



// ================= STUDENT CANCEL =================
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cancel_appointment'])
    && $role === 'student') {

    $stmt = $conn->prepare("
        UPDATE appointments
        SET status = 'Cancelled'
        WHERE appointment_id = ?
        AND user_id = ?
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
            a.appointment_date ASC,
            STR_TO_DATE(a.appointment_time, '%h:%i %p') ASC
    ");

    $appointments = $stmt->fetchAll();

} else {

    $stmt = $conn->prepare("
        SELECT *
        FROM appointments
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$user_id]);

    $appointments = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Appointments</title>

    <link rel="stylesheet" href="/MedLog/Css/layout.css">
    <link rel="stylesheet" href="/MedLog/Css/dashboard.css">
    <link rel="stylesheet" href="/MedLog/Css/appointments.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>

<div class="dashboard">

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include 'includes/header.php'; ?>

        <section class="content">

            <h1>Appointments</h1>


            <!-- ALERTS -->
            <?php if (isset($_SESSION['appointment_error'])): ?>

    <div class="alert-error">

        <div class="alert-content">

            <i class="fa-solid fa-circle-exclamation"></i>

            <div>
                <strong>Time Slot Unavailable</strong>
                <p>
                    Another student already booked this schedule.
                    Please choose a different time slot.
                </p>
            </div>

        </div>

    </div>

    <?php unset($_SESSION['appointment_error']); ?>

<?php endif; ?>


            <?php if (isset($_SESSION['appointment_success'])): ?>

                <div class="alert-success">
                    <?= $_SESSION['appointment_success']; ?>
                </div>

                <?php unset($_SESSION['appointment_success']); ?>

            <?php endif; ?>



            <!-- ================= ADMIN BULLETIN ================= -->
            <?php if ($role === 'admin'): ?>

            <div class="card">

                <h3>Today's Appointments</h3>

                <?php
                $today = date('Y-m-d');

                $todayStmt = $conn->prepare("
                    SELECT a.*, u.name
                    FROM appointments a
                    JOIN users u ON a.user_id = u.user_id
                    WHERE a.appointment_date = ?
                    AND a.status IN ('Pending', 'Approved')

                    ORDER BY
                    STR_TO_DATE(a.appointment_time, '%h:%i %p') ASC
                ");

                $todayStmt->execute([$today]);

                $todayAppointments = $todayStmt->fetchAll();
                ?>

                <?php if (empty($todayAppointments)): ?>

                    <p class="empty-state">
                        No appointments today.
                    </p>

                <?php else: ?>

                    <div class="bulletin-board">

                        <?php foreach ($todayAppointments as $todayApp): ?>

                            <div class="bulletin-item">

    <div class="bulletin-left">

        <strong>
            <?= $todayApp['appointment_time'] ?>
        </strong>

        -

        <?= htmlspecialchars($todayApp['name']) ?>

    </div>


    <div class="bulletin-right">

        <?php if ($todayApp['status'] === 'Pending'): ?>

            <span class="badge badge-pending">
                Pending
            </span>

        <?php elseif ($todayApp['status'] === 'Approved'): ?>

            <span class="badge badge-approved">
                Approved
            </span>

        <?php endif; ?>

    </div>

</div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

            <?php endif; ?>



            <!-- ================= STUDENT FORM ================= -->
            <?php if ($role === 'student'): ?>

            <div class="card">

                <h3>Request Appointment</h3>

                <form method="POST" class="appointment-form">

                    <!-- DATE -->
                    <div class="form-group">

                        <label>Select Date</label>

                        <input
                            type="date"
                            name="appointment_date"
                            min="<?= date('Y-m-d') ?>"
                            required
                        >

                    </div>


                    <!-- TIME SLOT -->
                    <div class="form-group">

                        <label>Select Time Slot</label>

                        <select name="appointment_time" required>

                            <option value="">
                                Choose Time Slot
                            </option>

                            <?php foreach ($timeSlots as $slot): ?>

                                <option value="<?= $slot ?>">
                                    <?= $slot ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- REASON -->
                    <div class="form-group">

                        <label>Reason for Visit</label>

                        <textarea
                            name="reason"
                            placeholder="Describe your concern..."
                            required
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        name="add_appointment"
                        class="btn-primary"
                    >
                        Request Appointment
                    </button>

                </form>

            </div>

            <?php endif; ?>



            <!-- ================= APPOINTMENTS LIST ================= -->
            <div class="card">

                <h3>
                    <?= $role === 'admin'
                        ? 'All Appointments'
                        : 'My Appointments' ?>
                </h3>

                <?php if (empty($appointments)): ?>

                    <p class="empty-state">
                        No appointments found.
                    </p>

                <?php endif; ?>



                <?php foreach ($appointments as $a): ?>

                <?php

                $statusClass = 'badge-pending';

                if ($a['status'] === 'Approved') {
                    $statusClass = 'badge-approved';
                }

                if ($a['status'] === 'Rejected') {
                    $statusClass = 'badge-rejected';
                }

                if ($a['status'] === 'Cancelled') {
                    $statusClass = 'badge-cancelled';
                }

                $dateFormatted = date(
                    "M d, Y",
                    strtotime($a['appointment_date'])
                );

                ?>

                <div class="appointment-card <?= $a['status'] === 'Cancelled'
                    ? 'cancelled-card'
                    : '' ?>">

                    <!-- LEFT -->
                    <div class="appointment-left">

                        <?php if ($role === 'admin'): ?>

                            <strong class="student-name">
                                <?= htmlspecialchars($a['name']) ?>
                            </strong>

                        <?php endif; ?>


                        <div class="appointment-datetime">

                            <span>
                                <i class="fa-solid fa-calendar"></i>
                                <?= $dateFormatted ?>
                            </span>

                            <span>
                                <i class="fa-solid fa-clock"></i>
                                <?= $a['appointment_time'] ?>
                            </span>

                        </div>


                        <div class="appointment-reason">
                            <?= htmlspecialchars($a['reason']) ?>
                        </div>


                        <?php if (!empty($a['admin_note'])): ?>

                            <div class="appointment-note">

                                <strong>Note:</strong>

                                <?= htmlspecialchars($a['admin_note']) ?>

                            </div>

                        <?php endif; ?>

                    </div>



                    <!-- RIGHT -->
                    <div class="appointment-right">

                        <span class="badge <?= $statusClass ?>">
                            <?= $a['status'] ?>
                        </span>


                        <!-- STUDENT CANCEL -->
                        <?php if (
                            $role === 'student'
                            && $a['status'] === 'Pending'
                        ): ?>

                        <form method="POST">

                            <input
                                type="hidden"
                                name="appointment_id"
                                value="<?= $a['appointment_id'] ?>"
                            >

                            <button
                                type="submit"
                                name="cancel_appointment"
                                class="btn-danger"
                            >
                                Cancel
                            </button>

                        </form>

                        <?php endif; ?>



                        <!-- ADMIN ACTIONS -->
                        <?php if (
                            $role === 'admin'
                            && $a['status'] !== 'Cancelled'
                        ): ?>

                        <form method="POST" class="admin-actions">

                            <input
                                type="hidden"
                                name="appointment_id"
                                value="<?= $a['appointment_id'] ?>"
                            >

                            <select name="status">

                                <option
                                    value="Pending"
                                    <?= $a['status'] === 'Pending'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Pending
                                </option>

                                <option
                                    value="Approved"
                                    <?= $a['status'] === 'Approved'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Approved
                                </option>

                                <option
                                    value="Rejected"
                                    <?= $a['status'] === 'Rejected'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Rejected
                                </option>

                            </select>


                            <input
                                type="text"
                                name="admin_note"
                                placeholder="Optional note"
                                value="<?= htmlspecialchars($a['admin_note'] ?? '') ?>"
                            >


                            <button
                                type="submit"
                                name="update_status"
                                class="btn-primary"
                            >
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