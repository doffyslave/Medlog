<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? 'guest';
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <h2 class="logo">MedLog</h2>

    <div class="user">
        <img src="Images/UserIcon.jpg" class="userImage">
        <span class="menuText">
            <?= htmlspecialchars($user['name'] ?? 'User') ?>
        </span>
        <small style="color:gray;">
            <?= ucfirst($role) ?>
        </small>
    </div>

    <nav class="menu">
        <ul>

            <!-- ALWAYS -->
            <li class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="menuText">Dashboard</span>
                </a>
            </li>

            <!-- ================= STUDENT VIEW ================= -->
            <?php if ($role === 'student'): ?>

                <li class="<?= $current == 'profile.php' ? 'active' : '' ?>">
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <span class="menuText">Profile</span>
                    </a>
                </li>

                <li class="<?= $current == 'my_visits.php' ? 'active' : '' ?>">
                    <a href="my_visits.php">
                        <i class="fas fa-notes-medical"></i>
                        <span class="menuText">My Visits</span>
                    </a>
                </li>

                <li class="<?= $current == 'medicines.php' ? 'active' : '' ?>">
                    <a href="medicines.php">
                        <i class="fas fa-capsules"></i>
                        <span class="menuText">Medicines</span>
                    </a>
                </li>

                <li class="<?= $current == 'clinic_info.php' ? 'active' : '' ?>">
                    <a href="clinic_info.php">
                        <i class="fas fa-clinic-medical"></i>
                        <span class="menuText">Clinic Info</span>
                    </a>
                </li>

            <?php endif; ?>

            <!-- ================= ADMIN VIEW ================= -->
            <?php if ($role === 'admin'): ?>

                <li class="<?= $current == 'patients.php' ? 'active' : '' ?>">
                    <a href="patients.php">
                        <i class="fas fa-user-injured"></i>
                        <span class="menuText">Patients</span>
                    </a>
                </li>

                <li class="<?= $current == 'visits.php' ? 'active' : '' ?>">
                    <a href="visits.php">
                        <i class="fas fa-notes-medical"></i>
                        <span class="menuText">Visits</span>
                    </a>
                </li>

                <li class="<?= $current == 'stocks.php' ? 'active' : '' ?>">
                    <a href="stocks.php">
                        <i class="fas fa-pills"></i>
                        <span class="menuText">Stocks Inventory</span>
                    </a>
                </li>

                <li class="<?= $current == 'medicines.php' ? 'active' : '' ?>">
                    <a href="medicines.php">
                        <i class="fas fa-capsules"></i>
                        <span class="menuText">Medicines</span>
                    </a>
                </li>

                <li class="<?= $current == 'reports.php' ? 'active' : '' ?>">
                    <a href="reports.php">
                        <i class="fas fa-chart-line"></i>
                        <span class="menuText">Reports</span>
                    </a>
                </li>

            <?php endif; ?>

        </ul>
    </nav>
</aside>