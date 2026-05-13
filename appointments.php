<?php
session_start();
date_default_timezone_set('Asia/Manila');
require 'Database/connection.php';
require_once __DIR__ . '/includes/appointments_lib.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
$role = $user['role'];
$user_id = $user['user_id'];

$isAdmin = $role === 'admin';
$isBooker = in_array($role, ['student', 'teacher'], true);

if (!$isAdmin && !$isBooker) {
    header('Location: dashboard.php');
    exit();
}

appt_ensure_schema($conn);
appt_mark_past_approved_missed($conn);

$timeSlots = appt_time_slots();
$apptErrSlotTaken = 'This time slot is already booked. Please choose another slot.';

// ================= STUDENT / TEACHER BOOK =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment']) && $isBooker) {
    if (!appt_form_token_verify('appt_student_book', $_POST['appt_book_token'] ?? null)) {
        $_SESSION['appointment_error'] = 'This form has expired. Please refresh the page and try again.';
        header('Location: appointments.php');
        exit();
    }
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $reason = trim((string) ($_POST['reason'] ?? ''));
    $timeCanon = appt_canonical_time($time);

    if ($date === '' || $time === '' || $reason === '') {
        $_SESSION['appointment_error'] = 'Please choose a date, time slot, and reason.';
    } elseif ($timeCanon === null || !in_array($timeCanon, $timeSlots, true)) {
        $_SESSION['appointment_error'] = 'Invalid time slot.';
    } elseif (appt_slot_conflict_count($conn, $date, $timeCanon, null) > 0) {
        $_SESSION['appointment_error'] = $apptErrSlotTaken;
    } else {
        try {
            $stmt = $conn->prepare('
                INSERT INTO appointments (user_id, appointment_date, appointment_time, reason, status)
                VALUES (?, ?, ?, ?, \'Pending\')
            ');
            $stmt->execute([$user_id, $date, $timeCanon, $reason]);
            $_SESSION['appointment_success'] = 'Appointment requested successfully.';
        } catch (Throwable $e) {
            $_SESSION['appointment_error'] = 'Failed to create appointment.';
        }
    }
    header('Location: appointments.php');
    exit();
}

// ================= ADMIN CREATE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_create_appointment']) && $isAdmin) {
    if (!appt_form_token_verify('appt_admin_create', $_POST['appt_admin_create_token'] ?? null)) {
        $_SESSION['appointment_error'] = 'This form has expired. Please refresh the page and try again.';
        header('Location: appointments.php');
        exit();
    }
    $patientId = trim((string) ($_POST['patient_user_id'] ?? ''));
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $reason = trim((string) ($_POST['reason'] ?? ''));
    $timeCanon = appt_canonical_time($time);

    if ($patientId === '' || $date === '' || $time === '' || $reason === '') {
        $_SESSION['appointment_error'] = 'Please select a patient, date, time, and reason.';
    } elseif ($timeCanon === null || !in_array($timeCanon, $timeSlots, true)) {
        $_SESSION['appointment_error'] = 'Invalid time slot.';
    } elseif (appt_slot_conflict_count($conn, $date, $timeCanon, null) > 0) {
        $_SESSION['appointment_error'] = $apptErrSlotTaken;
    } else {
        $roleChk = $conn->prepare("
            SELECT user_id, status FROM users
            WHERE user_id = ? AND LOWER(TRIM(COALESCE(role,''))) <> 'admin'
        ");
        $roleChk->execute([$patientId]);
        $userRow = $roleChk->fetch(PDO::FETCH_ASSOC);
        if (!$userRow) {
            $_SESSION['appointment_error'] = 'Selected patient is invalid.';
        } elseif (appt_users_has_status_column($conn)) {
            $bookSql = appt_sql_user_bookable_condition();
            $bookParams = appt_user_bookable_blocklist_lower();
            $bookChk = $conn->prepare("
                SELECT COUNT(*) FROM users
                WHERE user_id = ? AND LOWER(TRIM(COALESCE(role,''))) <> 'admin'
                  AND {$bookSql}
            ");
            $bookChk->execute(array_merge([$patientId], $bookParams));
            if ((int) $bookChk->fetchColumn() < 1) {
                $_SESSION['appointment_error'] = 'Cannot create appointment for inactive user.';
            } else {
                try {
                    $stmt = $conn->prepare('
                        INSERT INTO appointments (user_id, appointment_date, appointment_time, reason, status)
                        VALUES (?, ?, ?, ?, \'Approved\')
                    ');
                    $stmt->execute([$patientId, $date, $timeCanon, $reason]);
                    $_SESSION['appointment_success'] = 'Appointment created and approved.';
                } catch (Throwable $e) {
                    $_SESSION['appointment_error'] = 'Failed to create appointment.';
                }
            }
        } else {
            try {
                $stmt = $conn->prepare('
                    INSERT INTO appointments (user_id, appointment_date, appointment_time, reason, status)
                    VALUES (?, ?, ?, ?, \'Approved\')
                ');
                $stmt->execute([$patientId, $date, $timeCanon, $reason]);
                $_SESSION['appointment_success'] = 'Appointment created and approved.';
            } catch (Throwable $e) {
                $_SESSION['appointment_error'] = 'Failed to create appointment.';
            }
        }
    }
    header('Location: appointments.php');
    exit();
}

// ================= ADMIN DISCRETE ACTIONS =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_appointment_action']) && $isAdmin) {
    $appointment_id = (int) ($_POST['appointment_id'] ?? 0);
    $action = $_POST['admin_action'] ?? '';
    $admin_note = trim((string) ($_POST['admin_note'] ?? ''));

    $stmt = $conn->prepare('SELECT appointment_id, status FROM appointments WHERE appointment_id = ?');
    $stmt->execute([$appointment_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $_SESSION['appointment_error'] = 'Appointment not found.';
    } else {
        $st = appt_normalize_status($row['status']);

        if ($action === 'approve' && $st === 'Pending') {
            if ($admin_note !== '') {
                $u = $conn->prepare('UPDATE appointments SET status = \'Approved\', admin_note = ? WHERE appointment_id = ? AND status = \'Pending\'');
                $u->execute([$admin_note, $appointment_id]);
            } else {
                $u = $conn->prepare('UPDATE appointments SET status = \'Approved\' WHERE appointment_id = ? AND status = \'Pending\'');
                $u->execute([$appointment_id]);
            }
            $_SESSION['appointment_success'] = 'Appointment approved.';
        } elseif ($action === 'reject' && $st === 'Pending') {
            if ($admin_note !== '') {
                $u = $conn->prepare('UPDATE appointments SET status = \'Rejected\', admin_note = ? WHERE appointment_id = ? AND status = \'Pending\'');
                $u->execute([$admin_note, $appointment_id]);
            } else {
                $u = $conn->prepare('UPDATE appointments SET status = \'Rejected\' WHERE appointment_id = ? AND status = \'Pending\'');
                $u->execute([$appointment_id]);
            }
            $_SESSION['appointment_success'] = 'Appointment rejected.';
        } elseif ($action === 'cancel' && appt_admin_can_cancel($st)) {
            if ($admin_note !== '') {
                $u = $conn->prepare('UPDATE appointments SET status = \'Cancelled\', admin_note = ? WHERE appointment_id = ?');
                $u->execute([$admin_note, $appointment_id]);
            } else {
                $u = $conn->prepare('UPDATE appointments SET status = \'Cancelled\' WHERE appointment_id = ?');
                $u->execute([$appointment_id]);
            }
            $_SESSION['appointment_success'] = 'Appointment cancelled.';
        } elseif ($action === 'complete' && in_array($st, ['Approved', 'Rescheduled'], true)) {
            $ft = $conn->prepare('SELECT appointment_date, appointment_time FROM appointments WHERE appointment_id = ?');
            $ft->execute([$appointment_id]);
            $fd = $ft->fetch(PDO::FETCH_ASSOC);
            $slotStart = $fd ? appt_slot_start_immutable((string) $fd['appointment_date'], (string) $fd['appointment_time']) : null;
            $nowAppt = new DateTimeImmutable('now', appt_timezone());
            $isFutureSlot = $slotStart !== null && $nowAppt < $slotStart;
            if ($isFutureSlot && ($_POST['confirm_early_complete'] ?? '') !== '1') {
                $_SESSION['appointment_error'] = 'This appointment is still in the future. Confirm in the dialog to mark it completed anyway.';
            } else {
                if ($admin_note !== '') {
                    $u = $conn->prepare('UPDATE appointments SET status = \'Completed\', admin_note = ? WHERE appointment_id = ?');
                    $u->execute([$admin_note, $appointment_id]);
                } else {
                    $u = $conn->prepare('UPDATE appointments SET status = \'Completed\' WHERE appointment_id = ?');
                    $u->execute([$appointment_id]);
                }
                $_SESSION['appointment_success'] = 'Marked as completed.';
            }
        } elseif ($action === 'missed' && in_array($st, ['Approved', 'Rescheduled'], true)) {
            if ($admin_note !== '') {
                $u = $conn->prepare('UPDATE appointments SET status = \'Missed\', admin_note = ? WHERE appointment_id = ?');
                $u->execute([$admin_note, $appointment_id]);
            } else {
                $u = $conn->prepare('UPDATE appointments SET status = \'Missed\' WHERE appointment_id = ?');
                $u->execute([$appointment_id]);
            }
            $_SESSION['appointment_success'] = 'Marked as missed.';
        } else {
            $_SESSION['appointment_error'] = 'That action is not allowed for the current status.';
        }
    }
    header('Location: appointments.php');
    exit();
}

// ================= ADMIN RESCHEDULE =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_reschedule']) && $isAdmin) {
    if (!appt_form_token_verify('appt_reschedule', $_POST['appt_reschedule_token'] ?? null)) {
        $_SESSION['appointment_error'] = 'This form has expired. Please refresh the page and try again.';
        header('Location: appointments.php');
        exit();
    }
    $appointment_id = (int) ($_POST['appointment_id'] ?? 0);
    $newDate = $_POST['new_appointment_date'] ?? '';
    $newTime = $_POST['new_appointment_time'] ?? '';
    $reschedule_note = trim((string) ($_POST['reschedule_note'] ?? ''));
    $newTimeCanon = appt_canonical_time($newTime);

    $stmt = $conn->prepare('SELECT * FROM appointments WHERE appointment_id = ?');
    $stmt->execute([$appointment_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $_SESSION['appointment_error'] = 'Appointment not found.';
    } else {
        $st = appt_normalize_status($row['status']);
        if (!in_array($st, ['Pending', 'Approved', 'Rescheduled'], true)) {
            $_SESSION['appointment_error'] = 'This appointment cannot be rescheduled.';
        } elseif ($newDate === '' || $newTime === '') {
            $_SESSION['appointment_error'] = 'Choose a new date and time.';
        } elseif ($newTimeCanon === null || !in_array($newTimeCanon, $timeSlots, true)) {
            $_SESSION['appointment_error'] = 'Invalid time slot.';
        } elseif (appt_slot_conflict_count($conn, $newDate, $newTimeCanon, $appointment_id) > 0) {
            $_SESSION['appointment_error'] = $apptErrSlotTaken;
        } else {
            $noteVal = $reschedule_note !== ''
                ? $reschedule_note
                : ($row['reschedule_note'] ?? null);
            $u = $conn->prepare('
                UPDATE appointments
                SET appointment_date = ?, appointment_time = ?, status = \'Rescheduled\',
                    reschedule_note = ?
                WHERE appointment_id = ?
            ');
            $u->execute([$newDate, $newTimeCanon, $noteVal, $appointment_id]);
            $_SESSION['appointment_success'] = 'Appointment rescheduled.';
        }
    }
    header('Location: appointments.php');
    exit();
}

// ================= STUDENT / TEACHER CANCEL =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment']) && $isBooker) {
    $aid = (int) ($_POST['appointment_id'] ?? 0);
    $stmt = $conn->prepare('SELECT status FROM appointments WHERE appointment_id = ? AND user_id = ?');
    $stmt->execute([$aid, $user_id]);
    $st = $stmt->fetchColumn();
    if ($st === false) {
        $_SESSION['appointment_error'] = 'Appointment not found.';
    } else {
        $st = appt_normalize_status((string) $st);
        if (!appt_student_can_cancel($st)) {
            $_SESSION['appointment_error'] = 'This appointment cannot be cancelled.';
        } else {
            $u = $conn->prepare('UPDATE appointments SET status = \'Cancelled\' WHERE appointment_id = ? AND user_id = ?');
            $u->execute([$aid, $user_id]);
            $_SESSION['appointment_success'] = 'Appointment cancelled.';
        }
    }
    header('Location: appointments.php');
    exit();
}

// ================= FETCH DATA =================
$startBooked = date('Y-m-d');
$endBooked = date('Y-m-d', strtotime('+120 days'));
$bookedByDate = [];
try {
    $reservedCond = appt_sql_slot_reserved_condition();
    $q = $conn->prepare("
        SELECT appointment_date, appointment_time
        FROM appointments
        WHERE appointment_date >= ? AND appointment_date <= ?
          AND {$reservedCond}
    ");
    $q->execute(array_merge([$startBooked, $endBooked], appt_reserved_statuses_lower()));
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $d = $b['appointment_date'];
        if (!isset($bookedByDate[$d])) {
            $bookedByDate[$d] = [];
        }
        $tc = appt_canonical_time((string) ($b['appointment_time'] ?? ''));
        if ($tc !== null) {
            $bookedByDate[$d][] = $tc;
        }
    }
} catch (Throwable $e) {
}

foreach ($bookedByDate as $bk => $times) {
    $bookedByDate[$bk] = array_values(array_unique($times));
}

$bookedJson = json_encode($bookedByDate, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$patientOptions = [];
if ($isAdmin) {
    $pqSql = "
        SELECT user_id, name, role
        FROM users
        WHERE LOWER(TRIM(COALESCE(role,''))) <> 'admin'
    ";
    $pqParams = [];
    if (appt_users_has_status_column($conn)) {
        $pqSql .= ' AND ' . appt_sql_user_bookable_condition();
        $pqParams = appt_user_bookable_blocklist_lower();
    }
    $pqSql .= ' ORDER BY name ASC';
    $pq = $conn->prepare($pqSql);
    $pq->execute($pqParams);
    $patientOptions = $pq->fetchAll(PDO::FETCH_ASSOC);
}
$patientsJson = json_encode(array_map(static function ($r) {
    $name = trim((string) ($r['name'] ?? ''));
    $roleLabel = ucfirst(trim((string) ($r['role'] ?? '')));
    $label = $name;
    if ($roleLabel !== '' && stripos($name, '(' . $roleLabel . ')') === false) {
        $label .= ' (' . $roleLabel . ')';
    }
    return [
        'id' => (string) $r['user_id'],
        'label' => $label,
    ];
}, $patientOptions), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

if ($isAdmin) {
    $stmt = $conn->query("
        SELECT a.*, u.name
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        ORDER BY
            CASE a.status
                WHEN 'Pending' THEN 1
                WHEN 'Approved' THEN 2
                WHEN 'Rescheduled' THEN 3
                ELSE 4
            END,
            a.appointment_date ASC,
            COALESCE(
                STR_TO_DATE(a.appointment_time, '%h:%i %p'),
                STR_TO_DATE(a.appointment_time, '%H:%i:%s')
            ) ASC
    ");
    $appointments = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} else {
    $stmt = $conn->prepare('
        SELECT * FROM appointments
        WHERE user_id = ?
        ORDER BY appointment_date DESC,
            COALESCE(
                STR_TO_DATE(appointment_time, \'%h:%i %p\'),
                STR_TO_DATE(appointment_time, \'%H:%i:%s\')
            ) DESC
    ');
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($appointments as &$apptRow) {
    $apptRow['status'] = appt_normalize_status($apptRow['status'] ?? 'Pending');
}
unset($apptRow);

$slotsJson = json_encode($timeSlots, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$apptTokenStudent = $isBooker ? appt_form_token_issue('appt_student_book') : '';
$apptTokenAdminCreate = $isAdmin ? appt_form_token_issue('appt_admin_create') : '';
$apptTokenReschedule = $isAdmin ? appt_form_token_issue('appt_reschedule') : '';

$medlogPageHeader = [
    'title' => 'Appointments',
    'subtitle' => $isAdmin
        ? 'Review requests, approve schedules, and manage clinic visits.'
        : 'Book a clinic visit and track your appointments.',
    'icon' => 'appointments',
    'class' => 'medlog-page-header--appointments',
];

if ($isAdmin) {
    ob_start();
    echo '<button type="button" class="add-btn" id="openAdminCreateDrawer">+ Create appointment</button>';
    $medlogPageHeader['actions'] = ob_get_clean();
}

function appt_badge_class(string $status): string
{
    return match ($status) {
        'Approved' => 'badge-approved',
        'Rejected' => 'badge-rejected',
        'Cancelled' => 'badge-cancelled',
        'Completed' => 'badge-completed',
        'Missed' => 'badge-missed',
        'Rescheduled' => 'badge-rescheduled',
        default => 'badge-pending',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments</title>
    <link rel="stylesheet" href="Css/layout.css">
    <link rel="stylesheet" href="Css/dashboard.css">
    <link rel="stylesheet" href="Css/appointments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="dashboard">
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include 'includes/header.php'; ?>
        <section class="content appointments-page">
            <?php include 'includes/medlog-page-header.php'; ?>

            <?php if (!empty($_SESSION['appointment_error'])): ?>
                <div class="alert-error">
                    <div class="alert-content">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div>
                            <strong>Unable to process</strong>
                            <p><?= htmlspecialchars($_SESSION['appointment_error'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['appointment_error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['appointment_success'])): ?>
                <div class="alert-success"><?= htmlspecialchars($_SESSION['appointment_success'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php unset($_SESSION['appointment_success']); ?>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="card appt-bulletin-card">
                    <h3>Today's schedule</h3>
                    <?php
                    $today = date('Y-m-d');
                    $todayStmt = $conn->prepare("
                        SELECT a.*, u.name
                        FROM appointments a
                        JOIN users u ON a.user_id = u.user_id
                        WHERE a.appointment_date = ?
                          AND a.status IN ('Pending', 'Approved', 'Rescheduled')
                        ORDER BY COALESCE(
                            STR_TO_DATE(a.appointment_time, '%h:%i %p'),
                            STR_TO_DATE(a.appointment_time, '%H:%i:%s')
                        ) ASC
                    ");
                    $todayStmt->execute([$today]);
                    $todayAppointments = $todayStmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (empty($todayAppointments)): ?>
                        <p class="empty-state">No active appointments today.</p>
                    <?php else: ?>
                        <div class="bulletin-board">
                            <?php foreach ($todayAppointments as $ta): ?>
                                <?php
                                    $taTime = appt_canonical_time((string) ($ta['appointment_time'] ?? ''))
                                        ?? (string) ($ta['appointment_time'] ?? '');
                                ?>
                                <div class="bulletin-item">
                                    <div class="bulletin-left">
                                        <strong><?= htmlspecialchars($taTime, ENT_QUOTES, 'UTF-8') ?></strong>
                                        — <?= htmlspecialchars($ta['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="bulletin-right">
                                        <span class="badge <?= appt_badge_class(appt_normalize_status($ta['status'])) ?>">
                                            <?= htmlspecialchars(appt_normalize_status($ta['status']), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($isBooker): ?>
                <div class="appt-booking-shell card">
                    <h3 class="appt-booking-title">Book an appointment</h3>
                    <p class="appt-booking-lead">Pick a date, choose an open slot, then submit your visit reason.</p>
                    <form method="POST" class="appt-booking-grid" id="studentBookingForm">
                        <input type="hidden" name="appt_book_token" value="<?= htmlspecialchars($apptTokenStudent, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="appt-booking-col appt-booking-col--calendar">
                            <label class="appt-label" for="bookingDate">Date</label>
                            <input type="date" name="appointment_date" id="bookingDate" class="appt-date-input"
                                min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                                value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                                required>
                            <p class="appt-hint">Only future slots are shown. Booked times appear disabled.</p>
                        </div>
                        <div class="appt-booking-col appt-booking-col--slots">
                            <span class="appt-label">Available times</span>
                            <div class="appt-slot-grid" id="bookingSlotGrid" aria-live="polite"></div>
                            <input type="hidden" name="appointment_time" id="bookingTimeValue" value="">
                        </div>
                        <div class="appt-booking-col appt-booking-col--reason">
                            <label class="appt-label" for="bookingReason">Reason for visit</label>
                            <textarea name="reason" id="bookingReason" rows="4" placeholder="Describe symptoms or purpose of visit…" required></textarea>
                            <button type="submit" name="add_appointment" value="1" class="btn-primary appt-submit-btn" id="bookingSubmitBtn" disabled>
                                Request appointment
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="card appt-list-card">
                <h3><?= $isAdmin ? 'All appointments' : 'My appointments' ?></h3>
                <?php if (empty($appointments)): ?>
                    <p class="empty-state">No appointments yet.</p>
                <?php endif; ?>

                <?php
                $nowApptPage = new DateTimeImmutable('now', appt_timezone());
                foreach ($appointments as $a): ?>
                    <?php
                        $st = appt_normalize_status($a['status']);
                        $readonly = appt_is_readonly($st);
                        $dateFormatted = date('M d, Y', strtotime($a['appointment_date']));
                        $statusClass = appt_badge_class($st);
                        $timeDisplay = appt_canonical_time((string) ($a['appointment_time'] ?? ''))
                            ?? (string) ($a['appointment_time'] ?? '');
                        $slotStartCard = appt_slot_start_immutable(
                            (string) ($a['appointment_date'] ?? ''),
                            (string) ($a['appointment_time'] ?? '')
                        );
                        $completeIsFuture = $slotStartCard !== null && $nowApptPage < $slotStartCard;
                    ?>
                    <div class="appointment-card <?= in_array($st, ['Cancelled', 'Rejected'], true) ? 'cancelled-card' : '' ?> <?= $readonly ? 'appt-readonly' : '' ?>">
                        <div class="appointment-left">
                            <?php if ($isAdmin): ?>
                                <strong class="student-name"><?= htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php endif; ?>
                            <div class="appointment-datetime">
                                <span><i class="fa-solid fa-calendar"></i> <?= htmlspecialchars($dateFormatted, ENT_QUOTES, 'UTF-8') ?></span>
                                <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($timeDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="appointment-reason"><?= htmlspecialchars($a['reason'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($a['admin_note'])): ?>
                                <div class="appointment-note"><strong>Staff note:</strong> <?= htmlspecialchars($a['admin_note'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                            <?php if (!empty($a['reschedule_note'])): ?>
                                <div class="appointment-note appt-reschedule-note"><strong>Reschedule:</strong> <?= htmlspecialchars($a['reschedule_note'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="appointment-right">
                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>

                            <?php if ($isBooker && !$readonly && appt_student_can_cancel($st)): ?>
                                <form method="POST" class="appt-inline-form" onsubmit="return confirm('Cancel this appointment?');">
                                    <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                    <button type="submit" name="cancel_appointment" value="1" class="btn-danger">Cancel</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($isAdmin && !$readonly): ?>
                                <div class="admin-action-bar">
                                    <?php if ($st === 'Pending'): ?>
                                        <form method="POST" class="appt-inline-form appt-admin-action-form">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <input type="hidden" name="admin_action" value="approve">
                                            <button type="submit" name="admin_appointment_action" value="1" class="btn-primary btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" class="appt-inline-form appt-admin-action-form">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <input type="hidden" name="admin_action" value="reject">
                                            <button type="submit" name="admin_appointment_action" value="1" class="btn-danger btn-sm">Reject</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($st, ['Approved', 'Rescheduled'], true)): ?>
                                        <form method="POST" class="appt-inline-form appt-admin-action-form" data-complete-future="<?= $completeIsFuture ? '1' : '0' ?>">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <input type="hidden" name="admin_action" value="complete">
                                            <button type="submit" name="admin_appointment_action" value="1" class="btn-primary btn-sm">Mark completed</button>
                                        </form>
                                        <form method="POST" class="appt-inline-form appt-admin-action-form">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <input type="hidden" name="admin_action" value="missed">
                                            <button type="submit" name="admin_appointment_action" value="1" class="btn-danger btn-sm">Mark missed</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($st, ['Pending', 'Approved', 'Rescheduled'], true)): ?>
                                        <?php
                                            $resCanon = appt_canonical_time((string) ($a['appointment_time'] ?? '')) ?? '';
                                        ?>
                                        <button type="button" class="btn-secondary btn-sm appt-reschedule-open"
                                            data-id="<?= (int) $a['appointment_id'] ?>"
                                            data-date="<?= htmlspecialchars($a['appointment_date'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-time="<?= htmlspecialchars($resCanon, ENT_QUOTES, 'UTF-8') ?>">
                                            Reschedule
                                        </button>
                                    <?php endif; ?>

                                    <?php if (appt_admin_can_cancel($st)): ?>
                                        <form method="POST" class="appt-inline-form appt-admin-action-form">
                                            <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                            <input type="hidden" name="admin_action" value="cancel">
                                            <button type="submit" name="admin_appointment_action" value="1" class="btn-danger btn-sm">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>

<?php if ($isAdmin): ?>
<div class="appt-overlay" id="apptOverlay" hidden></div>
<div class="appt-drawer" id="adminCreateDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="adminCreateTitle">
    <button type="button" class="appt-drawer__close" id="adminCreateClose" aria-label="Close">&times;</button>
    <h2 id="adminCreateTitle">Create appointment</h2>
    <p class="appt-drawer__lead">Schedule for a student or teacher. The slot is confirmed as approved.</p>
    <form method="POST" class="appt-drawer-form" id="adminCreateApptForm" autocomplete="off">
        <input type="hidden" name="appt_admin_create_token" value="<?= htmlspecialchars($apptTokenAdminCreate, ENT_QUOTES, 'UTF-8') ?>">
        <label for="adminPatientSearch">Patient</label>
        <div class="medlog-combo" id="adminPatientCombo">
            <input type="text" id="adminPatientSearch" name="admin_patient_search_display" autocomplete="off" placeholder="Search by name…" class="appt-drawer-input">
            <input type="hidden" name="patient_user_id" id="adminPatientId" value="">
            <ul class="medlog-combo__list" id="adminPatientList" hidden></ul>
        </div>
        <label for="adminApptDate">Date</label>
        <input type="date" name="appointment_date" id="adminApptDate" class="appt-drawer-input" min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="adminApptTime">Time</label>
        <select name="appointment_time" id="adminApptTime" class="appt-drawer-input" required>
            <option value="">Select time</option>
            <?php foreach ($timeSlots as $slot): ?>
                <option value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <label for="adminApptReason">Reason / notes</label>
        <textarea name="reason" id="adminApptReason" rows="3" class="appt-drawer-input" required placeholder="Visit purpose…"></textarea>
        <button type="submit" name="admin_create_appointment" value="1" class="btn-primary appt-drawer-submit">Save appointment</button>
    </form>
</div>

<div class="appt-modal" id="rescheduleModal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="appt-modal__card">
        <button type="button" class="appt-modal__close" id="rescheduleModalClose" aria-label="Close">&times;</button>
        <h2>Reschedule</h2>
        <form method="POST" id="rescheduleForm">
            <input type="hidden" name="appt_reschedule_token" value="<?= htmlspecialchars($apptTokenReschedule, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="appointment_id" id="rescheduleApptId" value="">
            <label for="rescheduleDate">New date</label>
            <input type="date" name="new_appointment_date" id="rescheduleDate" class="appt-drawer-input" min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
            <label for="rescheduleTime">New time</label>
            <select name="new_appointment_time" id="rescheduleTime" class="appt-drawer-input" required>
                <option value="">Select time</option>
                <?php foreach ($timeSlots as $slot): ?>
                    <option value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
            <label for="rescheduleNote">Reschedule note (optional)</label>
            <textarea name="reschedule_note" id="rescheduleNote" rows="2" class="appt-drawer-input" placeholder="e.g. Patient requested afternoon slot"></textarea>
            <button type="submit" name="admin_reschedule" value="1" class="btn-primary">Save new schedule</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    function apptDeferDisableSubmit(form) {
        var btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        setTimeout(function () { btn.disabled = true; }, 0);
    }

    const SLOTS = <?= $slotsJson ?>;
    const BOOKED = <?= $bookedJson ?>;

    <?php if ($isBooker): ?>
    const dateInput = document.getElementById('bookingDate');
    const slotGrid = document.getElementById('bookingSlotGrid');
    const timeHidden = document.getElementById('bookingTimeValue');
    const submitBtn = document.getElementById('bookingSubmitBtn');

    function renderSlots(dateStr) {
        if (!slotGrid || !timeHidden) return;
        slotGrid.innerHTML = '';
        timeHidden.value = '';
        if (submitBtn) submitBtn.disabled = true;
        if (!dateStr) {
            slotGrid.innerHTML = '<p class="appt-slot-placeholder">Select a date to load times.</p>';
            return;
        }
        const taken = (BOOKED[dateStr] || []).slice();
        SLOTS.forEach(function (slot) {
            const busy = taken.indexOf(slot) !== -1;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'appt-slot-btn' + (busy ? ' is-busy' : '');
            btn.textContent = slot;
            btn.disabled = busy;
            if (!busy) {
                btn.addEventListener('click', function () {
                    slotGrid.querySelectorAll('.appt-slot-btn').forEach(function (b) { b.classList.remove('is-selected'); });
                    btn.classList.add('is-selected');
                    timeHidden.value = slot;
                    if (submitBtn) submitBtn.disabled = false;
                });
            }
            slotGrid.appendChild(btn);
        });
    }

    dateInput?.addEventListener('change', function () { renderSlots(dateInput.value); });
    if (dateInput && dateInput.value) renderSlots(dateInput.value);
    else renderSlots('');

    var studentBookingForm = document.getElementById('studentBookingForm');
    if (studentBookingForm && studentBookingForm.dataset.apptSubmitBound !== '1') {
        studentBookingForm.dataset.apptSubmitBound = '1';
        studentBookingForm.addEventListener('submit', function (e) {
            if (!document.getElementById('bookingTimeValue')?.value) {
                e.preventDefault();
                alert('Please select an available time slot.');
                return;
            }
            apptDeferDisableSubmit(this);
        });
    }
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    const PATIENTS = <?= $patientsJson ?>;
    const overlay = document.getElementById('apptOverlay');
    const drawer = document.getElementById('adminCreateDrawer');
    const openBtn = document.getElementById('openAdminCreateDrawer');
    const closeBtn = document.getElementById('adminCreateClose');
    const searchInput = document.getElementById('adminPatientSearch');
    const hiddenId = document.getElementById('adminPatientId');
    const listEl = document.getElementById('adminPatientList');
    const rescheduleModal = document.getElementById('rescheduleModal');
    const rescheduleClose = document.getElementById('rescheduleModalClose');
    const rescheduleForm = document.getElementById('rescheduleForm');
    const rescheduleId = document.getElementById('rescheduleApptId');

    function setOverlay(on) {
        if (!overlay) return;
        overlay.hidden = !on;
        overlay.classList.toggle('show', on);
    }

    function openDrawer() {
        if (!drawer) return;
        const form = document.getElementById('adminCreateApptForm');
        form?.reset();
        if (hiddenId) hiddenId.value = '';
        if (searchInput) searchInput.value = '';
        if (listEl) listEl.hidden = true;
        var sub = form?.querySelector('button[type="submit"]');
        if (sub) sub.disabled = false;
        drawer.classList.add('show');
        drawer.setAttribute('aria-hidden', 'false');
        setOverlay(true);
    }

    function closeDrawer() {
        if (!drawer) return;
        drawer.classList.remove('show');
        drawer.setAttribute('aria-hidden', 'true');
        if (!rescheduleModal?.classList.contains('show')) setOverlay(false);
    }

    openBtn?.addEventListener('click', openDrawer);
    closeBtn?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', function () {
        if (rescheduleModal?.classList.contains('show')) {
            rescheduleModal.classList.remove('show');
            rescheduleModal.setAttribute('aria-hidden', 'true');
            setOverlay(drawer?.classList.contains('show') || false);
            return;
        }
        closeDrawer();
        setOverlay(false);
    });

    function mountPatientCombo() {
        if (!searchInput || !hiddenId || !listEl) return;
        if (searchInput.dataset.apptComboBound === '1') return;
        searchInput.dataset.apptComboBound = '1';
        function render() {
            listEl.innerHTML = '';
            const q = searchInput.value.toLowerCase().trim();
            const filtered = !q ? PATIENTS.slice() : PATIENTS.filter(function (p) { return p.label.toLowerCase().indexOf(q) !== -1; });
            filtered.slice(0, 80).forEach(function (p) {
                const li = document.createElement('li');
                li.textContent = p.label;
                li.addEventListener('mousedown', function (e) { e.preventDefault(); pick(p); });
                listEl.appendChild(li);
            });
            listEl.hidden = filtered.length === 0;
        }
        function pick(p) {
            hiddenId.value = String(p.id);
            searchInput.value = p.label;
            listEl.hidden = true;
        }
        searchInput.addEventListener('focus', function () { render(); listEl.hidden = false; });
        searchInput.addEventListener('input', function () {
            hiddenId.value = '';
            render();
            listEl.hidden = false;
        });
        document.addEventListener('click', function (e) {
            if (!document.getElementById('adminPatientCombo')?.contains(e.target)) listEl.hidden = true;
        });
    }
    mountPatientCombo();

    document.querySelectorAll('.appt-reschedule-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!rescheduleModal || !rescheduleId) return;
            rescheduleId.value = btn.getAttribute('data-id') || '';
            document.getElementById('rescheduleDate').value = btn.getAttribute('data-date') || '';
            document.getElementById('rescheduleTime').value = btn.getAttribute('data-time') || '';
            document.getElementById('rescheduleNote').value = '';
            const rsBtn = rescheduleForm?.querySelector('button[type="submit"]');
            if (rsBtn) rsBtn.disabled = false;
            rescheduleModal.classList.add('show');
            rescheduleModal.setAttribute('aria-hidden', 'false');
            setOverlay(true);
        });
    });
    rescheduleClose?.addEventListener('click', function () {
        if (!rescheduleModal) return;
        rescheduleModal.classList.remove('show');
        rescheduleModal.setAttribute('aria-hidden', 'true');
        setOverlay(drawer?.classList.contains('show') || false);
    });

    if (rescheduleForm && rescheduleForm.dataset.apptSubmitBound !== '1') {
        rescheduleForm.dataset.apptSubmitBound = '1';
        rescheduleForm.addEventListener('submit', function () {
            apptDeferDisableSubmit(this);
        });
    }

    document.querySelectorAll('form.appt-admin-action-form').forEach(function (form) {
        if (form.dataset.apptSubmitBound === '1') return;
        form.dataset.apptSubmitBound = '1';
        form.addEventListener('submit', function (e) {
            const act = form.querySelector('input[name="admin_action"]')?.value || '';
            if (act === 'approve') {
                apptDeferDisableSubmit(form);
                return;
            }
            if (act === 'complete') {
                const isFuture = form.getAttribute('data-complete-future') === '1';
                const msg = isFuture
                    ? 'This appointment time has not happened yet. Mark as completed anyway?'
                    : 'Mark this appointment as completed?';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                    return;
                }
                if (isFuture) {
                    let hid = form.querySelector('input[name="confirm_early_complete"]');
                    if (!hid) {
                        hid = document.createElement('input');
                        hid.type = 'hidden';
                        hid.name = 'confirm_early_complete';
                        form.appendChild(hid);
                    }
                    hid.value = '1';
                }
                apptDeferDisableSubmit(form);
                return;
            }
            let msg = '';
            if (act === 'reject') msg = 'Are you sure you want to reject this appointment?';
            else if (act === 'cancel') msg = 'Are you sure you want to cancel this appointment?';
            else if (act === 'missed') msg = 'Mark this appointment as missed?';
            if (msg && !window.confirm(msg)) {
                e.preventDefault();
                return;
            }
            apptDeferDisableSubmit(form);
        });
    });

    var adminCreateForm = document.getElementById('adminCreateApptForm');
    if (adminCreateForm && adminCreateForm.dataset.apptSubmitBound !== '1') {
        adminCreateForm.dataset.apptSubmitBound = '1';
        adminCreateForm.addEventListener('submit', function (e) {
            if (!hiddenId || !hiddenId.value) {
                e.preventDefault();
                window.alert('Please select a patient from the list.');
                return;
            }
            const match = PATIENTS.find(function (p) { return String(p.id) === String(hiddenId.value); });
            if (!match) {
                e.preventDefault();
                window.alert('Selected patient is invalid.');
                return;
            }
            if (searchInput && searchInput.value.trim() !== match.label.trim()) {
                e.preventDefault();
                window.alert('Patient selection does not match the typed name. Choose the patient again from the list.');
                hiddenId.value = '';
                return;
            }
            apptDeferDisableSubmit(this);
        });
    }
    <?php endif; ?>
})();
</script>
</body>
</html>
