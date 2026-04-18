<header class="topnav">
    <div class="toggle-btn">
        <i class="fas fa-bars"></i>
    </div>

    <div class="actions">
        <a href="auth/logout.php" class="logout" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const toggleBtn = document.querySelector('.toggle-btn');
    const sidebar = document.querySelector('.sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Logout confirmation
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            if (!confirm("Are you sure you want to log out?")) {
                e.preventDefault();
            }
        });
    }

});
</script>