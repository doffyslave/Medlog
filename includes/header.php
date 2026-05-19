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

    if (sidebar && (sidebar.classList.contains('sidebar-student') || sidebar.classList.contains('sidebar-teacher'))) {
        var dockList = sidebar.querySelector('.sidebar-nav-list');
        var dockMoveTimer = null;

        function updateDockIndicator(animate) {
            if (!dockList) {
                return;
            }
            var activeItem = dockList.querySelector('li.active');
            if (!activeItem) {
                return;
            }
            var listRect = dockList.getBoundingClientRect();
            var itemRect = activeItem.getBoundingClientRect();
            var inset = window.matchMedia('(max-width: 640px)').matches ? 3 : 4;
            var left = Math.max(0, itemRect.left - listRect.left + inset);
            var width = Math.max(0, itemRect.width - (inset * 2));
            var center = itemRect.left - listRect.left + (itemRect.width / 2);
            dockList.style.setProperty('--dock-active-left', left + 'px');
            dockList.style.setProperty('--dock-active-width', width + 'px');
            dockList.style.setProperty('--dock-active-center', center + 'px');

            if (!dockList.classList.contains('dock-ready')) {
                window.requestAnimationFrame(function () {
                    dockList.classList.add('dock-ready');
                });
                return;
            }

            if (animate) {
                dockList.classList.add('dock-moving');
                window.clearTimeout(dockMoveTimer);
                dockMoveTimer = window.setTimeout(function () {
                    dockList.classList.remove('dock-moving');
                }, 460);
            }
        }

        updateDockIndicator(false);

        sidebar.querySelectorAll('.sidebar-nav-list > li > a').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var item = link.closest('li');
                if (!item || link.matches('[data-confirm-logout="1"]') || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                    return;
                }
                var targetUrl = new URL(link.href, window.location.href);
                var samePage = targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search;
                if (targetUrl.origin === window.location.origin) {
                    e.preventDefault();
                }
                sidebar.querySelectorAll('.sidebar-nav-list > li.active').forEach(function (activeItem) {
                    activeItem.classList.remove('active');
                });
                item.classList.add('active');
                updateDockIndicator(true);
                if (targetUrl.origin === window.location.origin && !samePage) {
                    window.setTimeout(function () {
                        window.location.href = targetUrl.href;
                    }, 320);
                }
            });
        });

        window.addEventListener('resize', function () {
            updateDockIndicator(false);
        });
        window.addEventListener('load', function () {
            updateDockIndicator(false);
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
