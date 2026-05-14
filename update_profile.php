<?php
/**
 * Student profile update (AJAX JSON).
 * Editable: course, year_level, phone_number, emergency_contact_name,
 * emergency_contact_number, allergies.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not signed in.']);
    exit;
}

$user = $_SESSION['user'];
if (strtolower(trim((string) ($user['role'] ?? ''))) !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only students can update this profile.']);
    exit;
}

$userId = (int) ($user['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid session.']);
    exit;
}

$csrf = (string) ($_POST['csrf_token'] ?? '');
$expected = (string) ($_SESSION['profile_csrf'] ?? '');
if ($expected === '' || !hash_equals($expected, $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security check failed. Please refresh the page.']);
    exit;
}

require __DIR__ . '/Database/connection.php';

function ml_trim_or_null(string $v, int $max): ?string
{
    $v = trim($v);
    if ($v === '') {
        return null;
    }
    if (function_exists('mb_substr')) {
        return mb_substr($v, 0, $max, 'UTF-8');
    }
    return substr($v, 0, $max);
}

function ml_normalize_phone(?string $raw): ?string
{
    if ($raw === null) {
        return null;
    }
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (strlen($raw) > 40) {
        return null;
    }
    if (!preg_match('/^[\d\s\-\+\(\)\.]{7,40}$/u', $raw)) {
        return null;
    }
    return $raw;
}

$course = ml_trim_or_null((string) ($_POST['course'] ?? ''), 160);
$yearLevel = ml_trim_or_null((string) ($_POST['year_level'] ?? ''), 80);
$phone = ml_normalize_phone((string) ($_POST['phone_number'] ?? ''));
$emName = ml_trim_or_null((string) ($_POST['emergency_contact_name'] ?? ''), 120);
$emNum = ml_normalize_phone((string) ($_POST['emergency_contact_number'] ?? ''));
$allergies = ml_trim_or_null((string) ($_POST['allergies'] ?? ''), 1000);

$errors = [];
if ($phone === null && trim((string) ($_POST['phone_number'] ?? '')) !== '') {
    $errors['phone_number'] = 'Enter a valid phone number (digits, spaces, +, -, parentheses).';
}
if ($emNum === null && trim((string) ($_POST['emergency_contact_number'] ?? '')) !== '') {
    $errors['emergency_contact_number'] = 'Enter a valid emergency number.';
}

if ($errors !== []) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors]);
    exit;
}

try {
    $exists = $conn->prepare('SELECT 1 FROM users WHERE user_id = ? AND role = \'student\' LIMIT 1');
    $exists->execute([$userId]);
    if (!$exists->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Profile could not be updated.']);
        exit;
    }

    $stmt = $conn->prepare('
        UPDATE users SET
            course = :course,
            year_level = :year_level,
            phone_number = :phone_number,
            emergency_contact_name = :emergency_contact_name,
            emergency_contact_number = :emergency_contact_number,
            allergies = :allergies
        WHERE user_id = :user_id AND role = \'student\'
    ');
    $stmt->execute([
        ':course' => $course,
        ':year_level' => $yearLevel,
        ':phone_number' => $phone,
        ':emergency_contact_name' => $emName,
        ':emergency_contact_number' => $emNum,
        ':allergies' => $allergies,
        ':user_id' => $userId,
    ]);

    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.',
        'csrf_token' => $_SESSION['profile_csrf'],
        'data' => [
            'course' => $course,
            'year_level' => $yearLevel,
            'phone_number' => $phone,
            'emergency_contact_name' => $emName,
            'emergency_contact_number' => $emNum,
            'allergies' => $allergies,
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save changes. If the problem persists, contact support.']);
}
