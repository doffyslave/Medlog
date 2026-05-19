<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$role = strtolower(trim((string) ($user['role'] ?? 'guest')));
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar sidebar-<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" aria-label="Main navigation">
    <div class="sidebar-brand">
        <a href="dashboard.php" class="sidebar-brand-mark" title="MedLog home">
            <img src="Images/FinalLogo.png?v=20260517" alt="MedLog" class="sidebar-brand-logo" width="40" height="40" decoding="async">
        </a>
        <div class="sidebar-brand-text">
            <span class="logo">MedLog</span>
        </div>
        <button type="button" class="sidebar-collapse-btn" aria-label="Collapse or expand sidebar" title="Toggle sidebar">
            <i class="fas fa-angles-left" aria-hidden="true"></i>
        </button>
    </div>

    <div class="user">
        <img src="Images/UserIcon.jpg" class="userImage" alt="" width="48" height="48" decoding="async">
        <div class="user-copy">
            <span class="menuText user-name">
                <?= htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span class="user-role-badge"><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <nav class="menu" aria-label="Primary">
        <p class="section-title">General</p>
        <ul class="sidebar-nav-list">

            <li class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php">
                    <span class="menu-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span>
                    <span class="menuText">Dashboard</span>
                </a>
            </li>

            <?php if ($role === 'student'): ?>

                <li class="<?= $current === 'profile.php' ? 'active' : '' ?>">
                    <a href="profile.php">
                        <span class="menu-icon"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                        <span class="menuText">Profile</span>
                    </a>
                </li>

                <li class="<?= $current === 'my_visits.php' ? 'active' : '' ?>">
                    <a href="my_visits.php">
                        <span class="menu-icon"><i class="fa-regular fa-clipboard" aria-hidden="true"></i></span>
                        <span class="menuText">My Visits</span>
                    </a>
                </li>

                <li class="<?= $current === 'appointments.php' ? 'active' : '' ?>">
                    <a href="appointments.php">
                        <span class="menu-icon"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span>
                        <span class="menuText">Appointments</span>
                    </a>
                </li>

                <li class="<?= $current === 'medicines.php' ? 'active' : '' ?>">
                    <a href="medicines.php">
                        <span class="menu-icon"><i class="fa-solid fa-pills" aria-hidden="true"></i></span>
                        <span class="menuText">Medicines</span>
                    </a>
                </li>

                <li class="sidebar-nav-dock-logout" aria-hidden="false">
                    <a href="auth/logout.php" class="sidebar-dock-logout-link" data-confirm-logout="1" title="Log out">
                        <span class="menu-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
                        <span class="menuText">Logout</span>
                    </a>
                </li>

            <?php elseif ($role === 'teacher'): ?>

                <li class="<?= $current === 'appointments.php' ? 'active' : '' ?>">
                    <a href="appointments.php">
                        <span class="menu-icon"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span>
                        <span class="menuText">Appointments</span>
                    </a>
                </li>

                <li class="<?= $current === 'medicines.php' ? 'active' : '' ?>">
                    <a href="medicines.php">
                        <span class="menu-icon"><i class="fa-solid fa-pills" aria-hidden="true"></i></span>
                        <span class="menuText">Medicines</span>
                    </a>
                </li>

            <?php endif; ?>

            <?php if ($role === 'admin'): ?>

                <li class="<?= $current === 'patients.php' ? 'active' : '' ?>">
                    <a href="patients.php">
                        <span class="menu-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                        <span class="menuText">Patients</span>
                    </a>
                </li>

                <li class="<?= $current === 'visits.php' ? 'active' : '' ?>">
                    <a href="visits.php">
                        <span class="menu-icon"><i class="fa-regular fa-clipboard" aria-hidden="true"></i></span>
                        <span class="menuText">Visits</span>
                    </a>
                </li>

                <li class="<?= $current === 'appointments.php' ? 'active' : '' ?>">
                    <a href="appointments.php">
                        <span class="menu-icon"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span>
                        <span class="menuText">Appointments</span>
                    </a>
                </li>

                <li class="<?= $current === 'stocks.php' ? 'active' : '' ?>">
                    <a href="stocks.php">
                        <span class="menu-icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></span>
                        <span class="menuText">Stocks Inventory</span>
                    </a>
                </li>

                <li class="<?= $current === 'medicines.php' ? 'active' : '' ?>">
                    <a href="medicines.php">
                        <span class="menu-icon"><i class="fa-solid fa-pills" aria-hidden="true"></i></span>
                        <span class="menuText">Medicines</span>
                    </a>
                </li>

                <li class="<?= $current === 'reports.php' ? 'active' : '' ?>">
                    <a href="reports.php">
                        <span class="menu-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
                        <span class="menuText">Reports</span>
                    </a>
                </li>

                <li class="sidebar-nav-dock-logout" aria-hidden="false">
                    <a href="auth/logout.php" class="sidebar-dock-logout-link" data-confirm-logout="1" title="Log out">
                        <span class="menu-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
                        <span class="menuText">Logout</span>
                    </a>
                </li>

            <?php endif; ?>

        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="auth/logout.php" class="sidebar-logout" id="sidebarLogoutBtn" data-confirm-logout="1">
            <span class="sidebar-logout-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
            <span class="sidebar-logout-text">Log out</span>
        </a>
    </div>
</aside>





