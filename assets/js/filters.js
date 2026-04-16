
// Filter Script
const filters = document.querySelectorAll('.filter-multiselect');

filters.forEach(filter => {
    const selectBox = filter.querySelector('.select-box');
    const options = filter.querySelector('.options');

    // Toggle dropdown
    selectBox.addEventListener('click', (e) => {
        e.stopPropagation();

        if (filter.classList.contains('active')) {
            filter.classList.remove('active');
            return;
        }

        filters.forEach(f => {
            if (f !== filter) f.classList.remove('active');
        });

        filter.classList.add('active');
    });

    if (options) {
        options.addEventListener('click', e => e.stopPropagation());
    }
});

// Close all on outside click
window.addEventListener('click', () => {
    filters.forEach(filter => {
        filter.classList.remove('active');
    });
});


// Select All Checkbox Script
document.querySelectorAll('.select-all').forEach(selectAllCheckbox => {
    selectAllCheckbox.addEventListener('change', function () {
        const container = this.closest('.filter-multiselect');
        const checkboxes = container.querySelectorAll('input[type="checkbox"]:not(.select-all)');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
});

// Sync "Select All" when individual checkboxes are changed
document.querySelectorAll('.filter-option').forEach(option => {
    option.addEventListener('change', function () {
        const container = this.closest('.filter-multiselect');
        const allCheckboxes = container.querySelectorAll('.filter-option');
        const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
        container.querySelector('.select-all').checked = allChecked;
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const customCheckbox = document.querySelector('.custom-range-checkbox');
    const dateRange = document.querySelector('.custom-date-range');
    const allCheckboxes = document.querySelectorAll('.timeline-filter:not(.custom-range-checkbox)');
    const fromDate = document.querySelector('.custom-from-date');
    const toDate = document.querySelector('.custom-to-date');

    // Set max attributes to today
    const today = new Date().toISOString().split('T')[0];
    fromDate.setAttribute('max', today);
    toDate.setAttribute('max', today);

    function validateDateRange() {
        if (fromDate.value && toDate.value && fromDate.value > toDate.value) {
            // alert("From date cannot be after To date.");
            toDate.value = '';
            fromDate.setAttribute('max', today); // Reset max to today
        }
    }

    fromDate.addEventListener('change', () => {
        if (fromDate.value) {
            toDate.min = fromDate.value;
        } else {
            toDate.min = "";
        }
        validateDateRange();
    });

    toDate.addEventListener('change', function () {
        validateDateRange();

        if (toDate.value) {
            fromDate.setAttribute('max', toDate.value);
        } else {
            // Reset to max = today if toDate is cleared
            fromDate.setAttribute('max', today);
        }
    });

    // Handle custom checkbox toggle
    customCheckbox.addEventListener('change', function () {
        if (this.checked) {
            dateRange.style.display = 'flex';

            // Uncheck all other timeline checkboxes
            allCheckboxes.forEach(cb => {
                cb.checked = false;
            });
        } else {
            dateRange.style.display = 'none';
        }
    });

    // Handle other timeline checkboxes
    allCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked) {
                // Uncheck all other timeline checkboxes
                allCheckboxes.forEach(otherCb => {
                    if (otherCb !== this) {
                        otherCb.checked = false;
                    }
                });

                // Uncheck custom and hide date range
                customCheckbox.checked = false;
                dateRange.style.display = 'none';
            }
        });
    });

    // Set initial visibility based on custom checkbox state
    dateRange.style.display = customCheckbox.checked ? 'block' : 'none';
});


// Filter Employee names on Input
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('.employee-filter-input');
    const employeeOptions = document.querySelectorAll('.employee-option');

    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            console.log('search input event run');
            
            const filterValue = searchInput.value.toLowerCase();

            employeeOptions.forEach(option => {
                const labelText = option.textContent.toLowerCase();
                if (labelText.includes(filterValue)) {
                    option.style.display = 'flex';
                } else {
                    option.style.display = 'none';
                }
            });
        });
    }
});



document.getElementById('clear-filter-btn').addEventListener('click', function () {
    // Uncheck all checkboxes
    document.querySelectorAll('.filter-option, .select-all, .timeline-filter, .employee-filter').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Clear the employee search input
    if (document.querySelector('.employee-filter-input')) document.querySelector('.employee-filter-input').value = '';
    console.log('Filters cleared.');

    applyFilters();
});


