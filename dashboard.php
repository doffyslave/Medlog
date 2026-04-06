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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="Css/dashboard.css">
</head>
<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h2 class="logo">MedLog</h2>

        <div class="user">
            <img src="Images/UserIcon.jpg" alt="User" class="userImage">

            <!-- DYNAMIC USER NAME -->
            <span class="menuText">
                <?php echo htmlspecialchars($user['name']); ?>
            </span>
        </div>

        <nav class="menu">
            <ul>
                <li class="active">
                    <a href="#">
                        <i class="fas fa-home"></i>
                        <span class="menuText">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="patients.php">
                        <i class="fas fa-user-injured"></i>
                        <span class="menuText">Patients</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-pills"></i>
                        <span class="menuText">Inventory</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-chart-line"></i>
                        <span class="menuText">Reports</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topnav">
            <div class="toggle-btn">
                <i class="fas fa-bars"></i>
            </div>

            <div class="actions">
                <!-- LOGOUT -->
                <a href="Database/logout.php" class="logout" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <section class="content">
            <h1>Dashboard</h1>
        </section>

    </main>

</div>

<script>
// Sidebar toggle
const toggleBtn = document.querySelector('.toggle-btn');
const sidebar = document.querySelector('.sidebar');
const menuText = document.querySelectorAll('.menuText');
const userImage = document.querySelector('.userImage');

let isCollapsed = false;

toggleBtn.addEventListener('click', () => {
    if (!isCollapsed) {
        sidebar.style.width = '80px';

        menuText.forEach(text => {
            text.style.display = 'none';
        });

        userImage.style.width = '30px';
        userImage.style.height = '30px';

        isCollapsed = true;
    } else {
        sidebar.style.width = '240px';

        menuText.forEach(text => {
            text.style.display = 'inline';
        });

        userImage.style.width = '40px';
        userImage.style.height = '40px';

        isCollapsed = false;
    }
});

// Logout confirmation
const logoutBtn = document.getElementById('logoutBtn');

if (logoutBtn) {
    logoutBtn.addEventListener('click', function (e) {
        const confirmLogout = confirm("Are you sure you want to log out?");
        
        if (!confirmLogout) {
            e.preventDefault();
        }
    });
}
</script>

</body>
</html>