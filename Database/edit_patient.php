<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 🔥 GET DATA
    $user_id = $_POST['user_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $course = $_POST['course'] ?? null;
    $year_level = $_POST['year_level'] ?? null;
    $status = $_POST['status'] ?? 'active';

    // 🔥 BASIC VALIDATION
    if (empty($user_id) || empty($name) || empty($email) || empty($role)) {
        die("Missing required fields.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // 🔥 AUTO CLEAN NON-STUDENT
    if ($role !== "student") {
        $course = null;
        $year_level = null;
    }

    try {
        $stmt = $conn->prepare("
            UPDATE users SET
                name = :name,
                email = :email,
                role = :role,
                course = :course,
                year_level = :year_level,
                status = :status
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':role' => $role,
            ':course' => $course,
            ':year_level' => $year_level,
            ':status' => $status,
            ':user_id' => $user_id
        ]);

        // 🔥 SUCCESS REDIRECT
        header("Location: ../patients.php?success=updated");
        exit();

    } catch (PDOException $e) {
        die("Update failed: " . $e->getMessage());
    }
}
?>