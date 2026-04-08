<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $course = $_POST['course'];
    $year_level = $_POST['year_level'];
    $password = $_POST['password'];

    if ($role != "student") {
        $course = NULL;
        $year_level = NULL;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $check = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id OR email = :email");
        $check->execute([
            ':user_id' => $user_id,
            ':email' => $email
        ]);

        if ($check->rowCount() > 0) {
            echo "User already exists!";
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO users 
        (user_id, name, email, role, course, year_level, password)
        VALUES (:user_id, :name, :email, :role, :course, :year_level, :password)");

        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $name,
            ':email' => $email,
            ':role' => $role,
            ':course' => $course,
            ':year_level' => $year_level,
            ':password' => $hashed_password
        ]);

        header("Location: ../patients.php");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
