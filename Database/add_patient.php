<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $course = $_POST['course'];
    $year_level = $_POST['year_level'];

    if ($role != "student") {
        $course = NULL;
        $year_level = NULL;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO users 
        (user_id, name, email, role, course, year_level, status)
        VALUES (:user_id, :name, :email, :role, :course, :year_level, 'active')");

        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $name,
            ':email' => $email,
            ':role' => $role,
            ':course' => $course,
            ':year_level' => $year_level
        ]);

        header("Location: ../patients.php");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>