<?php
/**
 * Session helpers — match dashboard.php / my_visits.php: use users.user_id as stored in $_SESSION['user'].
 * Never cast user_id to int (student IDs can be alphanumeric, e.g. registration or legacy formats).
 */
declare(strict_types=1);

/**
 * @param array<string, mixed> $user
 */
function ml_session_user_id(array $user): string
{
    if (!array_key_exists('user_id', $user)) {
        return '';
    }
    $id = $user['user_id'];
    if ($id === null) {
        return '';
    }
    if (is_bool($id)) {
        return '';
    }
    if (is_int($id)) {
        return (string) $id;
    }

    return trim((string) $id);
}
