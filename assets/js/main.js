// Active Link Script 
document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.pathname.split('/').pop();
    const tabs = document.querySelectorAll('.nav-tabs a');

    tabs.forEach(tab => {
        if (tab.getAttribute('href') === currentPage) {
            tab.classList.add('active');
        }
    });
});



document.getElementById('logout-link').addEventListener('click', (e) => {
    e.preventDefault();
    showAlert('Are you sure you want to logout?', () => {
        window.location.href = 'api/logout';
    }, null, 'Logout', 'Cancel');
});

// Graphs Toggle Script
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggle-graphs-btn');
    if (toggleBtn) {
        const contentArea = document.querySelector('.content-area');

        // Load initial state (Default to hidden if not explicitly 'false')
        const isHidden = localStorage.getItem('graphs-hidden') !== 'false';
        if (isHidden) {
            contentArea.classList.add('graphs-hidden');
            toggleBtn.classList.remove('active');
            localStorage.setItem('graphs-hidden', 'true'); // Persist default
        } else {
            toggleBtn.classList.add('active');
        }

        toggleBtn.addEventListener('click', function () {
            contentArea.classList.toggle('graphs-hidden');
            const nowHidden = contentArea.classList.contains('graphs-hidden');
            localStorage.setItem('graphs-hidden', nowHidden);

            // Toggle active class on button for visual feedback
            toggleBtn.classList.toggle('active', !nowHidden);

            // Re-render graphs if shown to fix layout issues (optional but good for Chart.js)
            if (!nowHidden) {
                if (typeof applyFilters === 'function') {
                    applyFilters();
                } else if (typeof loadGraphs === 'function' && typeof loggedUserId !== 'undefined') {
                    loadGraphs([loggedUserId]);
                }
            }
        });
    }
});
// Sidebar Toggle Script
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    if (sidebarToggleBtn) {
        // Load initial state (now handled by early-execution script in <head>, but we sync it here for safety)
        const isSidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isSidebarCollapsed) {
            document.documentElement.classList.add('sidebar-collapsed');
        }

        sidebarToggleBtn.addEventListener('click', function () {
            document.documentElement.classList.toggle('sidebar-collapsed');
            const nowCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', nowCollapsed);

            // Also ensure body class is in sync for legacy rules
            if (nowCollapsed) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        });
    }
});
