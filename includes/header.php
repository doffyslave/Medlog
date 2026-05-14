<header class="topnav topnav--shell" role="banner">
    <button type="button" class="toggle-btn" aria-label="Open menu">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.querySelector('.sidebar');
    var headerToggle = document.querySelector('.topnav .toggle-btn');
    var collapseInSidebar = document.querySelector('.sidebar-collapse-btn');

    function medlogStudentCompactNav() {
        return document.body.classList.contains('medlog-student-shell') && window.matchMedia('(max-width: 1024px)').matches;
    }

    function toggleSidebar() {
        if (!sidebar) {
            return;
        }
        if (medlogStudentCompactNav()) {
            return;
        }
        sidebar.classList.toggle('collapsed');
    }

    if (headerToggle && sidebar) {
        headerToggle.addEventListener('click', function (e) {
            if (medlogStudentCompactNav()) {
                e.preventDefault();
                return;
            }
            toggleSidebar();
        });
    }

    if (collapseInSidebar && sidebar) {
        collapseInSidebar.addEventListener('click', function (e) {
            e.preventDefault();
            if (medlogStudentCompactNav()) {
                return;
            }
            toggleSidebar();
        });
    }

    window.addEventListener('resize', function () {
        if (medlogStudentCompactNav() && sidebar && sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
        }
    });

    function bindLogoutConfirm(els) {
        if (!els || !els.length) {
            return;
        }
        els.forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (!confirm('Are you sure you want to log out?')) {
                    e.preventDefault();
                }
            });
        });
    }

    bindLogoutConfirm(document.querySelectorAll('[data-confirm-logout="1"], #sidebarLogoutBtn'));
});
</script>
