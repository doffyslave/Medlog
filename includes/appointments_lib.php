<?php
/**
 * Shared appointment scheduling logic (MedLog).
 * Include after PDO $conn is available.
 */

if (!function_exists('appt_timezone')) {
    function appt_timezone(): DateTimeZone
    {
        return new DateTimeZone('Asia/Manila');
    }
}

if (!function_exists('appt_time_slots')) {
    function appt_time_slots(): array
    {
        return [
            '08:00 AM',
            '08:30 AM',
            '09:00 AM',
            '09:30 AM',
            '10:00 AM',
            '10:30 AM',
            '11:00 AM',
            '11:30 AM',
            '01:00 PM',
            '01:30 PM',
            '02:00 PM',
            '02:30 PM',
            '03:00 PM',
            '03:30 PM',
            '04:00 PM',
        ];
    }
}

if (!function_exists('appt_canonical_time')) {
    /**
     * Map stored or submitted time (12h string or MySQL TIME) to a key from appt_time_slots().
     */
    function appt_canonical_time(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $t = trim((string) $raw);
        if ($t === '') {
            return null;
        }
        $slots = appt_time_slots();
        foreach ($slots as $s) {
            if (strcasecmp($s, $t) === 0) {
                return $s;
            }
        }
        $tz = appt_timezone();
        $dt = DateTimeImmutable::createFromFormat('H:i:s', $t, $tz)
            ?: DateTimeImmutable::createFromFormat('H:i', $t, $tz)
            ?: DateTimeImmutable::createFromFormat('h:i A', $t, $tz)
            ?: DateTimeImmutable::createFromFormat('g:i A', $t, $tz);
        if (!$dt) {
            return null;
        }
        $target = $dt->format('H:i');
        foreach ($slots as $s) {
            $slotDt = DateTimeImmutable::createFromFormat('h:i A', $s, $tz);
            if ($slotDt && $slotDt->format('H:i') === $target) {
                return $s;
            }
        }
        return null;
    }
}

if (!function_exists('appt_slot_end_immutable')) {
    /** End instant of the 30-minute block (exclusive comparison uses < now). */
    function appt_slot_end_immutable(string $dateYmd, string $timeRaw): ?DateTimeImmutable
    {
        $canon = appt_canonical_time($timeRaw);
        if ($canon === null) {
            return null;
        }
        $tz = appt_timezone();
        $start = DateTimeImmutable::createFromFormat('Y-m-d h:i A', $dateYmd . ' ' . $canon, $tz);
        if (!$start) {
            return null;
        }
        return $start->modify('+30 minutes');
    }
}

if (!function_exists('appt_slot_start_immutable')) {
    function appt_slot_start_immutable(string $dateYmd, string $timeRaw): ?DateTimeImmutable
    {
        $canon = appt_canonical_time($timeRaw);
        if ($canon === null) {
            return null;
        }
        $tz = appt_timezone();
        $start = DateTimeImmutable::createFromFormat('Y-m-d h:i A', $dateYmd . ' ' . $canon, $tz);
        return $start ?: null;
    }
}

if (!function_exists('appt_booking_slot_starts_in_future')) {
    /**
     * True when the slot start is strictly after "now" in appt_timezone().
     * Blocks past calendar days, invalid date/time pairs, and same-day times that have already begun.
     */
    function appt_booking_slot_starts_in_future(string $dateYmd, string $timeCanon, ?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable('now', appt_timezone());
        $start = appt_slot_start_immutable($dateYmd, $timeCanon);
        return $start !== null && $start > $now;
    }
}

if (!function_exists('appt_form_token_issue')) {
    function appt_form_token_issue(string $key): string
    {
        if (!isset($_SESSION['appt_form_tokens']) || !is_array($_SESSION['appt_form_tokens'])) {
            $_SESSION['appt_form_tokens'] = [];
        }
        $t = bin2hex(random_bytes(16));
        $_SESSION['appt_form_tokens'][$key] = $t;
        return $t;
    }
}

if (!function_exists('appt_form_token_verify')) {
    function appt_form_token_verify(string $key, ?string $submitted): bool
    {
        if ($submitted === null || $submitted === '') {
            return false;
        }
        if (!isset($_SESSION['appt_form_tokens'][$key])) {
            return false;
        }
        if (!hash_equals((string) $_SESSION['appt_form_tokens'][$key], $submitted)) {
            return false;
        }
        unset($_SESSION['appt_form_tokens'][$key]);
        return true;
    }
}

if (!function_exists('appt_users_has_status_column')) {
    function appt_users_has_status_column(PDO $conn): bool
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        try {
            $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            $cache = $stmt && $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            $cache = false;
        }
        return $cache;
    }
}

if (!function_exists('appt_reserved_statuses')) {
    /** Statuses that block a date/time slot */
    function appt_reserved_statuses(): array
    {
        return ['Pending', 'Approved', 'Rescheduled'];
    }
}

if (!function_exists('appt_reserved_statuses_lower')) {
    /** Lowercase keys for SQL / legacy rows with inconsistent casing */
    function appt_reserved_statuses_lower(): array
    {
        return ['pending', 'approved', 'rescheduled'];
    }
}

if (!function_exists('appt_sql_slot_reserved_condition')) {
    /** WHERE fragment: slot taken by pending-like status (case-insensitive). */
    function appt_sql_slot_reserved_condition(string $alias = ''): string
    {
        $col = ($alias !== '' ? $alias . '.' : '') . 'status';
        $lowers = appt_reserved_statuses_lower();
        $in = implode(',', array_fill(0, count($lowers), '?'));
        return "LOWER(TRIM({$col})) IN ({$in})";
    }
}

if (!function_exists('appt_user_bookable_blocklist_lower')) {
    /** Explicit non-bookable account_status values (lowercase). */
    function appt_user_bookable_blocklist_lower(): array
    {
        return ['inactive', 'disabled', 'archived', 'suspended', 'banned', 'deleted'];
    }
}

if (!function_exists('appt_sql_user_bookable_condition')) {
    /**
     * Non-admin users bookable for appointments when status column exists.
     * NULL / empty / unknown statuses are allowed; only explicit inactive-like values excluded.
     */
    function appt_sql_user_bookable_condition(string $alias = ''): string
    {
        $col = ($alias !== '' ? $alias . '.' : '') . 'status';
        $blocked = appt_user_bookable_blocklist_lower();
        $in = implode(',', array_fill(0, count($blocked), '?'));
        return "(
            {$col} IS NULL
            OR TRIM({$col}) = ''
            OR LOWER(TRIM({$col})) NOT IN ({$in})
        )";
    }
}

if (!function_exists('appt_all_statuses')) {
    function appt_all_statuses(): array
    {
        return ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Completed', 'Missed', 'Rescheduled'];
    }
}

if (!function_exists('appt_normalize_status')) {
    function appt_normalize_status(?string $status): string
    {
        $s = trim((string) $status);
        if ($s === '') {
            return 'Pending';
        }
        $map = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            'canceled' => 'Cancelled',
            'completed' => 'Completed',
            'complete' => 'Completed',
            'missed' => 'Missed',
            'rescheduled' => 'Rescheduled',
        ];
        $lower = strtolower($s);
        if (isset($map[$lower])) {
            return $map[$lower];
        }
        return $s;
    }
}

if (!function_exists('appt_ensure_schema')) {
    /**
     * Safe additive schema tweaks (no destructive changes).
     */
    function appt_ensure_schema(PDO $conn): void
    {
        try {
            $stmt = $conn->query('SHOW COLUMNS FROM appointments');
            $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            if (!is_array($cols)) {
                return;
            }
            if (!in_array('reschedule_note', $cols, true)) {
                try {
                    $conn->exec('ALTER TABLE appointments ADD COLUMN reschedule_note TEXT NULL');
                } catch (Throwable $e) {
                }
            }
        } catch (Throwable $e) {
        }
        try {
            $conn->exec("ALTER TABLE appointments MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Pending'");
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('appt_slot_conflict_count')) {
    function appt_slot_conflict_count(PDO $conn, string $date, string $time, ?int $excludeAppointmentId = null): int
    {
        $need = appt_canonical_time($time);
        if ($need === null) {
            return 999999;
        }
        $reservedCond = appt_sql_slot_reserved_condition();
        $params = array_merge([$date], appt_reserved_statuses_lower());
        $sql = "
            SELECT appointment_id, appointment_time
            FROM appointments
            WHERE appointment_date = ?
              AND {$reservedCond}
        ";
        if ($excludeAppointmentId !== null && $excludeAppointmentId > 0) {
            $sql .= ' AND appointment_id != ?';
            $params[] = $excludeAppointmentId;
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $n = 0;
        foreach ($rows as $row) {
            $c = appt_canonical_time((string) ($row['appointment_time'] ?? ''));
            if ($c !== null && $c === $need) {
                $n++;
            }
        }
        return $n;
    }
}

if (!function_exists('appt_mark_past_approved_missed')) {
    /**
     * If appointment slot end is before now (Asia/Manila) and status is Approved → Missed.
     */
    function appt_mark_past_approved_missed(PDO $conn): int
    {
        $stmt = $conn->query("
            SELECT appointment_id, appointment_date, appointment_time
            FROM appointments
            WHERE status = 'Approved'
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $now = new DateTimeImmutable('now', appt_timezone());
        $updated = 0;
        $up = $conn->prepare("UPDATE appointments SET status = 'Missed' WHERE appointment_id = ? AND status = 'Approved'");
        foreach ($rows as $row) {
            $end = appt_slot_end_immutable((string) $row['appointment_date'], (string) $row['appointment_time']);
            if ($end !== null && $end <= $now) {
                $up->execute([(int) $row['appointment_id']]);
                $updated++;
            }
        }
        return $updated;
    }
}

if (!function_exists('appt_student_can_cancel')) {
    function appt_student_can_cancel(string $status): bool
    {
        return in_array($status, ['Pending', 'Approved', 'Rescheduled'], true);
    }
}

if (!function_exists('appt_admin_can_cancel')) {
    function appt_admin_can_cancel(string $status): bool
    {
        return in_array($status, ['Pending', 'Approved', 'Rescheduled'], true);
    }
}

if (!function_exists('appt_is_readonly')) {
    function appt_is_readonly(string $status): bool
    {
        return in_array($status, ['Completed', 'Missed', 'Rejected', 'Cancelled'], true);
    }
}
