// Minimal JS for InventarisKu
document.addEventListener('DOMContentLoaded', function () {
    // Sembunyikan notifikasi setelah 6 detik agar tidak menutupi konten.
    if (window.bootstrap && bootstrap.Alert) {
        document.querySelectorAll('.alert').forEach(function (alert) {
            setTimeout(function () {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }, 6000);
        });
    }

    // Drawer navigasi untuk tablet & mobile.
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');
    const backdrop = document.getElementById('sidebarBackdrop');
    const mobileQuery = window.matchMedia('(max-width: 991.98px)');

    if (!sidebar || !toggle || !backdrop) {
        return;
    }

    let closeTimer = null;

    function openSidebar() {
        if (!mobileQuery.matches) {
            return;
        }
        clearTimeout(closeTimer);
        backdrop.hidden = false;
        body.classList.add('sidebar-open');
        toggle.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(function () {
            backdrop.classList.add('show');
        });
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeSidebar(returnFocus) {
        if (!body.classList.contains('sidebar-open')) {
            return;
        }
        body.classList.remove('sidebar-open');
        toggle.setAttribute('aria-expanded', 'false');
        backdrop.classList.remove('show');
        closeTimer = setTimeout(function () {
            if (!body.classList.contains('sidebar-open')) {
                backdrop.hidden = true;
            }
        }, 260);
        if (returnFocus) {
            toggle.focus();
        }
    }

    toggle.addEventListener('click', function () {
        if (body.classList.contains('sidebar-open')) {
            closeSidebar(true);
        } else {
            openSidebar();
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            closeSidebar(true);
        });
    }

    backdrop.addEventListener('click', function () {
        closeSidebar(true);
    });

    // Tutup drawer setelah memilih menu (khusus mobile).
    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (mobileQuery.matches) {
                closeSidebar(false);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar(true);
        }
    });

    // Kembali ke layout desktop: pastikan drawer benar-benar tertutup.
    function handleBreakpointChange(event) {
        if (!event.matches) {
            closeSidebar(false);
        }
    }

    if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', handleBreakpointChange);
    } else if (typeof mobileQuery.addListener === 'function') {
        mobileQuery.addListener(handleBreakpointChange);
    }
});
