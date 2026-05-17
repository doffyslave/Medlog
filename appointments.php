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
$role = strtolower(trim((string) ($user['role'] ?? 'guest')));
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
$apptErrSlotPast = 'That time has already passed for the selected day. Pick a later slot or another date.';

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
    } elseif (!appt_booking_slot_starts_in_future($date, $timeCanon)) {
        $_SESSION['appointment_error'] = $apptErrSlotPast;
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
    } elseif (!appt_booking_slot_starts_in_future($date, $timeCanon)) {
        $_SESSION['appointment_error'] = $apptErrSlotPast;
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
        } elseif (!appt_booking_slot_starts_in_future($newDate, $newTimeCanon)) {
            $_SESSION['appointment_error'] = $apptErrSlotPast;
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
            CASE LOWER(TRIM(COALESCE(a.status, '')))
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'rescheduled' THEN 3
                WHEN 'completed' THEN 4
                WHEN 'missed' THEN 5
                WHEN 'rejected' THEN 6
                WHEN 'cancelled' THEN 7
                WHEN 'canceled' THEN 7
                ELSE 8
            END,
            a.appointment_id DESC
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
    <?php print_r($appointments[0]); ?>
}

foreach ($appointments as &$apptRow) {
    $apptRow['status'] = appt_normalize_status($apptRow['status'] ?? 'Pending');
}
unset($apptRow);

$nowApptRef = new DateTimeImmutable('now', appt_timezone());

$apptStatusCounts = [
    'Pending' => 0,
    'Approved' => 0,
    'Rescheduled' => 0,
    'Completed' => 0,
    'Missed' => 0,
    'Rejected' => 0,
    'Cancelled' => 0,
];
$apptAdminBuckets = [
    'pending' => [],
    'approved' => [],
    'rescheduled' => [],
    'completed' => [],
    'missed' => [],
    'archived' => [],
];
$bookerActive = [];
$bookerHistory = [];

foreach ($appointments as $a) {
    $st = $a['status'];
    if (isset($apptStatusCounts[$st])) {
        $apptStatusCounts[$st]++;
    }
    if ($isAdmin) {
        if ($st === 'Pending') {
            $apptAdminBuckets['pending'][] = $a;
        } elseif ($st === 'Approved') {
            $apptAdminBuckets['approved'][] = $a;
        } elseif ($st === 'Rescheduled') {
            $apptAdminBuckets['rescheduled'][] = $a;
        } elseif ($st === 'Completed') {
            $apptAdminBuckets['completed'][] = $a;
        } elseif ($st === 'Missed') {
            $apptAdminBuckets['missed'][] = $a;
        } elseif (in_array($st, ['Rejected', 'Cancelled'], true)) {
            $apptAdminBuckets['archived'][] = $a;
        } else {
            $apptAdminBuckets['archived'][] = $a;
        }
    } else {
        if (in_array($st, ['Pending', 'Approved', 'Rescheduled'], true)) {
            $bookerActive[] = $a;
        } else {
            $bookerHistory[] = $a;
        }
    }
}

if (!$isAdmin) {
    usort($bookerActive, static function (array $x, array $y): int {
        $dx = strcmp((string) ($x['appointment_date'] ?? ''), (string) ($y['appointment_date'] ?? ''));
        if ($dx !== 0) {
            return $dx;
        }

        return strcmp((string) ($x['appointment_time'] ?? ''), (string) ($y['appointment_time'] ?? ''));
    });
    usort($bookerHistory, static function (array $x, array $y): int {
        $dx = strcmp((string) ($y['appointment_date'] ?? ''), (string) ($x['appointment_date'] ?? ''));
        if ($dx !== 0) {
            return $dx;
        }

        return strcmp((string) ($y['appointment_time'] ?? ''), (string) ($x['appointment_time'] ?? ''));
    });
}

$todayApptYmd = $nowApptRef->format('Y-m-d');
$apptPastSlotsToday = [];
foreach ($timeSlots as $slotLabel) {
    $slotStartToday = appt_slot_start_immutable($todayApptYmd, $slotLabel);
    $apptPastSlotsToday[$slotLabel] = $slotStartToday === null || $slotStartToday <= $nowApptRef;
}
$apptPastSlotsTodayJson = json_encode($apptPastSlotsToday, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

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
    echo '<button type="button" class="add-btn appt-header-cta" id="openAdminCreateDrawer"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Create appointment</span></button>';
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

/**
 * @param array<string,mixed> $a
 */
function appt_render_appointment_card(array $a, bool $isAdmin, DateTimeImmutable $nowApptRef): void
{
    $st = appt_normalize_status($a['status'] ?? 'Pending');
    $readonly = appt_is_readonly($st);
    $dateFormatted = date('M d, Y', strtotime((string) ($a['appointment_date'] ?? '')));
    $statusClass = appt_badge_class($st);
    $timeDisplay = appt_canonical_time((string) ($a['appointment_time'] ?? ''))
        ?? (string) ($a['appointment_time'] ?? '');
    $slotStartCard = appt_slot_start_immutable(
        (string) ($a['appointment_date'] ?? ''),
        (string) ($a['appointment_time'] ?? '')
    );
    $completeIsFuture = $slotStartCard !== null && $nowApptRef < $slotStartCard;
    $searchBlob = strtolower(
        trim(
            ($isAdmin ? (string) ($a['name'] ?? '') . ' ' : '')
            . (string) ($a['reason'] ?? '')
            . ' ' . (string) ($a['appointment_date'] ?? '')
            . ' ' . $timeDisplay
            . ' ' . $st
        )
    );
    $statusBar = match ($st) {
        'Pending' => 'appt-v2-card__bar--pending',
        'Approved' => 'appt-v2-card__bar--approved',
        'Rescheduled' => 'appt-v2-card__bar--rescheduled',
        'Completed' => 'appt-v2-card__bar--completed',
        'Missed' => 'appt-v2-card__bar--missed',
        'Rejected' => 'appt-v2-card__bar--rejected',
        'Cancelled' => 'appt-v2-card__bar--cancelled',
        default => 'appt-v2-card__bar--pending',
    };

    ?>
    <article
        class="appt-v2-card <?= in_array($st, ['Cancelled', 'Rejected'], true) ? 'appt-v2-card--muted' : '' ?> <?= $readonly ? 'appt-readonly' : '' ?>"
        data-appt-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>"
        data-appt-status="<?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>"
        data-appt-date="<?= htmlspecialchars((string) ($a['appointment_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <div class="appt-v2-card__bar <?= $statusBar ?>" aria-hidden="true"></div>
        <div class="appt-v2-card__body">
            <div class="appt-v2-card__main">
                <?php if ($isAdmin): ?>
                    <div class="appt-v2-card__who">
                        <span class="appt-v2-card__avatar" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                        <strong
                            class="appt-v2-card__name"><?= htmlspecialchars((string) ($a['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                <?php endif; ?>
                <div class="appt-v2-card__when">
                    <span class="appt-v2-chip"><i class="fa-solid fa-calendar" aria-hidden="true"></i>
                        <?= htmlspecialchars($dateFormatted, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="appt-v2-chip"><i class="fa-solid fa-clock" aria-hidden="true"></i>
                        <?= htmlspecialchars($timeDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p class="appt-v2-card__reason"><?= htmlspecialchars((string) ($a['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if (!empty($a['admin_note'])): ?>
                    <div class="appt-v2-meta appt-v2-meta--staff">
                        <span class="appt-v2-meta__label">Staff note</span>
                        <p><?= htmlspecialchars((string) $a['admin_note'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($a['reschedule_note'])): ?>
                    <div class="appt-v2-meta appt-v2-meta--reschedule">
                        <span class="appt-v2-meta__label">Reschedule</span>
                        <p><?= htmlspecialchars((string) $a['reschedule_note'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="appt-v2-card__side">
                <span
                    class="appt-v2-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>

                <?php if (!$isAdmin && !$readonly && appt_student_can_cancel($st)): ?>
                    <form method="POST" class="appt-inline-form js-appt-confirm-form"
                        data-appt-confirm-title="Cancel appointment"
                        data-appt-confirm-msg="Cancel this appointment? You can book a new visit later if needed."
                        data-appt-confirm-danger="1">
                        <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                        <button type="submit" name="cancel_appointment" value="1"
                            class="appt-btn appt-btn--danger appt-btn--sm">Cancel</button>
                    </form>
                <?php elseif ($isAdmin && !$readonly): ?>
                    <div class="appt-v2-actions">
                        <?php if ($st === 'Pending'): ?>
                            <form method="POST" class="appt-inline-form appt-admin-action-form">
                                <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                <input type="hidden" name="admin_action" value="approve">
                                <button type="submit" name="admin_appointment_action" value="1"
                                    class="appt-btn appt-btn--primary appt-btn--sm">Approve</button>
                            </form>
                            <form method="POST" class="appt-inline-form appt-admin-action-form js-appt-confirm-form"
                                data-appt-confirm-title="Reject request"
                                data-appt-confirm-msg="Reject this appointment request? The patient will see it as rejected."
                                data-appt-confirm-danger="1">
                                <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                <input type="hidden" name="admin_action" value="reject">
                                <button type="submit" name="admin_appointment_action" value="1"
                                    class="appt-btn appt-btn--outline-danger appt-btn--sm">Reject</button>
                            </form>
                        <?php endif; ?>

                        <?php if (in_array($st, ['Approved', 'Rescheduled'], true)): ?>
                            <form method="POST" class="appt-inline-form appt-admin-action-form js-appt-confirm-form"
                                data-appt-confirm-title="Mark completed"
                                data-appt-confirm-msg="<?= htmlspecialchars($completeIsFuture ? 'This visit has not occurred yet by the schedule. Mark it completed anyway?' : 'Mark this appointment as completed?', ENT_QUOTES, 'UTF-8') ?>"
                                data-complete-future="<?= $completeIsFuture ? '1' : '0' ?>">
                                <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                <input type="hidden" name="admin_action" value="complete">
                                <button type="submit" name="admin_appointment_action" value="1"
                                    class="appt-btn appt-btn--primary appt-btn--sm">Complete</button>
                            </form>
                            <form method="POST" class="appt-inline-form appt-admin-action-form js-appt-confirm-form"
                                data-appt-confirm-title="Mark missed"
                                data-appt-confirm-msg="Mark this appointment as missed (no-show)?" data-appt-confirm-danger="1">
                                <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                <input type="hidden" name="admin_action" value="missed">
                                <button type="submit" name="admin_appointment_action" value="1"
                                    class="appt-btn appt-btn--outline-danger appt-btn--sm">Missed</button>
                            </form>
                        <?php endif; ?>

                        <?php if (in_array($st, ['Pending', 'Approved', 'Rescheduled'], true)): ?>
                            <?php
                            $resCanon = appt_canonical_time((string) ($a['appointment_time'] ?? '')) ?? '';
                            ?>
                            <button type="button" class="appt-btn appt-btn--secondary appt-btn--sm appt-reschedule-open"
                                data-id="<?= (int) $a['appointment_id'] ?>"
                                data-date="<?= htmlspecialchars((string) $a['appointment_date'], ENT_QUOTES, 'UTF-8') ?>"
                                data-time="<?= htmlspecialchars($resCanon, ENT_QUOTES, 'UTF-8') ?>">
                                Reschedule
                            </button>
                        <?php endif; ?>

                        <?php if (appt_admin_can_cancel($st)): ?>
                            <form method="POST" class="appt-inline-form appt-admin-action-form js-appt-confirm-form"
                                data-appt-confirm-title="Cancel appointment"
                                data-appt-confirm-msg="Cancel this appointment on the schedule?" data-appt-confirm-danger="1">
                                <input type="hidden" name="appointment_id" value="<?= (int) $a['appointment_id'] ?>">
                                <input type="hidden" name="admin_action" value="cancel">
                                <button type="submit" name="admin_appointment_action" value="1"
                                    class="appt-btn appt-btn--ghost appt-btn--sm">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
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
<body<?= $role === 'student' ? ' class="medlog-student-shell"' : '' ?>>
    <div class="dashboard">
        <div class="appointments-section">

  <div class="appointments-header">
    <h1>Appointments</h1>
    <button class="btn-primary">+ Book Appointment</button>
  </div>

  <div class="appointments-card">
    <h2>Appointment List</h2>

    <table class="appointments-table">
      <thead>
        <tr>
          <th>Student</th>
          <th>Date</th>
          <th>Time</th>
          <th>Reason</th>
          <th>Status</th>
        </tr>
      </thead>

     <tbody>
<?php foreach ($appointments as $a): ?>
  <tr>
    <td><?= htmlspecialchars($a['student_name'] ?? 'N/A') ?></td>
    <td><?= htmlspecialchars($a['appointment_date'] ?? '') ?></td>
    <td><?= htmlspecialchars($a['appointment_time'] ?? '') ?></td>
    <td><?= htmlspecialchars($a['reason'] ?? '') ?></td>
    <td>
      <span class="status <?= strtolower($a['status']) ?>">
        <?= htmlspecialchars($a['status']) ?>
      </span>
    </td>
  </tr>
<?php endforeach; ?>
</tbody>
    </table>

  </div>

</div>
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
                    <div class="alert-success">
                        <?= htmlspecialchars($_SESSION['appointment_success'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['appointment_success']); ?>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
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
                    $activeQueueCount = (int) ($apptStatusCounts['Pending'] ?? 0)
                        + (int) ($apptStatusCounts['Approved'] ?? 0)
                        + (int) ($apptStatusCounts['Rescheduled'] ?? 0);
                    ?>
                    <div class="card appt-today-hero">
                        <div class="appt-today-hero__intro">
                            <div>
                                <h2 class="appt-today-hero__title">Today's schedule</h2>
                                <p class="appt-today-hero__date">
                                    <?= htmlspecialchars(date('l — F j, Y'), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <div class="appt-today-hero__pulse" aria-hidden="true">
                                <span class="appt-today-hero__pulse-dot"></span>
                                Live queue · <?= (int) count($todayAppointments) ?>
                                slot<?= count($todayAppointments) === 1 ? '' : 's' ?>
                            </div>
                        </div>
                        <?php if (empty($todayAppointments)): ?>
                            <div class="appt-empty appt-empty--inline">
                                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                                <p>No active visits on the board for today. Pending and upcoming work appears below.</p>
                            </div>
                        <?php else: ?>
                            <ul class="appt-today-timeline">
                                <?php foreach ($todayAppointments as $ta): ?>
                                    <?php
                                    $taSt = appt_normalize_status($ta['status'] ?? 'Pending');
                                    $taTime = appt_canonical_time((string) ($ta['appointment_time'] ?? ''))
                                        ?? (string) ($ta['appointment_time'] ?? '');
                                    ?>
                                    <li
                                        class="appt-today-timeline__item appt-today-timeline__item--<?= htmlspecialchars(strtolower($taSt), ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="appt-today-timeline__time"><?= htmlspecialchars($taTime, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="appt-today-timeline__body">
                                            <strong><?= htmlspecialchars((string) ($ta['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span
                                                class="appt-v2-badge <?= htmlspecialchars(appt_badge_class($taSt), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($taSt, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($ta['reason'])): ?>
                                                <p class="appt-today-timeline__reason">
                                                    <?= htmlspecialchars((string) $ta['reason'], ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="appt-admin-board card" id="apptAdminBoard"
                        data-appt-today="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="appt-admin-board__head">
                            <div>
                                <h2 class="appt-admin-board__title">Appointment desk</h2>
                                <p class="appt-admin-board__lead"><?= (int) $activeQueueCount ?> active in workflow · use
                                    filters to narrow the list</p>
                            </div>
                        </div>

                        <div class="appt-stat-strip" role="list">
                            <div class="appt-stat-pill appt-stat-pill--pending" role="listitem">
                                <span class="appt-stat-pill__n"><?= (int) ($apptStatusCounts['Pending'] ?? 0) ?></span>
                                <span class="appt-stat-pill__l">Pending</span>
                            </div>
                            <div class="appt-stat-pill appt-stat-pill--approved" role="listitem">
                                <span class="appt-stat-pill__n"><?= (int) ($apptStatusCounts['Approved'] ?? 0) ?></span>
                                <span class="appt-stat-pill__l">Approved</span>
                            </div>
                            <div class="appt-stat-pill appt-stat-pill--rescheduled" role="listitem">
                                <span class="appt-stat-pill__n"><?= (int) ($apptStatusCounts['Rescheduled'] ?? 0) ?></span>
                                <span class="appt-stat-pill__l">Rescheduled</span>
                            </div>
                            <div class="appt-stat-pill appt-stat-pill--completed" role="listitem">
                                <span class="appt-stat-pill__n"><?= (int) ($apptStatusCounts['Completed'] ?? 0) ?></span>
                                <span class="appt-stat-pill__l">Completed</span>
                            </div>
                            <div class="appt-stat-pill appt-stat-pill--missed" role="listitem">
                                <span class="appt-stat-pill__n"><?= (int) ($apptStatusCounts['Missed'] ?? 0) ?></span>
                                <span class="appt-stat-pill__l">Missed</span>
                            </div>
                            <div class="appt-stat-pill appt-stat-pill--archived" role="listitem">
                                <span
                                    class="appt-stat-pill__n"><?= (int) (($apptStatusCounts['Rejected'] ?? 0) + ($apptStatusCounts['Cancelled'] ?? 0)) ?></span>
                                <span class="appt-stat-pill__l">Archived</span>
                            </div>
                        </div>

                        <div class="appt-toolbar">
                            <div class="appt-toolbar__search">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" id="apptFilterSearch" class="appt-toolbar__input"
                                    placeholder="Search patient, reason, date…" autocomplete="off"
                                    aria-label="Search appointments">
                            </div>
                            <div class="appt-toolbar__filters">
                                <label class="appt-sr-only" for="apptFilterStatus">Status</label>
                                <select id="apptFilterStatus" class="appt-toolbar__select">
                                    <option value="">All statuses</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rescheduled">Rescheduled</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Missed">Missed</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                <label class="appt-sr-only" for="apptFilterDate">Date</label>
                                <input type="date" id="apptFilterDate" class="appt-toolbar__date"
                                    aria-label="Filter by date">
                                <div class="appt-toolbar__pills" role="group" aria-label="Quick scope">
                                    <button type="button" class="appt-pill appt-pill--active"
                                        data-appt-scope="all">All</button>
                                    <button type="button" class="appt-pill" data-appt-scope="today">Today</button>
                                    <button type="button" class="appt-pill" data-appt-scope="upcoming">Upcoming</button>
                                </div>
                            </div>
                        </div>

                        <?php
                        $apptDeskSections = [
                            ['key' => 'pending', 'title' => 'Pending requests', 'hint' => 'Approve or reject before the visit.', 'items' => $apptAdminBuckets['pending']],
                            ['key' => 'approved', 'title' => 'Approved', 'hint' => 'Confirmed visits awaiting completion.', 'items' => $apptAdminBuckets['approved']],
                            ['key' => 'rescheduled', 'title' => 'Rescheduled', 'hint' => 'Moved to a new slot — verify details.', 'items' => $apptAdminBuckets['rescheduled']],
                            ['key' => 'completed', 'title' => 'Completed history', 'hint' => 'Closed visits (newest first).', 'items' => $apptAdminBuckets['completed']],
                            ['key' => 'missed', 'title' => 'Missed', 'hint' => 'No-shows or unattended slots.', 'items' => $apptAdminBuckets['missed']],
                        ];
                        foreach ($apptDeskSections as $sec):
                            $items = $sec['items'];
                            ?>
                            <section class="appt-desk-section"
                                data-appt-section="<?= htmlspecialchars($sec['key'], ENT_QUOTES, 'UTF-8') ?>">
                                <header class="appt-desk-section__head">
                                    <h3 class="appt-desk-section__title">
                                        <?= htmlspecialchars($sec['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </h3>
                                    <span class="appt-desk-section__count"><?= count($items) ?></span>
                                </header>
                                <p class="appt-desk-section__hint"><?= htmlspecialchars($sec['hint'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <?php if (empty($items)): ?>
                                    <div class="appt-empty appt-empty--section">Nothing in this queue.</div>
                                <?php else: ?>
                                    <div class="appt-desk-section__list">
                                        <?php foreach ($items as $a): ?>
                                            <?php appt_render_appointment_card($a, true, $nowApptRef); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>

                        <details class="appt-archive-fold">
                            <summary class="appt-archive-fold__summary">
                                <span class="appt-archive-fold__title">Archived</span>
                                <span class="appt-archive-fold__meta">Rejected &amp; cancelled ·
                                    <?= count($apptAdminBuckets['archived']) ?>
                                    record<?= count($apptAdminBuckets['archived']) === 1 ? '' : 's' ?></span>
                            </summary>
                            <div class="appt-archive-fold__body">
                                <?php if (empty($apptAdminBuckets['archived'])): ?>
                                    <div class="appt-empty appt-empty--section">No archived appointments.</div>
                                <?php else: ?>
                                    <?php foreach ($apptAdminBuckets['archived'] as $a): ?>
                                        <?php appt_render_appointment_card($a, true, $nowApptRef); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

                <?php if ($isBooker): ?>
                    <div class="card appt-book-shell">
                        <header class="appt-book-shell__header">
                            <div>
                                <h2 class="appt-book-shell__title">Book a clinic visit</h2>
                                <p class="appt-book-shell__lead">Choose when you need the nurse, then describe your visit.
                                </p>
                            </div>
                            <ol class="appt-book-steps" aria-label="Booking steps">
                                <li class="appt-book-steps__i is-active"><span>1</span> Date</li>
                                <li class="appt-book-steps__i"><span>2</span> Time</li>
                                <li class="appt-book-steps__i"><span>3</span> Details</li>
                            </ol>
                        </header>
                        <form method="POST" class="appt-book-layout" id="studentBookingForm">
                            <input type="hidden" name="appt_book_token"
                                value="<?= htmlspecialchars($apptTokenStudent, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="appt-book-panel appt-book-panel--date">
                                <span class="appt-book-panel__label">Select date</span>
                                <input type="date" name="appointment_date" id="bookingDate" class="appt-book-date"
                                    min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
                                    value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                                <p class="appt-book-panel__hint">Slots already taken for a day are disabled automatically.
                                </p>
                            </div>
                            <div class="appt-book-panel appt-book-panel--slots">
                                <span class="appt-book-panel__label">Select time</span>
                                <div class="appt-slot-grid appt-slot-grid--book" id="bookingSlotGrid" aria-live="polite">
                                </div>
                                <p class="appt-book-inline-msg appt-book-inline-msg--error" id="bookingSlotError" hidden
                                    role="alert"></p>
                                <input type="hidden" name="appointment_time" id="bookingTimeValue" value="">
                            </div>
                            <div class="appt-book-panel appt-book-panel--full">
                                <label class="appt-book-panel__label" for="bookingReason">Reason for visit</label>
                                <textarea name="reason" id="bookingReason" rows="4" class="appt-book-textarea"
                                    placeholder="Symptoms, follow-up, or purpose of visit…" required></textarea>
                                <button type="submit" name="add_appointment" value="1"
                                    class="appt-btn appt-btn--primary appt-btn--block" id="bookingSubmitBtn" disabled>
                                    <span>Request appointment</span>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="appt-user-lists">
                        <div class="card appt-user-card">
                            <header class="appt-user-card__head">
                                <h2 class="appt-user-card__title">Upcoming &amp; active</h2>
                                <span class="appt-user-card__badge"><?= count($bookerActive) ?></span>
                            </header>
                            <?php if (empty($bookerActive)): ?>
                                <div class="appt-empty">You have no pending or confirmed visits.</div>
                            <?php else: ?>
                                <?php foreach ($bookerActive as $a): ?>
                                    <?php appt_render_appointment_card($a, false, $nowApptRef); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="card appt-user-card appt-user-card--history">
                            <header class="appt-user-card__head">
                                <h2 class="appt-user-card__title">Visit history</h2>
                                <span
                                    class="appt-user-card__badge appt-user-card__badge--muted"><?= count($bookerHistory) ?></span>
                            </header>
                            <?php if (empty($bookerHistory)): ?>
                                <div class="appt-empty">Completed and closed visits will appear here.</div>
                            <?php else: ?>
                                <div class="appt-user-card__timeline">
                                    <?php foreach ($bookerHistory as $a): ?>
                                        <?php appt_render_appointment_card($a, false, $nowApptRef); ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </section>
        </main>
    </div>

    <div id="apptConfirmDialog" class="appt-dialog" hidden aria-hidden="true">
        <div class="appt-dialog__backdrop" data-appt-dialog-dismiss></div>
        <div class="appt-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="apptConfirmTitle">
            <h3 id="apptConfirmTitle" class="appt-dialog__title">Confirm</h3>
            <p id="apptConfirmBody" class="appt-dialog__body"></p>
            <div class="appt-dialog__actions">
                <button type="button" class="appt-btn appt-btn--ghost" data-appt-dialog-dismiss>Back</button>
                <button type="button" class="appt-btn appt-btn--primary" id="apptConfirmPrimary">Confirm</button>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
        <div class="appt-overlay" id="apptOverlay" hidden></div>
        <div class="appt-drawer appt-drawer--v2" id="adminCreateDrawer" aria-hidden="true" role="dialog" aria-modal="true"
            aria-labelledby="adminCreateTitle">
            <div class="appt-drawer__top">
                <button type="button" class="appt-drawer__close" id="adminCreateClose"
                    aria-label="Close drawer">&times;</button>
                <div>
                    <h2 id="adminCreateTitle" class="appt-drawer__title">Create appointment</h2>
                    <p class="appt-drawer__lead">Schedule for a student or teacher. The slot is saved as approved.</p>
                </div>
            </div>
            <form method="POST" class="appt-drawer-form" id="adminCreateApptForm" autocomplete="off">
                <input type="hidden" name="appt_admin_create_token"
                    value="<?= htmlspecialchars($apptTokenAdminCreate, ENT_QUOTES, 'UTF-8') ?>">
                <label for="adminPatientSearch">Patient</label>
                <div class="medlog-combo" id="adminPatientCombo">
                    <input type="text" id="adminPatientSearch" name="admin_patient_search_display" autocomplete="off"
                        placeholder="Search by name…" class="appt-drawer-input">
                    <input type="hidden" name="patient_user_id" id="adminPatientId" value="">
                    <ul class="medlog-combo__list" id="adminPatientList" hidden></ul>
                </div>
                <p class="appt-drawer-inline-error" id="adminPatientInlineError" hidden role="alert"></p>
                <label for="adminApptDate">Date</label>
                <input type="date" name="appointment_date" id="adminApptDate" class="appt-drawer-input"
                    min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                <label for="adminApptTime">Time</label>
                <select name="appointment_time" id="adminApptTime" class="appt-drawer-input" required>
                    <option value="">Select time</option>
                    <?php foreach ($timeSlots as $slot): ?>
                        <option value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="adminApptReason">Reason / notes</label>
                <textarea name="reason" id="adminApptReason" rows="3" class="appt-drawer-input" required
                    placeholder="Visit purpose…"></textarea>
                <button type="submit" name="admin_create_appointment" value="1"
                    class="appt-btn appt-btn--primary appt-drawer-submit">Save appointment</button>
            </form>
        </div>

        <div class="appt-modal appt-modal--v2" id="rescheduleModal" aria-hidden="true" role="dialog" aria-modal="true">
            <div class="appt-modal__backdrop" id="rescheduleModalBackdrop"></div>
            <div class="appt-modal__card appt-modal__card--v2">
                <button type="button" class="appt-modal__close" id="rescheduleModalClose"
                    aria-label="Close">&times;</button>
                <h2 class="appt-modal__title">Reschedule visit</h2>
                <p class="appt-modal__lead">Pick a new open slot. The visit is marked rescheduled for the patient.</p>
                <form method="POST" id="rescheduleForm" class="appt-modal__form">
                    <input type="hidden" name="appt_reschedule_token"
                        value="<?= htmlspecialchars($apptTokenReschedule, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="appointment_id" id="rescheduleApptId" value="">
                    <label for="rescheduleDate">New date</label>
                    <input type="date" name="new_appointment_date" id="rescheduleDate" class="appt-drawer-input"
                        min="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>
                    <label for="rescheduleTime">New time</label>
                    <select name="new_appointment_time" id="rescheduleTime" class="appt-drawer-input" required>
                        <option value="">Select time</option>
                        <?php foreach ($timeSlots as $slot): ?>
                            <option value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="rescheduleNote">Reschedule note (optional)</label>
                    <textarea name="reschedule_note" id="rescheduleNote" rows="2" class="appt-drawer-input"
                        placeholder="e.g. Patient requested afternoon slot"></textarea>
                    <div class="appt-modal__footer">
                        <button type="button" class="appt-btn appt-btn--ghost" id="rescheduleModalCancelBtn">Cancel</button>
                        <button type="submit" name="admin_reschedule" value="1" class="appt-btn appt-btn--primary">Save new
                            schedule</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        (function () {
            function apptDeferDisableSubmit(form) {
                var btn = form.querySelector('button[type="submit"]');
                if (!btn) return;
                setTimeout(function () { btn.disabled = true; },0);
            }

            var confirmRoot = document.getElementById('apptConfirmDialog');
            var confirmTitle = document.getElementById('apptConfirmTitle');
            var confirmBody = document.getElementById('apptConfirmBody');
            var confirmPrimary = document.getElementById('apptConfirmPrimary');
            var confirmPendingCb = null;

            function apptConfirmClose() {
                if (!confirmRoot) return;
                confirmRoot.classList.remove('appt-dialog--open');
                confirmRoot.setAttribute('aria-hidden','true');
                confirmPendingCb = null;
                setTimeout(function () {
                    if (confirmRoot && !confirmRoot.classList.contains('appt-dialog--open')) {
                        confirmRoot.setAttribute('hidden','hidden');
                    }
                },220);
            }

            function apptConfirmOpen(title,message,isDanger,onConfirm) {
                if (!confirmRoot || !confirmTitle || !confirmBody || !confirmPrimary) {
                    if (onConfirm) onConfirm();
                    return;
                }
                confirmTitle.textContent = title || 'Confirm';
                confirmBody.textContent = message || '';
                confirmPrimary.classList.toggle('appt-btn--danger',!!isDanger);
                confirmPrimary.classList.toggle('appt-btn--primary',!isDanger);
                confirmPendingCb = onConfirm;
                confirmRoot.removeAttribute('hidden');
                confirmRoot.setAttribute('aria-hidden','false');
                requestAnimationFrame(function () {
                    confirmRoot.classList.add('appt-dialog--open');
                });
                confirmPrimary.focus();
            }

            if (confirmRoot && confirmPrimary) {
                confirmPrimary.addEventListener('click',function () {
                    var cb = confirmPendingCb;
                    apptConfirmClose();
                    if (cb) cb();
                });
                confirmRoot.querySelectorAll('[data-appt-dialog-dismiss]').forEach(function (el) {
                    el.addEventListener('click',apptConfirmClose);
                });
                document.addEventListener('keydown',function (e) {
                    if (e.key === 'Escape' && confirmRoot && !confirmRoot.hasAttribute('hidden')) {
                        apptConfirmClose();
                    }
                });
            }

            document.querySelectorAll('form.js-appt-confirm-form').forEach(function (form) {
                if (form.dataset.apptConfirmBound === '1') return;
                form.dataset.apptConfirmBound = '1';
                form.addEventListener('submit',function (e) {
                    if (form.dataset.apptSkipConfirm === '1') {
                        form.dataset.apptSkipConfirm = '';
                        return;
                    }
                    e.preventDefault();
                    var title = form.getAttribute('data-appt-confirm-title') || 'Please confirm';
                    var msg = form.getAttribute('data-appt-confirm-msg') || 'Continue with this action?';
                    var danger = form.getAttribute('data-appt-confirm-danger') === '1';
                    apptConfirmOpen(title,msg,danger,function () {
                        var actInput = form.querySelector('input[name="admin_action"]');
                        var actVal = actInput ? actInput.value : '';
                        if (actVal === 'complete' && form.getAttribute('data-complete-future') === '1') {
                            var hid = form.querySelector('input[name="confirm_early_complete"]');
                            if (!hid) {
                                hid = document.createElement('input');
                                hid.type = 'hidden';
                                hid.name = 'confirm_early_complete';
                                form.appendChild(hid);
                            }
                            hid.value = '1';
                        }
                        form.dataset.apptSkipConfirm = '1';
                        apptDeferDisableSubmit(form);
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    });
                });
            });

            document.querySelectorAll('form.appt-admin-action-form:not(.js-appt-confirm-form)').forEach(function (form) {
                if (form.dataset.apptApproveBound === '1') return;
                form.dataset.apptApproveBound = '1';
                form.addEventListener('submit',function () {
                    apptDeferDisableSubmit(form);
                });
            });

            const SLOTS = <?= $slotsJson ?>;
            const APPT_BOOKING_TODAY = <?= json_encode($todayApptYmd, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const APPT_PAST_SLOTS_TODAY = <?= $apptPastSlotsTodayJson ?>;
            const BOOKED = <?= $bookedJson ?>;

            <?php if ($isBooker): ?>
                const dateInput = document.getElementById('bookingDate');
                const slotGrid = document.getElementById('bookingSlotGrid');
                const timeHidden = document.getElementById('bookingTimeValue');
                const submitBtn = document.getElementById('bookingSubmitBtn');
                const slotErr = document.getElementById('bookingSlotError');
                const stepEls = document.querySelectorAll('.appt-book-steps__i');

                function apptSetStep(current) {
                    var c = Math.max(1,Math.min(3,current || 1));
                    stepEls.forEach(function (li,i) {
                        li.classList.toggle('is-done',i < c - 1);
                        li.classList.toggle('is-active',i === c - 1);
                    });
                }

                function apptShowSlotErr(text) {
                    if (!slotErr) return;
                    if (text) {
                        slotErr.textContent = text;
                        slotErr.removeAttribute('hidden');
                    } else {
                        slotErr.setAttribute('hidden','hidden');
                        slotErr.textContent = '';
                    }
                }

                function renderSlots(dateStr) {
                    if (!slotGrid || !timeHidden) return;
                    slotGrid.innerHTML = '';
                    timeHidden.value = '';
                    apptShowSlotErr('');
                    if (submitBtn) submitBtn.disabled = true;
                    apptSetStep(1);
                    if (!dateStr) {
                        slotGrid.innerHTML = '<p class="appt-slot-placeholder">Select a date to load times.</p>';
                        return;
                    }
                    apptSetStep(2);
                    const taken = (BOOKED[dateStr] || []).slice();
                    SLOTS.forEach(function (slot) {
                        const busy = taken.indexOf(slot) !== -1;
                        const past = dateStr === APPT_BOOKING_TODAY && APPT_PAST_SLOTS_TODAY[slot] === true;
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'appt-slot-btn'
                            + (busy ? ' is-busy' : '')
                            + (past ? ' is-past' : '');
                        btn.textContent = slot;
                        btn.disabled = busy || past;
                        if (!busy && !past) {
                            btn.addEventListener('click',function () {
                                slotGrid.querySelectorAll('.appt-slot-btn').forEach(function (b) { b.classList.remove('is-selected'); });
                                btn.classList.add('is-selected');
                                timeHidden.value = slot;
                                if (submitBtn) submitBtn.disabled = false;
                                apptShowSlotErr('');
                                apptSetStep(3);
                            });
                        }
                        slotGrid.appendChild(btn);
                    });
                }

                dateInput?.addEventListener('change',function () { renderSlots(dateInput.value); });
                if (dateInput && dateInput.value) renderSlots(dateInput.value);
                else renderSlots('');

                var studentBookingForm = document.getElementById('studentBookingForm');
                if (studentBookingForm && studentBookingForm.dataset.apptSubmitBound !== '1') {
                    studentBookingForm.dataset.apptSubmitBound = '1';
                    studentBookingForm.addEventListener('submit',function (e) {
                        if (!document.getElementById('bookingTimeValue')?.value) {
                            e.preventDefault();
                            apptShowSlotErr('Please select an available time slot before submitting.');
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
                const patientInlineErr = document.getElementById('adminPatientInlineError');
                const rescheduleModal = document.getElementById('rescheduleModal');
                const rescheduleClose = document.getElementById('rescheduleModalClose');
                const rescheduleBackdrop = document.getElementById('rescheduleModalBackdrop');
                const rescheduleCancelBtn = document.getElementById('rescheduleModalCancelBtn');
                const rescheduleForm = document.getElementById('rescheduleForm');
                const rescheduleId = document.getElementById('rescheduleApptId');
                const adminBoard = document.getElementById('apptAdminBoard');

                function setOverlay(on) {
                    if (!overlay) return;
                    overlay.hidden = !on;
                    overlay.classList.toggle('show',on);
                }

                function openDrawer() {
                    if (!drawer) return;
                    const form = document.getElementById('adminCreateApptForm');
                    form?.reset();
                    if (hiddenId) hiddenId.value = '';
                    if (searchInput) searchInput.value = '';
                    if (listEl) listEl.hidden = true;
                    if (patientInlineErr) {
                        patientInlineErr.setAttribute('hidden','hidden');
                        patientInlineErr.textContent = '';
                    }
                    var sub = form?.querySelector('button[type="submit"]');
                    if (sub) sub.disabled = false;
                    drawer.classList.add('show');
                    drawer.setAttribute('aria-hidden','false');
                    setOverlay(true);
                }

                function closeDrawer() {
                    if (!drawer) return;
                    drawer.classList.remove('show');
                    drawer.setAttribute('aria-hidden','true');
                    if (!rescheduleModal?.classList.contains('show')) setOverlay(false);
                }

                function closeRescheduleModal() {
                    if (!rescheduleModal) return;
                    rescheduleModal.classList.remove('show');
                    rescheduleModal.setAttribute('aria-hidden','true');
                    setOverlay(drawer?.classList.contains('show') || false);
                }

                openBtn?.addEventListener('click',openDrawer);
                closeBtn?.addEventListener('click',closeDrawer);
                overlay?.addEventListener('click',function () {
                    if (rescheduleModal?.classList.contains('show')) {
                        closeRescheduleModal();
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
                        filtered.slice(0,80).forEach(function (p) {
                            const li = document.createElement('li');
                            li.textContent = p.label;
                            li.addEventListener('mousedown',function (e) { e.preventDefault(); pick(p); });
                            listEl.appendChild(li);
                        });
                        listEl.hidden = filtered.length === 0;
                    }
                    function pick(p) {
                        hiddenId.value = String(p.id);
                        searchInput.value = p.label;
                        listEl.hidden = true;
                        if (patientInlineErr) patientInlineErr.setAttribute('hidden','hidden');
                    }
                    searchInput.addEventListener('focus',function () { render(); listEl.hidden = false; });
                    searchInput.addEventListener('input',function () {
                        hiddenId.value = '';
                        render();
                        listEl.hidden = false;
                    });
                    document.addEventListener('click',function (e) {
                        if (!document.getElementById('adminPatientCombo')?.contains(e.target)) listEl.hidden = true;
                    });
                }
                mountPatientCombo();

                document.querySelectorAll('.appt-reschedule-open').forEach(function (btn) {
                    btn.addEventListener('click',function () {
                        if (!rescheduleModal || !rescheduleId) return;
                        rescheduleId.value = btn.getAttribute('data-id') || '';
                        document.getElementById('rescheduleDate').value = btn.getAttribute('data-date') || '';
                        document.getElementById('rescheduleTime').value = btn.getAttribute('data-time') || '';
                        document.getElementById('rescheduleNote').value = '';
                        const rsBtn = rescheduleForm?.querySelector('button[type="submit"]');
                        if (rsBtn) rsBtn.disabled = false;
                        rescheduleModal.classList.add('show');
                        rescheduleModal.setAttribute('aria-hidden','false');
                        setOverlay(true);
                    });
                });
                rescheduleClose?.addEventListener('click',closeRescheduleModal);
                rescheduleBackdrop?.addEventListener('click',closeRescheduleModal);
                rescheduleCancelBtn?.addEventListener('click',closeRescheduleModal);

                if (rescheduleForm && rescheduleForm.dataset.apptSubmitBound !== '1') {
                    rescheduleForm.dataset.apptSubmitBound = '1';
                    rescheduleForm.addEventListener('submit',function () {
                        apptDeferDisableSubmit(this);
                    });
                }

                function apptApplyAdminFilters() {
                    if (!adminBoard) return;
                    var q = (document.getElementById('apptFilterSearch')?.value || '').toLowerCase().trim();
                    var st = document.getElementById('apptFilterStatus')?.value || '';
                    var dFilter = document.getElementById('apptFilterDate')?.value || '';
                    var scopeEl = document.querySelector('.appt-pill--active');
                    var scope = scopeEl ? scopeEl.getAttribute('data-appt-scope') : 'all';
                    var todayStr = adminBoard.getAttribute('data-appt-today') || '';

                    adminBoard.querySelectorAll('.appt-v2-card').forEach(function (card) {
                        var blob = (card.getAttribute('data-appt-search') || '').toLowerCase();
                        var cst = card.getAttribute('data-appt-status') || '';
                        var cdt = card.getAttribute('data-appt-date') || '';
                        var ok = true;
                        if (q && blob.indexOf(q) === -1) ok = false;
                        if (st && cst !== st) ok = false;
                        if (dFilter && cdt !== dFilter) ok = false;
                        if (scope === 'today' && cdt !== todayStr) ok = false;
                        if (scope === 'upcoming' && !(cdt > todayStr)) ok = false;
                        card.classList.toggle('appt-v2-card--filtered-out',!ok);
                    });

                    adminBoard.querySelectorAll('.appt-desk-section').forEach(function (sec) {
                        var vis = sec.querySelectorAll('.appt-v2-card:not(.appt-v2-card--filtered-out)').length;
                        sec.classList.toggle('appt-desk-section--all-hidden',vis === 0);
                    });
                    var arch = adminBoard.querySelector('.appt-archive-fold');
                    if (arch) {
                        var av = arch.querySelectorAll('.appt-v2-card:not(.appt-v2-card--filtered-out)').length;
                        arch.classList.toggle('appt-archive-fold--all-hidden',av === 0);
                    }
                }

                document.getElementById('apptFilterSearch')?.addEventListener('input',apptApplyAdminFilters);
                document.getElementById('apptFilterStatus')?.addEventListener('change',apptApplyAdminFilters);
                document.getElementById('apptFilterDate')?.addEventListener('change',apptApplyAdminFilters);
                document.querySelectorAll('.appt-pill[data-appt-scope]').forEach(function (pill) {
                    pill.addEventListener('click',function () {
                        document.querySelectorAll('.appt-pill[data-appt-scope]').forEach(function (p) { p.classList.remove('appt-pill--active'); });
                        pill.classList.add('appt-pill--active');
                        var scope = pill.getAttribute('data-appt-scope') || '';
                        var dInput = document.getElementById('apptFilterDate');
                        var todayStr = adminBoard ? (adminBoard.getAttribute('data-appt-today') || '') : '';
                        if (dInput && scope === 'today' && todayStr) {
                            dInput.value = todayStr;
                        } else if (dInput && scope === 'upcoming') {
                            dInput.value = '';
                        } else if (dInput && scope === 'all') {
                            dInput.value = '';
                        }

                        apptApplyAdminFilters();
                    });
                });

                var adminCreateForm = document.getElementById('adminCreateApptForm');
                if (adminCreateForm && adminCreateForm.dataset.apptSubmitBound !== '1') {
                    adminCreateForm.dataset.apptSubmitBound = '1';
                    adminCreateForm.addEventListener('submit',function (e) {
                        function showErr(t) {
                            if (patientInlineErr) {
                                patientInlineErr.textContent = t;
                                patientInlineErr.removeAttribute('hidden');
                            }
                        }
                        if (!hiddenId || !hiddenId.value) {
                            e.preventDefault();
                            showErr('Please select a patient from the search list.');
                            return;
                        }
                        const match = PATIENTS.find(function (p) { return String(p.id) === String(hiddenId.value); });
                        if (!match) {
                            e.preventDefault();
                            showErr('Selected patient is invalid. Choose again from the list.');
                            return;
                        }
                        if (searchInput && searchInput.value.trim() !== match.label.trim()) {
                            e.preventDefault();
                            showErr('Pick the patient again from the list so the name matches your selection.');
                            hiddenId.value = '';
                            return;
                        }
                        if (patientInlineErr) patientInlineErr.setAttribute('hidden','hidden');
                        apptDeferDisableSubmit(this);
                    });
                }
            <?php endif; ?>
        })();
    </script>
    </body>

</html>

</html>