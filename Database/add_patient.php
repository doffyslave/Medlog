<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $course = $_POST['course'] ?? null;
    $year_level = $_POST['year_level'] ?? null;

    // 🔥 AUTO GENERATE USER ID
    $user_id = uniqid();

    // 🔥 GENERATE STUDENT ID
    $student_id = null;

    if ($role === "student") {
        if (preg_match('/\.(\d+)@/', $email, $matches)) {
            $num = $matches[1];
            $student_id = '02-000' . substr($num, 0, 1) . '-' . substr($num, 1);
        }
    } else {
        $course = null;
        $year_level = null;
    }

    try {
        // prevent duplicate email
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            header("Location: ../patients.php?error=exists");
            exit();
        }

        // 🔥 INSERT WITH student_id
        $stmt = $conn->prepare("
            INSERT INTO users 
            (user_id, name, email, role, course, year_level, student_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");

        $stmt->execute([
            $user_id,
            $name,
            $email,
            $role,
            $course,
            $year_level,
            $student_id
        ]);

        header("Location: ../patients.php?success=1");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}