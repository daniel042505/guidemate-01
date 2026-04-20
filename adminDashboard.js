document.addEventListener('DOMContentLoaded', function () {
    if (typeof feather !== 'undefined' && typeof feather.replace === 'function') {
        feather.replace();
    }

    var dashSelect = document.getElementById('dashboardLocationSelect');
    if (!dashSelect) {
        return;
    }

    function updateDashboardSelectColor() {
        if (!dashSelect.value) {
            dashSelect.classList.add('placeholder-color');
        } else {
            dashSelect.classList.remove('placeholder-color');
        }
    }

    dashSelect.addEventListener('change', updateDashboardSelectColor);
    updateDashboardSelectColor();
});
