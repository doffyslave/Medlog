<?php
/**
 * Mobile-only bottom navigation for students.
 * Include once after </main> (still inside .dashboard) so it never sits between sidebar and content.
 */
$user = $_SESSION['user'] ?? null;
$role = strtolower(trim((string) ($user['role'] ?? 'guest')));
if ($role !== 'student') {
    return;
}
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="medlog-bottom-nav" aria-label="Primary navigation">
    <a href="dashboard.php" class="medlog-bottom-nav__item <?= $current === 'dashboard.php' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
        <span>Dashboard</span>
    </a>
    <a href="profile.php" class="medlog-bottom-nav__item <?= $current === 'profile.php' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-user" aria-hidden="true"></i>
        <span>Profile</span>
    </a>
    <a href="my_visits.php" class="medlog-bottom-nav__item <?= $current === 'my_visits.php' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
        <span>My visits</span>
    </a>
    <a href="appointments.php" class="medlog-bottom-nav__item <?= $current === 'appointments.php' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
        <span>Appointments</span>
    </a>
    <a href="medicines.php" class="medlog-bottom-nav__item <?= $current === 'medicines.php' ? 'is-active' : '' ?>">
        <i class="fa-solid fa-pills" aria-hidden="true"></i>
        <span>Medicines</span>
    </a>
</nav>
