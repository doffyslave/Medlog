<?php 
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDLOG Login - Inventory Management System</title>
    <link rel="stylesheet" href="../Css/login.css">
</head>
<body id="loginBody">

    <div class="loginContainer">

        <!-- LOGO -->
        <div class="logoContainer">
            <img src="../Images/MEDLOG, BG-REMOVED.png" alt="MedLog Logo">
        </div>

        <!-- HEADER -->
        <div class="loginHeader">
           <h1>MEDLOG</h1> 
            <p>Inventory Management System</p>
        </div>

        <!-- BUTTONS (FIXED STRUCTURE) -->
        <div class="loginFormContainer"> 

            <!-- M365 LOGIN -->
            <div class="loginButtonContainer">
                <a href="login_redirect.php" class="m365-btn">
                    Login with Microsoft 365
                </a>
            </div>

            <!-- ADMIN LOGIN -->
            <div class="loginButtonContainer">
                <a href="admin_login.php" class="admin-btn">
                    Admin Login
                </a>
            </div>

        </div>

        <!-- EXTRA TEXT -->
        <div class="loginExtraLinks">
            <p style="text-align:center; font-size:12px;">
                Use your STI Microsoft account to continue
            </p>
        </div>

    </div>

</body>
</html>
