<?php
if (!isset($user)) {
    session_start();
    $user = $_SESSION['user'];
}

$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <h2 class="logo">MedLog</h2>

    <div class="user">
        <img src="Images/UserIcon.jpg" class="userImage">
        <span class="menuText"><?php echo htmlspecialchars($user['name']); ?></span>
    </div>

    <nav class="menu">
        <ul>
            <li class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="menuText">Dashboard</span>
                </a>
            </li>

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

            <li>
                <a href="reports.php">
                    <i class="fas fa-chart-line"></i>
                    <span class="menuText">Reports</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>