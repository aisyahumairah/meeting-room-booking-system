/**
 * Room Filters JavaScript
 * Handles auto-submit on select change and time validation
 */
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');

    if (!filterForm) return;

    const startTimeSelect = document.getElementById('startTimeSelect');
    const endTimeSelect = document.getElementById('endTimeSelect');

    // Auto-submit on select change (capacity only for UX)
    filterForm.querySelectorAll('select[name="capacity"]').forEach(select => {
        select.addEventListener('change', function () {
            filterForm.submit();
        });
    });

    // Validate time range
    if (endTimeSelect && startTimeSelect) {
        endTimeSelect.addEventListener('change', function () {
            if (startTimeSelect.value && this.value && this.value <= startTimeSelect.value) {
                // Show toast notification if available, otherwise alert
                if (typeof showToast === 'function') {
                    showToast('End time must be after start time', 'warning');
                } else {
                    alert('End time must be after start time');
                }
                this.value = '';
            }
        });

        startTimeSelect.addEventListener('change', function () {
            // Clear end time if it becomes invalid
            if (endTimeSelect.value && this.value && endTimeSelect.value <= this.value) {
                endTimeSelect.value = '';
            }
        });
    }

    // Initialize tooltips for amenity badges if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[title]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }
});
