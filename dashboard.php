<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedLog Dashboard</title>

    <!-- ICONS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- 🔥 GLOBAL LAYOUT -->
    <link rel="stylesheet" href="Css/layout.css">

    <!-- 🎯 PAGE CSS -->
    <link rel="stylesheet" href="Css/dashboard.css">
</head>
<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- HEADER -->
        <?php include 'includes/header.php'; ?>

        <!-- CONTENT -->
        <section class="content">
            <h1>Dashboard</h1>
        </section>

    </main>

</div>

</body>
</html>