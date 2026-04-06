<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_POST['user_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        header("Location: ../patients.php");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>