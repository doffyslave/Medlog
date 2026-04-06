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
        $stmt = $conn->prepare("UPDATE users SET
            name = :name,
            email = :email,
            role = :role,
            course = :course,
            year_level = :year_level
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':role' => $role,
            ':course' => $course,
            ':year_level' => $year_level,
            ':user_id' => $user_id
        ]);

        header("Location: ../patients.php");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>