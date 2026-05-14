<header class="topnav">
    <button type="button" class="toggle-btn" aria-label="Toggle sidebar">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>

    <div class="actions">
        <a href="auth/logout.php" class="logout" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.querySelector('.toggle-btn');
    var sidebar = document.querySelector('.sidebar');

    function medlogStudentCompactNav() {
        return document.body.classList.contains('medlog-student-shell') && window.matchMedia('(max-width: 1024px)').matches;
    }

    function bindToggle() {
        if (!toggleBtn || !sidebar) {
            return;
        }
        toggleBtn.addEventListener('click', function (e) {
            if (medlogStudentCompactNav()) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            sidebar.classList.toggle('collapsed');
        });
    }

    bindToggle();

    window.addEventListener('resize', function () {
        if (medlogStudentCompactNav() && sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
        }
    });

    var logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            if (!confirm("Are you sure you want to log out?")) {
                e.preventDefault();
            }
        });
    }
});
</script>
