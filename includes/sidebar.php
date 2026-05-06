<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-left">
            <div class="brand-icon">
                <i class="fa-solid fa-heart-pulse brand-symbol" aria-hidden="true"></i>
            </div>
            <h2 class="logo">MedLog</h2>
        </div>
    </div>

    <div class="user">
        <img src="Images/UserIcon.jpg" class="userImage" alt="User avatar">
        <div class="user-copy">
            <span class="menuText user-name">
                <?= htmlspecialchars($user['name'] ?? 'User') ?>
            </span>
            <small><?= ucfirst($role) ?></small>
        </div>
    </div>

    <nav class="menu">
        <p class="section-title">GENERAL</p>
        <ul>

            <!-- DASHBOARD -->
            <li class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php">
                    <span class="menu-icon"><i class="fa-solid fa-chart-line"></i></span>
                    <span class="menuText">Dashboard</span>
                </a>
            </li>

            <!-- ================= STUDENT VIEW ================= -->
            <?php if ($role === 'student'): ?>

                <li class="<?= $current == 'profile.php' ? 'active' : '' ?>">
                    <a href="profile.php">
                        <span class="menu-icon profile-menu-icon"><span class="profile-head"></span><span class="profile-body"></span></span>
                        <span class="menuText">Profile</span>
                    </a>
                </li>

                <li class="<?= $current == 'my_visits.php' ? 'active' : '' ?>">
                    <a href="my_visits.php">
                        <span class="menu-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                        <span class="menuText">My Visits</span>
                    </a>
                </li>

                <li class="<?= $current == 'appointments.php' ? 'active' : '' ?>">
                    <a href="appointments.php">
                        <span class="menu-icon"><i class="fa-solid fa-calendar-check"></i></span>
                        <span class="menuText">Appointments</span>
                    </a>
                </li>

                <li class="<?= $current == 'medicines.php' ? 'active' : '' ?>">
                    <a href="medicines.php">
                        <span class="menu-icon"><i class="fa-solid fa-pills"></i></span>
                        <span class="menuText">Medicines</span>
                    </a>
                </li>

            <?php endif; ?>

            <!-- ================= ADMIN VIEW ================= -->
            <?php if ($role === 'admin'): ?>

                <li class="<?= $current == 'patients.php' ? 'active' : '' ?>">
                    <a href="patients.php">
                        <span class="menu-icon"><i class="fa-solid fa-users"></i></span>
                        <span class="menuText">Patients</span>
                    </a>
                </li>

                <li class="<?= $current == 'visits.php' ? 'active' : '' ?>">
                    <a href="visits.php">
                        <span class="menu-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                        <span class="menuText">Visits</span>
                    </a>
                </li>

                <li class="<?= $current == 'appointments.php' ? 'active' : '' ?>">
                    <a href="appointments.php">
                        <span class="menu-icon"><i class="fa-solid fa-calendar-check"></i></span>
                        <span class="menuText">Appointments</span>
                    </a>
                </li>

                <li class="<?= $current == 'stocks.php' ? 'active' : '' ?>">
                    <a href="stocks.php">
                        <span class="menu-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                        <span class="menuText">Stocks Inventory</span>
                    </a>
                </li>

                <li class="<?= $current == 'medicines.php' ? 'active' : '' ?>">
                    <a href="medicines.php">
                        <span class="menu-icon"><i class="fa-solid fa-pills"></i></span>
                        <span class="menuText">Medicines</span>
                    </a>
                </li>

                <li class="<?= $current == 'reports.php' ? 'active' : '' ?>">
                    <a href="reports.php">
                        <span class="menu-icon"><i class="fa-solid fa-chart-column"></i></span>
                        <span class="menuText">Reports</span>
                    </a>
                </li>

            <?php endif; ?>

        </ul>
    </nav>
</aside>