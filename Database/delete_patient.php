<?php
include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_POST['user_id'];
    $current_status = $_POST['status'];

    $new_status = ($current_status == 'active') ? 'inactive' : 'active';

    try {
        $stmt = $conn->prepare("UPDATE users SET status = :status WHERE user_id = :user_id");
        $stmt->execute([
            ':status' => $new_status,
            ':user_id' => $user_id
        ]);

        header("Location: ../patients.php");
        exit();

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>