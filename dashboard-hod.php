<?php
header("X-Robots-Tag: noindex, nofollow", true);

// require_once 'config/session_init.php';
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Task.php';
// require_once 'classes/TaskCategory.php';

requireRole(['hod', 'admin']);

$emp_id = $_SESSION['emp_id'];
$empObj = new Employee($conn);
$taskObj = new Task($conn);

$employee = $empObj->getEmployeeById($emp_id);
$deptEmployees = $empObj->getEmployeesByDeptId($employee['dept_id']);
$deptEmployeeIds = array_column($deptEmployees, 'emp_id');
$repManagers = array_values(array_filter($deptEmployees, fn($emp) => $emp['role'] == 'rm'));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Home</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .description-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }

        .description-cell:hover {
            border: 2px solid yellow;
        }

        #desc-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        #desc-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            position: relative;
        }

        #desc-modal-body {
            padding: 15px;
            min-width: 400px;
            max-width: 600px;
            max-height: 80vh;
            min-height: 200px;
            overflow-y: auto;
            font-size: .9rem;
        }

        #desc-modal-close {
            position: absolute;
            top: 10px;
            right: 12px;
            cursor: pointer;
            font-size: 25px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <!-- Header Dblist-->
    <!-- Header removed -->
    <?php
    include_once 'includes/sidebar-hod.php';
    ?>
    <main>
        <div class="top-row">
            <!-- <h3 class="">Home</h3> -->
            <div class="left">
                <!-- Filter Boxes -->
                <!-- <div class="filters"> -->
                <div class="filter-multiselect">
                    <div class="select-box">Rep. Manager <i class="fa-solid fa-caret-down"></i></div>
                    <div class="options">
                        <label><input type="checkbox" class="select-all" value="">Select All</label>
                        <?php
                        foreach ($repManagers as $repManager) {
                            ?>
                            <label class="rm-option">
                                <input type="checkbox" class="filter-option rm-filter" value="<?= $repManager['emp_id'] ?>"
                                    data-dept-id="<?= $repManager['dept_id'] ?>"><?= $repManager['name'] ?>
                            </label>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="filter-multiselect">
                    <div class="select-box">Employee Name <i class="fa-solid fa-caret-down"></i></div>
                    <div class="options">
                        <label><input type="text" class="employee-filter-input" name="emp-name"
                                placeholder="Search employee..."></label>
                        <label><input type="checkbox" class="employee-filter select-all" value=""> Select All</label>

                        <?php foreach ($deptEmployees as $deptEmployee) { ?>
                            <label class="employee-option">
                                <input type="checkbox" class="filter-option employee-filter"
                                    value="<?= $deptEmployee['emp_id'] ?>"
                                    data-dept-id="<?= $deptEmployee['dept_id'] ?>"
                                    data-rm-id="<?= $deptEmployee['rm_id'] ?>">
                                <?= $deptEmployee['name'] ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="filter-multiselect" id="status-filter">
                    <div class="select-box">Task Status <i class="fa-solid fa-caret-down"></i></div>
                    <div class="options">
                        <label><input type="checkbox" class="status-filter select-all" value=""> Select All</label>
                        <label><input type="checkbox" class="filter-option status-filter" value="0"> Approval Pending
                        </label>
                        <label><input type="checkbox" class="filter-option status-filter" value="1"> Approved
                        </label>
                        <label><input type="checkbox" class="filter-option status-filter" value="-1"> Unapproved
                        </label>
                    </div>
                </div>
                <div class="filter-multiselect">
                    <div class="select-box">Timeline<i class="fa-solid fa-caret-down"></i></div>
                    <div class="options">
                        <!-- <label><input type="checkbox" class="select-all timeline-filter" value="">Select All</label> -->
                        <!-- <label><input type="checkbox" class="filter-option timeline-filter" value="today"> Today</label> -->
                        <label><input type="checkbox" class="filter-option timeline-filter" value="last7"> Last 7
                            Days</label>
                        <label><input type="checkbox" class="filter-option timeline-filter" value="last30"> Last 30
                            Days</label>
                        <!-- <label><input type="checkbox" class="filter-option timeline-filter" value="lastyear"> Last
                        Year</label> -->
                        <label id="custom-range-label">
                            <input type="checkbox" class="timeline-filter custom-range-checkbox" value="custom"> Custom
                            <div class="custom-date-range" style="display: none;">
                                From -
                                <input type="date" class="custom-from-date" placeholder="From">
                                To -
                                <input type="date" class="custom-to-date" placeholder="To">
                            </div>
                        </label>
                    </div>
                </div>

                <!-- </div> -->
            </div>
            <div class="right">
                <button class="action-btn" id="download-list-btn"><i class="fa-solid fa-download"></i></button>
                <button class="action-btn" id="clear-filter-btn">Clear Filters</button>
            </div>

        </div>
        <div class="active-filters" id="active-filters"></div>
        <div class="content-area">
            <div class="tab-container">
                <div class="tab-links" role="tablist">
                    <button class="tab-link active" role="tab" aria-selected="true" data-tab="all">All</button>
                    <button class="tab-link" role="tab" aria-selected="false" data-tab="missed">Not Filled</button>
                    <button class="tab-link" role="tab" aria-selected="false" data-tab="pending">Approval
                        Pending</button>
                    <button class="tab-link" role="tab" aria-selected="false" data-tab="lowhours">Low Work
                        Hours</button>
                    <button class="tab-link" role="tab" aria-selected="false" data-tab="special">Special
                        Cases</button>
                </div>
                <div class="tab-content">
                    <div class="tab-panel active" id="tab-all" role="tabpanel">
                        <div class="tasklist grid-item">
                            <div class="task-table-wrapper">
                                <table id="tasktable">
                                    <thead id="tasktablehead">
                                        <tr>
                                            <th class="checkbox"><input type="checkbox" id="select-all"></th>
                                            <th>Date</th>
                                            <th>Emp Id</th>
                                            <th>Name</th>
                                            <th>Working/Leave</th>
                                            <th>Duration</th>
                                            <th><?= getClientLabel() ?></th>
                                            <th>Nature of Task</th>
                                            <th>Task Brief</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasktablebody">
                                        <!-- Employees Tasks to be fetched via Ajax -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination" id="taskPagination">
                                <div class="rows-per-page">
                                    <label>Rows:
                                        <select id="rows-per-page-select">
                                            <option value="50">50</option>
                                            <option value="100" selected>100</option>
                                            <option value="500">500</option>
                                            <option value="1000">1000</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="pagination-controls">
                                    <button class="first">First</button>
                                    <button class="prev">Prev</button>
                                    <span class="pages"><button class="page-btn active">1</button></span>
                                    <button class="next">Next</button>
                                    <button class="last">Last</button>
                                </div>
                            </div>
                        </div>
                        <div class="graphs-wrapper">
                            <div class="graph graph1 grid-item">
                                <h5>Weekly Work Hours</h5>
                                <!-- Area Chart -->
                                <canvas id="hoursChart"></canvas>
                            </div>
                            <div class="graph graph2 grid-item">
                                <h5>Leaves</h5>
                                <!-- Line Chart -->
                                <canvas id="leavesChart"></canvas>
                            </div>
                            <div class="graph graph3 grid-item">
                                <h5>Working Days</h5>
                                <!-- Pie Chart -->
                                <canvas id="workdaysChart"></canvas>
                            </div>
                        </div>

                    </div>
                    <!-- <div class="tab-panel active" id="tab-all" role="tabpanel">
                        <div class="tasklist grid-item">
                            <div class="task-table-wrapper">
                                <table id="tasktable-all">
                                    <thead>
                                        <tr>
                                            <th class="checkbox"><input type="checkbox" id="select-all"></th>
                                            <th>Date</th>
                                            <th>Emp Id</th>
                                            <th>Name</th>
                                            <th>Working/Leave</th>
                                            <th>Nature of Task</th>
                                            <th>Task Brief</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasktablebody-all">
                                        Employees Tasks to be fetched via Ajax
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination" id="taskPagination">
                                <button class="first">First</button>
                                <button class="prev">Prev</button>
                                <span class="pages"><button class="page-btn active">1</button></span>
                                <button class="next">Next</button>
                                <button class="last">Last</button>
                            </div>
                        </div>
                        <div class="graph graph1 grid-item">
                            <h5>Weekly Work Hours</h5>
                            Area Chart
                            <canvas id="hoursChart"></canvas>
                        </div>
                        <div class="graph graph2 grid-item">
                            <h5>Leaves</h5>
                            Line Chart
                            <canvas id="leavesChart"></canvas>
                        </div>
                        <div class="graph graph3 grid-item">
                            <h5>Working Days</h5>
                            Pie Chart
                            <canvas id="workdaysChart"></canvas>
                        </div>
                    </div>

                    <div class="tab-panel" id="tab-missed" role="tabpanel">
                        <div class="tasklist grid-item">
                            <div class="task-table-wrapper">
                                <table id="tasktable-missed">
                                    <thead>
                                        <tr>
                                            <th class="checkbox"><input type="checkbox" id="select-all"></th>
                                            <th>Date</th>
                                            <th>Emp Id</th>
                                            <th>Name</th>
                                            <th>Dept.</th>
                                            <th>Sub Dept.</th>
                                            <th>Status</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasktablebody-missed">
                                        Employees Tasks to be fetched via Ajax
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination" id="taskPagination">
                                <button class="first">First</button>
                                <button class="prev">Prev</button>
                                <span class="pages"><button class="page-btn active">1</button></span>
                                <button class="next">Next</button>
                                <button class="last">Last</button>
                            </div>
                        </div>
                        <div class="graph graph1 grid-item">
                            <h5>Weekly Work Hours</h5>
                            Area Chart
                            <canvas id="hoursChart"></canvas>
                        </div>
                        <div class="graph graph2 grid-item">
                            <h5>Leaves</h5>
                            Line Chart
                            <canvas id="leavesChart"></canvas>
                        </div>
                        <div class="graph graph3 grid-item">
                            <h5>Working Days</h5>
                            Pie Chart
                            <canvas id="workdaysChart"></canvas>
                        </div>
                    </div>

                    <div class="tab-panel" id="tab-pending" role="tabpanel">
                        <div class="tasklist grid-item">
                            <div class="task-table-wrapper">
                                <table id="tasktable-pending">
                                    <thead>
                                        <tr>
                                            <th class="checkbox"><input type="checkbox" id="select-all"></th>
                                            <th>Date</th>
                                            <th>Emp Id</th>
                                            <th>Name</th>
                                            <th>Working/Leave</th>
                                            <th>Nature of Task</th>
                                            <th>Task Brief</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasktablebody-pending">
                                        Employees Tasks to be fetched via Ajax
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination" id="taskPagination">
                                <button class="first">First</button>
                                <button class="prev">Prev</button>
                                <span class="pages"><button class="page-btn active">1</button></span>
                                <button class="next">Next</button>
                                <button class="last">Last</button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-panel" id="tab-low-hours" role="tabpanel">
                        <div class="tasklist grid-item">
                            <div class="task-table-wrapper">
                                <table id="tasktable-low-hours">
                                    <thead>
                                        <tr>
                                            <th class="checkbox"><input type="checkbox" id="select-all"></th>
                                            <th>Date</th>
                                            <th>Emp Id</th>
                                            <th>Name</th>
                                            <th>Working/Leave</th>
                                            <th>Nature of Task</th>
                                            <th>Task Brief</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasktablebody-low-hours">
                                        Employees Tasks to be fetched via Ajax
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination" id="taskPagination">
                                <button class="first">First</button>
                                <button class="prev">Prev</button>
                                <span class="pages"><button class="page-btn active">1</button></span>
                                <button class="next">Next</button>
                                <button class="last">Last</button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-panel" id="tab-special-case" role="tabpanel">
                        <div class="tasklist grid-item">
                            <div class="task-table-wrapper">
                                <table id="tasktable-special-case">
                                    <thead>
                                        <tr>
                                            <th class="checkbox"><input type="checkbox" id="select-all"></th>
                                            <th>Date</th>
                                            <th>Emp Id</th>
                                            <th>Name</th>
                                            <th>Working/Leave</th>
                                            <th>Nature of Task</th>
                                            <th>Task Brief</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tasktablebody-special-case">
                                        Employees Tasks to be fetched via Ajax
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination" id="taskPagination">
                                <button class="first">First</button>
                                <button class="prev">Prev</button>
                                <span class="pages"><button class="page-btn active">1</button></span>
                                <button class="next">Next</button>
                                <button class="last">Last</button>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>


            <!-- 
            <div class="tasklist grid-item">
                <div class="task-table-wrapper">
                    <table id="tasktable">
                        <thead>
                            <tr>
                                <th class="checkbox"><input type="checkbox" id="select-all"></th>
                                <th>Date</th>
                                <th>Emp Id</th>
                                <th>Name</th>
                                <th>Working/Leave</th>
                                <th>Nature of Task</th>
                                <th>Task Brief</th>
                                <th>Description</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Comments</th>
                            </tr>
                        </thead>
                        <tbody id="tasktablebody">
                            Employees Tasks to be fetched via Ajax
                        </tbody>
                    </table>
                </div>
                <div class="pagination" id="taskPagination">
                    <button class="first">First</button>
                    <button class="prev">Prev</button>
                    <span class="pages"><button class="page-btn active">1</button></span>
                    <button class="next">Next</button>
                    <button class="last">Last</button>
                </div>
            </div> -->
            <!-- <div class="graph graph1 grid-item">
                <h5>Weekly Work Hours</h5>
                Area Chart
                <canvas id="hoursChart"></canvas>
            </div>
            <div class="graph graph2 grid-item">
                <h5>Leaves</h5>
                Line Chart
                <canvas id="leavesChart"></canvas>
            </div>
            <div class="graph graph3 grid-item">
                <h5>Working Days</h5>
                Pie Chart
                <canvas id="workdaysChart"></canvas>
            </div> -->
        </div>
    </main>
    <!-- <footer></footer> -->

    <!-- Message Toast -->
    <div id="toast">Saved Successfully!</div>

    <!-- Loading Spinner -->
    <div id="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Custom Alert -->
    <div id="custom-alert" class="alert-overlay">
        <div class="alert-box" id="alert-box">
            <p id="alert-message">Alert Message</p>
            <div class="alert-actions">
                <button id="alert-confirm-btn">Yes</button>
                <button id="alert-cancel-btn">No</button>
            </div>
        </div>
    </div>
    <button class="floating-toggle-btn" id="toggle-graphs-btn" title="Toggle Graphs"><i class="fa-solid fa-chart-column"></i></button>


    <!-- Task Description Modal -->
    <div id="desc-modal" style="display: none;">
        <div id="desc-modal-content">
            <div id="desc-modal-header">
                <h4 style="font-weight: 500">Task Description</h4>
                <span id="desc-modal-close">&times;</span>
            </div>
            <hr>
            <div id="desc-modal-body"></div>
        </div>
    </div>

    <script>
        // Show modal with full description
        document.addEventListener('click', function (e) {
            const target = e.target;
            if (target.classList.contains('description-cell')) {
                const fullText = target.dataset.full;
                if (fullText && fullText !== '-') {
                    document.getElementById('desc-modal-body').textContent = fullText;
                    document.getElementById('desc-modal').style.display = 'flex';
                }
            }
        });

        // Close modal
        document.getElementById('desc-modal-close').addEventListener('click', function () {
            document.getElementById('desc-modal').style.display = 'none';
        });

        // Optional: Close on overlay click
        document.getElementById('desc-modal').addEventListener('click', function (e) {
            if (e.target.id === 'desc-modal') {
                e.currentTarget.style.display = 'none';
            }
        });

    </script>

    <?php
    // Add task entry modal
    // include_once 'task-modal.php';
    // Edit Profile Photo Modal 
    include_once 'photo-modal.php';
    ?>

    <script>
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;

            if (isError) {
                toast.style.backgroundColor = '#ef828c';
            } else {
                // toast.style.backgroundColor = '#5eda7b';
            }

            toast.className = 'show';

            setTimeout(() => { toast.classList.remove('show') }, 3000);
        }

    </script>

    <!-- Task Modal Script -->
    <!-- <script>
        const addModal = document.getElementById('modal-dialog-common');
        // const addButton = document.getElementById('add-btn');
        const openButton = document.getElementById('open-modal-btn');
        const closeButton = document.getElementById('close-btn');

        openButton.addEventListener('click', function (e) {
            addModal.classList.add('show');
        });

        // Close modal
        closeButton.addEventListener('click', () => {
            addModal.classList.remove('show');
        });

        window.addEventListener('click', (e) => {
            if (addModal === e.target) addModal.classList.remove('show');
        })
    </script> -->

    <!-- Graphs Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="assets/js/graph.js"></script>

    <!-- <script src="assets/js/filters.js"></script> -->

    <!-- globally declare currentTab -->
    <script>
        let currentTab = "all"; // default active tab
        const statusFilter = document.getElementById('status-filter');
    </script>

    <!-- Filters handling script -->
    <!-- <script src="assets/js/filters.js"></script> -->
    <script>

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


            updateAllFilters();
            currentPage = 1;
            loadTasks(currentTab, currentPage, limit, getFilterValues());
            // applyFilters();
        });

    </script>

    <script>

        document.addEventListener("DOMContentLoaded", () => {
            const deptCheckboxes = document.querySelectorAll(".department-filter");
            const rmOptions = document.querySelectorAll(".rm-option");
            const rmCheckboxes = document.querySelectorAll(".rm-filter");
            const employeeOptions = document.querySelectorAll(".employee-option");

            function getSelectedValues(checkboxes) {
                return Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
            }

            function updateRMFilter(selectedDepts) {
                rmOptions.forEach(option => {
                    const checkbox = option.querySelector("input.rm-filter");
                    const deptId = checkbox.dataset.deptId;

                    if (selectedDepts.length === 0 || selectedDepts.includes(deptId)) {
                        option.style.display = "flex";
                    } else {
                        option.style.display = "none";
                        checkbox.checked = false; // Uncheck if hidden
                    }
                });
            }

            function updateEmployeeFilter(selectedDepts, selectedRMs) {
                employeeOptions.forEach(option => {
                    const checkbox = option.querySelector("input.employee-filter");
                    const empDeptId = checkbox.dataset.deptId;
                    const empRmId = checkbox.dataset.rmId;

                    const showByDept = selectedDepts.length === 0 || selectedDepts.includes(empDeptId);
                    const showByRM = selectedRMs.length === 0 || selectedRMs.includes(empRmId);

                    if (showByDept && showByRM) {
                        option.style.display = "flex";
                    } else {
                        option.style.display = "none";
                        checkbox.checked = false; // Uncheck if hidden
                    }
                });
            }

            function updateAllFilters() {
                const selectedDepts = getSelectedValues(deptCheckboxes);
                const selectedRMs = getSelectedValues(rmCheckboxes);

                updateRMFilter(selectedDepts);
                updateEmployeeFilter(selectedDepts, selectedRMs);
            }

            // Make updateAllFilters() accessible to window level
            window.updateAllFilters = updateAllFilters;

            deptCheckboxes.forEach(cb => cb.addEventListener("change", updateAllFilters));
            rmCheckboxes.forEach(cb => cb.addEventListener("change", updateAllFilters));

            updateAllFilters(); // Initial run
        });
    </script>


    <script>
        const loggedUserId = <?= $emp_id ?>;
        const allowedEmployees = <?= json_encode($deptEmployeeIds ?? $teamEmployeeIds ?? []) ?>;
        // console.log(allowedEmployees);
    </script>

    <script>

        let currentPage = 1;
        let limit = 100; // default rows per page

        document.addEventListener("DOMContentLoaded", function () {
            const taskTable = document.getElementById("tasktable");

            console.log(taskTable);
            const tbody = taskTable.querySelector("#tasktablebody");

            const tabs = document.querySelectorAll(".tab-links .tab-link");
            // let currentTab = "all"; // default active tab

            // --- Load default tab on page load ---
            loadTasks(currentTab, currentPage, limit, getFilterValues());


            const filterInputs = document.querySelectorAll('.filter-option, .timeline-filter, .rm-filter, .employee-filter, .status-filter, .custom-from-date, .custom-to-date');

            filterInputs.forEach(input => {
                input.addEventListener('change', () => {
                    currentPage = 1;
                    loadTasks(currentTab, currentPage, limit, getFilterValues())
                });
            });

            // --- Tab click event ---
            tabs.forEach(tab => {
                tab.addEventListener("click", function (e) {
                    e.preventDefault();
                    tabs.forEach(t => t.classList.remove("active"));
                    this.classList.add("active");
                    currentTab = this.getAttribute("data-tab");
                    currentPage = 1;
                    handleFilterVisibility(currentTab);
                    loadTasks(currentTab, currentPage, limit, getFilterValues());
                });
            });

            function handleFilterVisibility(tab) {
                // Tabs where status filter should be hidden
                const hideFor = ['pending', 'missed', 'lowhours'];

                if (hideFor.includes(tab)) {
                    statusFilter.style.display = 'none';
                    clearStatusSelection(); // clear checkboxes so no status filter is applied
                } else {
                    statusFilter.style.display = 'block';
                }
            }

            function clearStatusSelection() {
                document.querySelectorAll('#status-filter input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
            }

            // --- Filter application ---
            // document.querySelector("#applyFiltersBtn").addEventListener("click", function () {
            //     loadTasks(currentTab, getFilterValues());
            // });

            // Make getFilterValues globally accessible
            // window.getFilterValues = getFilterValues;

            // --- Get filter values from panel ---
            // function getFilterValues() {
            //     // Adjust these selectors to match your filter inputs
            //     // const employee = document.querySelector("#filterEmployee")?.value || "";
            //     // const client = document.querySelector("#filterClient")?.value || "";
            //     // const startDate = document.querySelector("#filterStartDate")?.value || "";
            //     // const endDate = document.querySelector("#filterEndDate")?.value || "";

            //     const selectedDepartments = Array.from(document.querySelectorAll('.department-filter:checked')).map(el => el.value);
            //     const selectedManagers = Array.from(document.querySelectorAll('.rm-filter:checked')).map(el => el.value);
            //     const selectedEmployees = Array.from(document.querySelectorAll('.employee-filter:checked')).map(el => el.value);
            //     const selectedStatus = Array.from(document.querySelectorAll('.status-filter:checked')).map(el => el.value);

            //     const customCheckbox = document.querySelector('.custom-range-checkbox');
            //     let timeline = '';
            //     let customFrom = '';
            //     let customTo = '';

            //     if (customCheckbox.checked) {
            //         customFrom = document.querySelector('.custom-from-date').value;
            //         customTo = document.querySelector('.custom-to-date').value;
            //         timeline = 'custom';
            //     } else {
            //         const selectedTimelines = Array.from(document.querySelectorAll('.timeline-filter:checked')).map(el => el.value);
            //         if (selectedTimelines.length > 0) {
            //             timeline = selectedTimelines[0];
            //         }
            //     }

            //     return { selectedEmployees, selectedDepartments, selectedManagers, selectedStatus, timeline, customFrom, customTo };
            // }

            // Exposing loadTasks() to window scope to call globally
            window.loadTasks = loadTasks;

            // --- Main task loader ---
            function loadTasks(type, page, limit, filters = {}) {
                renderFilterTags();
                // Build the payload body
                const payload = {
                    taskType: type, // e.g. 'all', 'missed', 'pending', 'lowwork', 'special'
                    allowedEmployees: allowedEmployees,
                    employees: filters.selectedEmployees,
                    departments: filters.selectedDepartments,
                    managers: filters.selectedManagers,
                    status: filters.selectedStatus,
                    timeline: filters.timeline,
                    customFrom: filters.customFrom,
                    customTo: filters.customTo,
                    page: page || 1,
                    limit: limit || 100
                };

                showSpinner();
                fetch('api/filter-tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                    .then(res => res.json())
                    .then(data => {
                        let columns = [];
                        switch (type) {
                            case "missed": columns = ["Date", "Emp Id", "Name", "Email", "Rep. Manager"]; break;
                            case "lowhours": columns = ["Date", "Emp Id", "Name", "Email", "Rep. Manager", "Total Time"]; break;
                            case "special": columns = ["Date", "Occasion", "Emp Id", "Name", "Work Type", "Duration", "Client / Project", "Nature of Task", "Task Brief", "Description", "Status", "Comments"]; break;
                            default: columns = ["Date", "Emp Id", "Name", "Work Type", "Duration", "Client / Project", "Nature of Task", "Task Brief", "Description", "Status", "Comments"];
                        }

                        // Update table header
                        const thead = taskTable.querySelector("#tasktablehead");
                        thead.innerHTML = '<tr><th class="checkbox"><input type="checkbox" id="select-all"></th>' + columns.map(c => `<th>${c}</th>`).join("") + '</tr>';

                        // Update table body
                        tbody.innerHTML = "";
                        if (!data || !data.tasks || data.tasks.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="${columns.length}">No tasks found</td></tr>`;
                            renderPagination(0, 1, limit);
                            return;
                        }

                        renderPagination(data.total, data.page, data.limit);

                        // Render each row using insertAdjacentHTML
                        data.tasks.forEach(task => {
                            let row = `<tr data-task-id="${task.task_id}">`;
                            row += `<td class="checkbox"><input type="checkbox" class="select-task"></td>`;
                            columns.forEach(col => {
                                switch (col) {
                                    case "Date": row += `<td>${formatDate(task.date)}</td>`; break;
                                    case "Occasion": row += `<td>${(task.occasion)}</td>`; break;
                                    case "Emp Id": row += `<td>${task.emp_id}</td>`; break;
                                    case "Name": row += `<td>${task.name}</td>`; break;
                                    case "Email": row += `<td>${task.email}</td>`; break;
                                    case "Rep. Manager": row += `<td>${task.rm_name || "-"}</td>`; break;
                                    case "Work Type": row += `<td>${task.worktype || "-"}</td>`; break;
                                    case "Duration": row += `<td>${formatTime(task.duration) || "-"}</td>`; break;
                                    case "Client / Project": row += `<td>${task.client_name || '-'}</td>`; break;
                                    case "Nature of Task": row += `<td>${task.cat_name}</td>`; break;
                                    case "Task Brief": row += `<td>${task.brief || '-'}</td>`; break;
                                    case "Description": row += `<td class="description-cell" data-full="${task.description || '-'}">${task.description?.substring(0, 50) || ''}${task.description?.length > 50 ? '...' : ''}</td>`; break;
                                    case "Status": row += `<td>
                                      <select name="status-dropdown" class="status-dropdown ${task.status == '0' ? 'pending' : task.status == 1 ? 'approved' : 'unapproved'}">
                                            <option value="0" ${task.status == 0 ? 'selected' : ''} disabled hidden>Pending</option>
                                            <option value="1" ${task.status == 1 ? 'selected' : ''}>Approved</option>
                                            <option value="-1" ${task.status == -1 ? 'selected' : ''}>Unapproved</option>
                                        </select>`;
                                        break;
                                    case "Comments": row += `<td><input type="text" name="comment" value="${task.comment ?? ''}" placeholder="Add Comment" readonly></td>`; break;
                                    case "Actions": row += `<td>Actions</td>`; break;
                                }
                            });
                            row += "</tr>";
                            tbody.insertAdjacentHTML("beforeend", row);
                        });

                        const graphContainers = document.querySelectorAll('.graph');

                        if (filters.selectedEmployees.length === 1) {
                            // Case 1: Single employee selected
                            const contentArea = document.querySelector('.content-area');
                            if (!contentArea.classList.contains('graphs-hidden')) {
                                document.querySelectorAll('.graph').forEach(graph => graph.style.display = '');
                                loadGraphs([filters.selectedEmployees[0]]);
                            }
                        } else if (filters.selectedEmployees.length === 0 && filters.selectedManagers.length > 0) {
                            // Case 2: RM(s) selected, no specific employee selected — show only visible filtered employees
                            const allEmployeeInputs = document.querySelectorAll('.filter-option.employee-filter');
                            const filteredVisibleEmpIds = Array.from(allEmployeeInputs)
                                .filter(el => el.closest('.employee-option')?.style.display !== 'none')
                                .map(el => el.value);
                            ////////console.log(filteredVisibleEmpIds);

                            if (filteredVisibleEmpIds.length > 0) {
                                const contentArea2 = document.querySelector('.content-area');
                                if (!contentArea2.classList.contains('graphs-hidden')) {
                                    document.querySelectorAll('.graph').forEach(graph => graph.style.display = '');
                                    loadGraphs(filteredVisibleEmpIds);
                                }
                            } else {
                                hideGraphs();
                            }

                        } else {
                            // Case 3: Show department-wide aggregate graphs by default
                            const contentArea = document.querySelector('.content-area');
                            if (!contentArea.classList.contains('graphs-hidden')) {
                                document.querySelectorAll('.graph').forEach(graph => graph.style.display = '');
                                loadGraphs(allowedEmployees);
                            }
                        }
                        // console.log(payload);



                        renderFilterTags();
                    })
                    .catch(err => {
                        console.error("Error loading tasks:", err);
                        tbody.innerHTML = `<tr><td colspan="6">Error loading tasks</td></tr>`;
                    })
                    .finally(() => hideSpinner());
            }
        });

        // Rows per page
        const rowsSelect = document.getElementById('rows-per-page-select');
        if (rowsSelect) {
            rowsSelect.value = String(limit);
            rowsSelect.addEventListener('change', function () {
                limit = parseInt(this.value);
                currentPage = 1;
                loadTasks(currentTab, currentPage, limit, getFilterValues());
            });
        }

        // ---- Render Active Filter Tags ----
        function renderFilterTags() {
            const container = document.getElementById('active-filters');
            if (!container) return;
            container.innerHTML = '';
            const tagDefs = [
                { selector: '.timeline-filter:checked:not(.custom-range-checkbox)', category: 'Timeline', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.employee-filter:checked:not(.select-all)', category: 'Employee', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.status-filter:checked:not(.select-all)', category: 'Status', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.department-filter:checked:not(.select-all)', category: 'Dept', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.rm-filter:checked:not(.select-all)', category: 'Manager', labelFn: el => el.closest('label').textContent.trim() },
            ];
            tagDefs.forEach(({ selector, category, labelFn }) => {
                document.querySelectorAll(selector).forEach(el => {
                    const tag = buildTag(category, labelFn(el), () => { el.checked = false; el.dispatchEvent(new Event('change', { bubbles: true })); });
                    container.appendChild(tag);
                });
            });
            const customCb = document.querySelector('.custom-range-checkbox');
            if (customCb && customCb.checked) {
                const from = document.querySelector('.custom-from-date')?.value;
                const to = document.querySelector('.custom-to-date')?.value;
                if (from || to) {
                    container.appendChild(buildTag('Date Range', `${from || '?'} → ${to || '?'}`, () => { customCb.checked = false; customCb.dispatchEvent(new Event('change', { bubbles: true })); }));
                }
            }
        }
        function buildTag(category, label, onRemove) {
            const tag = document.createElement('span');
            tag.className = 'filter-tag';
            tag.innerHTML = `<span><strong>${category}:</strong> ${label.replace(/^\s+/, '')}</span>`;
            const btn = document.createElement('span');
            btn.className = 'tag-remove'; btn.textContent = '×'; btn.title = 'Remove filter';
            btn.addEventListener('click', onRemove);
            tag.appendChild(btn);
            return tag;
        }
        document.getElementById('clear-filter-btn')?.addEventListener('click', function () {
            document.querySelectorAll('.filter-option:checked, .timeline-filter:checked, .employee-filter:checked, .status-filter:checked, .department-filter:checked, .rm-filter:checked').forEach(el => { el.checked = false; });
            const fromDate = document.querySelector('.custom-from-date');
            const toDate = document.querySelector('.custom-to-date');
            if (fromDate) fromDate.value = ''; if (toDate) toDate.value = '';
            document.querySelector('.custom-date-range') && (document.querySelector('.custom-date-range').style.display = 'none');
            currentPage = 1; loadTasks(currentTab, currentPage, limit, getFilterValues());
        });

        function getFilterValues() {
            // Adjust these selectors to match your filter inputs
            // const employee = document.querySelector("#filterEmployee")?.value || "";
            // const client = document.querySelector("#filterClient")?.value || "";
            // const startDate = document.querySelector("#filterStartDate")?.value || "";
            // const endDate = document.querySelector("#filterEndDate")?.value || "";

            const selectedDepartments = Array.from(document.querySelectorAll('.department-filter:checked')).map(el => el.value);
            const selectedManagers = Array.from(document.querySelectorAll('.rm-filter:checked')).map(el => el.value);
            const selectedEmployees = Array.from(document.querySelectorAll('.employee-filter:checked')).map(el => el.value);
            const selectedStatus = Array.from(document.querySelectorAll('.status-filter:checked')).map(el => el.value);

            const customCheckbox = document.querySelector('.custom-range-checkbox');
            let timeline = '';
            let customFrom = '';
            let customTo = '';

            if (customCheckbox.checked) {
                customFrom = document.querySelector('.custom-from-date').value;
                customTo = document.querySelector('.custom-to-date').value;
                timeline = 'custom';
            } else {
                const selectedTimelines = Array.from(document.querySelectorAll('.timeline-filter:checked')).map(el => el.value);
                if (selectedTimelines.length > 0) {
                    timeline = selectedTimelines[0];
                }
            }


            return { selectedEmployees, selectedDepartments, selectedManagers, selectedStatus, timeline, customFrom, customTo };
        }

        function hideGraphs() {
            const graphContainers = document.querySelectorAll('.graph');
            graphContainers.forEach(graph => graph.style.display = 'none');
            document.querySelector('.tab-panel.active').style.display = 'flex';

            if (hoursChart instanceof Chart) {
                hoursChart.destroy();
                hoursChart = null;
            }

            if (typeof leavesChart !== 'undefined' && leavesChart instanceof Chart) {
                leavesChart.destroy();
            }

            if (workdaysChart instanceof Chart) {
                workdaysChart.destroy();
                workdaysChart = null;
            }
        }

        function renderPagination(total, page, limit) {
            const totalPages = Math.ceil(total / limit);
            const container = document.getElementById("taskPagination");

            container.querySelector(".first").onclick = () => { currentPage = 1; loadTasks(currentTab, currentPage, limit, getFilterValues()); };
            container.querySelector(".prev").onclick = () => { if (currentPage > 1) { currentPage--; loadTasks(currentTab, currentPage, limit, getFilterValues()); } };
            container.querySelector(".next").onclick = () => { if (currentPage < totalPages) { currentPage++; loadTasks(currentTab, currentPage, limit, getFilterValues()); } };
            container.querySelector(".last").onclick = () => { currentPage = totalPages; loadTasks(currentTab, currentPage, limit, getFilterValues()); };

            const pagesContainer = container.querySelector(".pages");
            pagesContainer.innerHTML = ""; // clear old

            const windowSize = 2;
            let start = Math.max(1, page - windowSize);
            let end = Math.min(totalPages, page + windowSize);

            if (start > 1) {
                addPageButton(pagesContainer, 1, page);
                if (start > 2) addEllipsis(pagesContainer);
            }

            for (let i = start; i <= end; i++) {
                addPageButton(pagesContainer, i, page);
            }

            if (end < totalPages) {
                if (end < totalPages - 1) addEllipsis(pagesContainer);
                addPageButton(pagesContainer, totalPages, page);
            }

            function addPageButton(container, i, current) {
                const btn = document.createElement("button");
                btn.textContent = i;
                btn.className = "page-btn";
                if (i === current) btn.classList.add("active");
                btn.onclick = () => { currentPage = i; loadTasks(currentTab, currentPage, limit, getFilterValues()); };
                container.appendChild(btn);
            }

            function addEllipsis(container) {
                const span = document.createElement("span");
                span.textContent = "...";
                container.appendChild(span);
            }
        }
        //     applyFilters();
        // });

    </script>
    <script>
        function showSpinner() {
            document.getElementById('spinner-overlay').style.display = 'block';
        }

        function hideSpinner() {
            document.getElementById('spinner-overlay').style.display = 'none';
        }
    </script>

    <script src="assets/js/profile.js"></script>
    <script>
        // window.onload = function () {
        //     alert('here');
        //     document.querySelectorAll('td.task-date').forEach(cell => {
        //         cell.textContent = formatDate(cell.textContent);
        //     });
        // }

        window.onload = function () {

            document.querySelectorAll('td.task-date').forEach(cell => {
                cell.textContent = formatDate(cell.textContent);
            });

            document.querySelectorAll('td.task-duration').forEach(cell => {
                cell.textContent = formatTime(cell.textContent);
            });

        }

        // Date formatting function
        function formatDate(dateString) {
            if (!dateString) return '';
            const parts = dateString.split('-'); // ["YYYY", "MM", "DD"]
            return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : dateString; // "DD-MM-YYYY"
        }

        // Duration formatting function
        function formatTime(timeString) {
            if (!timeString) return '';
            return timeString.length >= 5 ? timeString.slice(0, 5) : timeString; // Converts '02:30:00' to '02:30'
        }


    </script>
    <!-- <script src="assets/js/add.js"></script> -->
    <script src="assets/js/alert.js"></script>
    <script src="assets/js/approve.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        //     function showCommentPopup(onConfirm, onCancel) {
        //         showAlert('Please enter comment for disapproval:', function () {
        //             const comment = document.getElementById('popup-comment-input').value;
        //             onConfirm(comment);
        //         }, function () {
        //             if (onCancel) onCancel(); // Call onCancel when user clicks Cancel
        //         }, 'Submit', 'Cancel');

        //         document.getElementById('alert-message').innerHTML = `
        //     Please enter comment for disapproval:
        //     <input type="text" id="popup-comment-input" placeholder="Enter comment" style="margin-top:10px;width:90%;" />
        // `;
        //     }


    </script>


    <!-- Multiple Task Script -->
    <script>
        document.getElementById('select-all').addEventListener('change', function () {
            const allCheckboxes = document.querySelectorAll('input.select-task');
            allCheckboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>

    <style>
        /* Disable text selection */
        body {
            user-select: none;
        }
    </style>

    <script>
        // Disable right-click
        // document.addEventListener('contextmenu', function (e) {
        //     e.preventDefault();
        // });

        // Disable specific key combinations
        document.addEventListener('keydown', function (e) {
            // F12
            if (e.key === "F12") {
                e.preventDefault();
            }
            // Ctrl+Shift+I / Ctrl+Shift+J
            if (e.ctrlKey && e.shiftKey && (e.key.toUpperCase() === 'I' || e.key.toUpperCase() === 'J')) {
                e.preventDefault();
            }
            // Ctrl+U
            if (e.ctrlKey && e.key.toUpperCase() === 'U') {
                e.preventDefault();
            }
        });

        // Disable copy
        document.addEventListener('copy', function (e) {
            e.preventDefault();
        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        document.getElementById('download-list-btn').addEventListener('click', async function () {
            try {
                // 1. Fetch all task entries from API with current filters
                const filters = getFilterValues();
                const payload = {
                    taskType: currentTab,
                    allowedEmployees: allowedEmployees,
                    employees: filters.selectedEmployees,
                    departments: filters.selectedDepartments,
                    managers: filters.selectedManagers,
                    status: filters.selectedStatus,
                    timeline: filters.timeline,
                    customFrom: filters.customFrom,
                    customTo: filters.customTo,
                    page: 1,
                    limit: 10000 // High limit to get all filtered tasks
                };

                const response = await fetch('api/filter-tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status !== 'success' || !result.tasks || result.tasks.length === 0) {
                    alert("No task data found!");
                    return;
                }

                const allTasks = result.tasks;

                //  2. Define headers (adjust according to backend field names)
                const headers = [
                    "Date", "Emp Id", "Email", "Name", "Working/Leave", "Project / Client", "Nature of Task",
                    "Task Brief", "Task Description", "Duration", "Status", "Comments"
                ];
                const data = [headers];

                //  3. Convert API data to Excel rows
                allTasks.forEach(task => {
                    const rowData = [
                        formatDate(task.date) || '',
                        task.emp_id || '',
                        task.email || '',
                        task.name || '',
                        task.worktype || '',
                        task.client_name || '-',
                        task.cat_name || '',
                        task.brief || '-',
                        task.description || '-',
                        formatTime(task.duration) || '',
                        task.status != null
                            ? task.status == 0
                                ? 'Pending'
                                : task.status == 1
                                    ? 'Approved'
                                    : 'Not Approved'
                            : '-',
                        task.comment || ''
                    ];
                    data.push(rowData);
                });

                //  4. Create worksheet
                const worksheet = XLSX.utils.aoa_to_sheet(data);

                // Auto column widths
                worksheet['!cols'] = headers.map((header, i) => ({
                    wch: Math.max(header.length + 2, ...data.map(r => (r[i] ? r[i].toString().length : 0))) + 2
                }));

                //  5. Create and download workbook
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, "Tasks");
                XLSX.writeFile(workbook, `task-list-${new Date().toISOString().slice(0, 10)}.xlsx`);
            } catch (error) {
                console.error("Error generating Excel:", error);
                alert("Failed to fetch or export task data.");
            }
        });
    </script>



</body>

</html>