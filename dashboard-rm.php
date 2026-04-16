<?php
header("X-Robots-Tag: noindex, nofollow", true);

// require_once 'config/session_init.php';
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Task.php';
// require_once 'classes/TaskCategory.php';

requireRole(['rm', 'admin']);

$emp_id = $_SESSION['emp_id'];

$empObj = new Employee($conn);
$taskObj = new Task($conn);
// $taskCategoryObj = new TaskCategory($conn);

$employee = $empObj->getEmployeeById($emp_id);
$teamEmployees = $empObj->getEmployeesByRMId($emp_id);
$teamEmployeeIds = array_column($teamEmployees, 'emp_id');
// print_r($teamEmployeeIds);die;
// $tasks = $taskObj->getEmployeeTasks($emp_id);
// $taskCategories = $taskCategoryObj->getCategoriesByDept($employee['dept_id']);
// print_r($taskCategories);die;

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
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
    include_once 'includes/sidebar-rm.php';
    ?>
    <main>
        <div class="top-row">
            <!-- <h3 class="">Home</h3> -->
            <div class="left">
                <!-- Filter Boxes -->
                <!-- <div class="filters"> -->
                <div class="filter-date-selector">
                    <label for="main-date-filter" class="date-label">Select Date:</label>
                    <div class="date-nav-wrapper">
                        <button type="button" id="prev-date-btn" class="date-nav-btn"><i
                                class="fa-solid fa-chevron-left"></i></button>
                        <input type="date" id="main-date-filter" class="main-date-filter"
                            value="<?php echo date('Y-m-d'); ?>">
                        <button type="button" id="next-date-btn" class="date-nav-btn"><i
                                class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="filter-multiselect">
                    <div class="select-box">Employee Name <i class="fa-solid fa-caret-down"></i></div>
                    <div class="options">
                        <label><input type="text" class="employee-filter-input" name="emp-name"
                                placeholder="Search employee..."></label>
                        <label><input type="checkbox" class="employee-filter select-all" value=""> Select All</label>

                        <?php foreach ($teamEmployees as $teamEmployee) { ?>
                            <label class="employee-option">
                                <input type="checkbox" class="filter-option employee-filter"
                                    value="<?= $teamEmployee['emp_id'] ?>">
                                <?= $teamEmployee['name'] ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="filter-multiselect">
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

                <!-- </div> -->
            </div>
            <div class="right">
                <button class="action-btn" id="bulk-approve-btn">Approve All</button>
                <button class="action-btn" id="clear-filter-btn">Clear Filters</button>
            </div>

        </div>
        <div class="active-filters" id="active-filters"></div>
        <div class="content-area">
            <div class="tasklist grid-item">
                <div class="task-table-wrapper">
                    <table id="tasktable">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all"></th>
                                <th id="main-table-date-header">Employee Summary</th>
                                <th style="text-align: right; padding-right: 20px;">Group Actions</th>
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
        <button class="floating-toggle-btn" id="toggle-graphs-btn" title="Toggle Graphs"><i class="fa-solid fa-chart-column"></i></button>
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


    <!-- Task Description Modal -->
    <div id="desc-modal" style="display: none;">
        <div id="desc-modal-content">
            <div id="desc-modal-header">
                <h4 style="font-weight: 500">Task Details</h4>
                <span id="desc-modal-close">&times;</span>
            </div>
            <hr>
            <div id="desc-modal-body">
                <div class="modal-top-row">
                    <div class="modal-detail-item">
                        <label>Task Nature:</label>
                        <div id="modal-task-nature" class="detail-value"></div>
                    </div>
                    <div class="modal-detail-item">
                        <label>Task Brief:</label>
                        <div id="modal-task-brief" class="detail-value"></div>
                    </div>
                </div>
                <div class="modal-detail-item">
                    <label>Description:</label>
                    <div id="modal-task-desc" class="detail-value"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Show modal with full details
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.desc-icon-btn');
            if (btn) {
                const nature = btn.dataset.nature || '-';
                const brief = btn.dataset.brief || '-';
                const desc = btn.dataset.desc || 'No description available.';

                document.getElementById('modal-task-nature').textContent = nature;
                document.getElementById('modal-task-brief').textContent = brief;
                document.getElementById('modal-task-desc').textContent = desc;

                document.getElementById('desc-modal').style.display = 'flex';
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
                toast.style.backgroundColor = '#5eda7b';
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

    <script src="assets/js/filters.js"></script>

    <script>
        const loggedUserId = <?= $emp_id ?>;
        const allowedEmployees = <?= json_encode(value: $deptEmployeeIds ?? $teamEmployeeIds ?? []) ?>;
        console.log(allowedEmployees);

    </script>
    <script>

        let currentPage = 1;
        let limit = 100; // default rows per page

        document.addEventListener('DOMContentLoaded', function () {
            const filterInputs = document.querySelectorAll('.filter-option, .main-date-filter, .employee-filter, .status-filter');
            const taskTableContainer = document.querySelector('.task-table-wrapper');

            filterInputs.forEach(input => {
                input.addEventListener('change', applyFilters);
            });

            // Date Navigation Buttons
            const dateInput = document.getElementById('main-date-filter');
            const prevBtn = document.getElementById('prev-date-btn');
            const nextBtn = document.getElementById('next-date-btn');

            if (prevBtn && nextBtn && dateInput) {
                prevBtn.addEventListener('click', () => {
                    adjustDate(-1);
                });
                nextBtn.addEventListener('click', () => {
                    adjustDate(1);
                });
            }

            function adjustDate(days) {
                const currentDate = new Date(dateInput.value);
                if (isNaN(currentDate.getTime())) return;

                currentDate.setDate(currentDate.getDate() + days);

                const year = currentDate.getFullYear();
                const month = String(currentDate.getMonth() + 1).padStart(2, '0');
                const day = String(currentDate.getDate()).padStart(2, '0');

                dateInput.value = `${year}-${month}-${day}`;
                applyFilters();
            }

            // Exposing applyFilters() to window scope to call globally
            window.applyFilters = applyFilters;

            function applyFilters(silent = false) {
                renderFilterTags();
                // Get selected employee names
                const selectedEmployees = Array.from(document.querySelectorAll('.filter-option.employee-filter:checked')).map(el => el.value);
                // Get selected departments
                const selectedManagers = [loggedUserId];
                const selectedDepartments = Array.from(document.querySelectorAll('.department-filter:checked')).map(el => el.value);
                // Get selected Approval Status
                const selectedStatus = Array.from(document.querySelectorAll('.status-filter:checked')).map(el => el.value);
                // Timeline Logic
                const selectedDate = document.getElementById('main-date-filter').value;

                if (!silent) showSpinner();
                fetch('api/filter-tasks', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        loggedUserId: loggedUserId,
                        employees: selectedEmployees,
                        allowedEmployees: allowedEmployees,
                        departments: selectedDepartments,
                        managers: selectedManagers,
                        status: selectedStatus,
                        timeline: 'custom',
                        customFrom: selectedDate,
                        customTo: selectedDate,
                        page: currentPage,
                        limit: limit
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        updateTaskTable(data.tasks);
                        renderPagination(data.total, data.page, data.limit);
                        if (!silent) {
                            taskTableContainer.scrollTo({ top: 0 });
                        }
                        renderFilterTags();

                        let employeesToChart = [];
                        if (selectedEmployees.length > 0) {
                            employeesToChart = selectedEmployees;
                        } else {
                            employeesToChart = [loggedUserId, ...allowedEmployees];
                        }

                        if (employeesToChart.length > 0) {
                            const contentArea = document.querySelector('.content-area');
                            if (!contentArea.classList.contains('graphs-hidden')) {
                                // Ensure wrapper and individual graphs are visible
                                const graphsWrapper = document.querySelector('.graphs-wrapper');
                                if (graphsWrapper) graphsWrapper.style.display = '';
                                document.querySelectorAll('.graph').forEach(graph => graph.style.display = '');
                                loadGraphs(employeesToChart);
                            }
                        } else {
                            hideGraphs();
                        }

                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => hideSpinner());
            }

            function updateTaskTable(tasks) {
                const tableBody = document.querySelector('#tasktablebody');

                if (!tasks || tasks.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding: 20px;">No tasks found</td></tr>';
                    return;
                }

                // Update Main Date Header
                const firstTask = tasks[0];
                const formattedDate = formatDate(firstTask.date);
                document.getElementById('main-table-date-header').innerHTML = `Employee Summary for <strong>${formattedDate}</strong>`;

                // Group by EmpID (Date grouping is now in the header)
                const employeeGroups = {};
                tasks.forEach(task => {
                    if (!employeeGroups[task.emp_id]) {
                        employeeGroups[task.emp_id] = {
                            emp_id: task.emp_id,
                            name: task.name,
                            date: task.date,
                            tasks: [],
                            totalDuration: 0
                        };
                    }
                    employeeGroups[task.emp_id].tasks.push(task);

                    const parts = task.duration.split(':');
                    const minutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);
                    employeeGroups[task.emp_id].totalDuration += minutes;
                });

                let html = '';
                Object.values(employeeGroups).forEach(group => {
                    const groupKey = `${group.date}_${group.emp_id}`;
                    const hours = Math.floor(group.totalDuration / 60);
                    const mins = group.totalDuration % 60;
                    const totalStr = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')} hrs`;

                    html += `<tr class="employee-box-row">
                            <td colspan="3">
                                <div class="employee-task-box" data-group-key="${groupKey}">
                                    <div class="box-header">
                                        <div class="header-left">
                                            <input type="checkbox" class="group-checkbox" data-group="${groupKey}">
                                            <span class="header-info"><strong>Empid :</strong> ${group.emp_id}</span>
                                            <span class="header-info"><strong>Name :</strong> ${group.name}</span>
                                        </div>
                                        <div class="header-right">
                                            <button class="group-approve-btn-styled group-approve-btn" data-group="${groupKey}">Approve</button>
                                            <button class="group-disapprove-btn-styled group-disapprove-btn" data-group="${groupKey}">Disapprove</button>
                                        </div>
                                    </div>
                                    
                                    <div class="box-labels">
                                        <span class="label-item bullet-col"></span>
                                        <span class="label-item worktype-col">Worktype</span>
                                        <span class="label-item duration-col">Duration</span>
                                        <span class="label-item project-col">project/client</span>
                                        <span class="label-item nature-col">task-nature</span>
                                        <span class="label-item brief-col">task-brief</span>
                                        <span class="label-item desc-icon-col">desc</span>
                                        <span class="label-item status-col">status</span>
                                        <span class="label-item comment-col">comments</span>
                                        <span class="label-item actions-col">actions</span>
                                    </div>

                                    <div class="box-tasks-list">`;

                    group.tasks.forEach(task => {
                        const statusText = task.status == '0' ? 'Pending' : task.status == 1 ? 'Approved' : 'Unapproved';
                        const statusClass = task.status == '0' ? 'pending' : task.status == 1 ? 'approved' : 'unapproved';

                        html += `
                                <div class="task-bullet-row" data-task-id="${task.task_id}" data-group-key="${groupKey}">
                                    <span class="bullet">-</span>
                                    <span class="val worktype-col">${task.worktype}</span>
                                    <span class="val duration-col">${formatTime(task.duration)}Hrs</span>
                                    <span class="val project-col" title="${task.client_name || '-'}">${task.client_name || '-'}</span>
                                    <span class="val nature-col" title="${task.cat_name}">${task.cat_name}</span>
                                    <span class="val brief-col" title="${task.brief || '-'}">${task.brief || '-'}</span>
                                    <span class="desc-icon-col">
                                        <button class="desc-icon-btn" title="View Details" 
                                            data-nature="${(task.cat_name || '-').replace(/"/g, '&quot;')}"
                                            data-brief="${(task.brief || '-').replace(/"/g, '&quot;')}"
                                            data-desc="${(task.description || 'No Description').replace(/"/g, '&quot;')}">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </span>
                                    <span class="val status-col ${statusClass}">${statusText}</span>
                                    <span class="val comment-col">
                                        <input type="text" name="comment" value="${task.comment ?? ''}" placeholder="Add Comment" readonly class="inline-comment">
                                    </span>
                                    <span class="val actions-col">
                                        <select name="status-dropdown" class="status-dropdown-plain">
                                            <option value="0" ${task.status == 0 ? 'selected' : ''} disabled hidden>Approve/Disapprove(button)</option>
                                            <option value="1" ${task.status == 1 ? 'selected' : ''}>Approve</option>
                                            <option value="-1" ${task.status == -1 ? 'selected' : ''}>Disapprove</option>
                                        </select>
                                    </span>
                                </div>`;
                    });

                    html += `   </div>
                                    <div class="box-footer">
                                        <strong>Total Duration :</strong> ${totalStr}
                                    </div>
                                </div>
                            </td>
                        </tr>`;
                });

                tableBody.innerHTML = html;
            }

            // Updated Event Listeners (Checkboxes now only toggle at card/global level)
            document.querySelector('#tasktablebody').addEventListener('change', function (e) {
                if (e.target.classList.contains('group-checkbox')) {
                    // State is maintained for global "Approve All" logic
                }
            });

            document.querySelector('#tasktablebody').addEventListener('click', function (e) {
                // Description popup
                if (e.target.classList.contains('desc-icon-btn')) {
                    const desc = e.target.dataset.desc || 'No description available.';
                    document.getElementById('desc-modal-body').textContent = desc;
                    document.getElementById('desc-modal').style.display = 'flex';
                    return;
                }

                if (e.target.classList.contains('group-approve-btn')) {
                    const groupKey = e.target.dataset.group;
                    const updates = [];
                    document.querySelectorAll(`.task-bullet-row[data-group-key="${groupKey}"]`).forEach(row => {
                        updates.push({
                            task_id: row.dataset.taskId,
                            status: 1,
                            comment: row.querySelector('input[name="comment"]').value.trim()
                        });
                    });
                    if (updates.length > 0) bulkUpdateGroup(updates);
                } else if (e.target.classList.contains('group-disapprove-btn')) {
                    const groupKey = e.target.dataset.group;
                    showCommentPopup((comment) => {
                        const updates = [];
                        document.querySelectorAll(`.task-bullet-row[data-group-key="${groupKey}"]`).forEach(row => {
                            updates.push({
                                task_id: row.dataset.taskId,
                                status: -1,
                                comment: comment
                            });
                        });
                        if (updates.length > 0) bulkUpdateGroup(updates);
                        hideAlert();
                    }, null);
                }
            });

            function bulkUpdateGroup(updates) {
                showSpinner();
                fetch('api/bulk-approve', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tasks: updates })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Group tasks updated successfully!');
                            applyFilters(true);
                        } else {
                            showToast('Error updating group tasks.');
                        }
                    })
                    .catch(error => console.error('Error:', error))
                    .finally(() => hideSpinner());
            }


            function renderPagination(total, page, limit) {
                const totalPages = Math.ceil(total / limit);
                const container = document.getElementById("taskPagination");

                container.querySelector(".first").onclick = () => { currentPage = 1; applyFilters(); };
                container.querySelector(".prev").onclick = () => { if (currentPage > 1) { currentPage--; applyFilters(); } };
                container.querySelector(".next").onclick = () => { if (currentPage < totalPages) { currentPage++; applyFilters(); } };
                container.querySelector(".last").onclick = () => { currentPage = totalPages; applyFilters(); };

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
                    if (i === current) btn.classList.add("active"); // highlight instead of disable
                    btn.onclick = () => { currentPage = i; applyFilters(); };
                    container.appendChild(btn);
                }

                function addEllipsis(container) {
                    const span = document.createElement("span");
                    span.textContent = "...";
                    container.appendChild(span);
                }
            }


            applyFilters();

            // Rows per page
            const rowsSelect = document.getElementById('rows-per-page-select');
            if (rowsSelect) {
                rowsSelect.value = String(limit);
                rowsSelect.addEventListener('change', function () {
                    limit = parseInt(this.value);
                    currentPage = 1;
                    applyFilters();
                });
            }
        });

        // ---- Render Active Filter Tags ----
        function renderFilterTags() {
            const container = document.getElementById('active-filters');
            if (!container) return;
            container.innerHTML = '';

            const tagDefs = [
                { selector: '.employee-filter:checked:not(.select-all)', category: 'Employee', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.status-filter:checked:not(.select-all)', category: 'Status', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.department-filter:checked:not(.select-all)', category: 'Dept', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.rm-filter:checked:not(.select-all)', category: 'Manager', labelFn: el => el.closest('label').textContent.trim() },
            ];

            tagDefs.forEach(({ selector, category, labelFn }) => {
                document.querySelectorAll(selector).forEach(el => {
                    const tag = buildTag(category, labelFn(el), () => {
                        el.checked = false;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    container.appendChild(tag);
                });
            });

            // Main date selector tag
            const dateInput = document.getElementById('main-date-filter');
            if (dateInput && dateInput.value) {
                const tag = buildTag('Date', formatDate(dateInput.value), () => {
                    // Reset to today on click? Or just leave it? 
                    // Usually "clear" means setting to default. Let's set to today.
                    const today = new Date().toISOString().split('T')[0];
                    dateInput.value = today;
                    dateInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
                container.appendChild(tag);
            }
        }

        function buildTag(category, label, onRemove) {
            const tag = document.createElement('span');
            tag.className = 'filter-tag';
            tag.innerHTML = `<span><strong>${category}:</strong> ${label.replace(/^\s+/, '')}</span>`;
            const btn = document.createElement('span');
            btn.className = 'tag-remove';
            btn.textContent = '×';
            btn.title = 'Remove filter';
            btn.addEventListener('click', onRemove);
            tag.appendChild(btn);
            return tag;
        }

        // Integrate with clear button
        document.getElementById('clear-filter-btn')?.addEventListener('click', function () {
            document.querySelectorAll('.filter-option:checked, .employee-filter:checked, .status-filter:checked, .department-filter:checked, .rm-filter:checked').forEach(el => {
                el.checked = false;
            });
            const mainDateFilter = document.getElementById('main-date-filter');
            if (mainDateFilter) {
                const today = new Date().toISOString().split('T')[0];
                mainDateFilter.value = today;
            }
            currentPage = 1;
            applyFilters();
        });

        function hideGraphs() {
            document.querySelectorAll('.graph').forEach(graph => graph.style.display = 'none');

            if (typeof hoursChart !== 'undefined' && hoursChart) {
                hoursChart.destroy();
                hoursChart = null;
            }
            if (typeof leavesChart !== 'undefined' && leavesChart) {
                leavesChart.destroy();
                leavesChart = null;
            }
            if (typeof workdaysChart !== 'undefined' && workdaysChart) {
                workdaysChart.destroy();
                workdaysChart = null;
            }
        }

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
            const parts = dateString.split('-'); // ["YYYY", "MM", "DD"]
            return `${parts[2]}-${parts[1]}-${parts[0]}`; // "DD-MM-YYYY"
        }

        // Duration formatting function
        function formatTime(timeString) {
            return timeString.slice(0, 5); // Converts '02:30:00' to '02:30'
        }


    </script>
    <!-- <script src="assets/js/add.js"></script> -->
    <script src="assets/js/alert.js"></script>
    <script src="assets/js/approve.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        document.getElementById('select-all').addEventListener('change', function () {
            const allCheckboxes = document.querySelectorAll('input.select-task, .group-checkbox');
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
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

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

        // Disable copy (except from input fields and textareas)
        document.addEventListener('copy', function (e) {
            const target = e.target;
            // Allow copy from input, textarea, and contenteditable elements
            if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
                return; // Allow the copy
            }
            e.preventDefault();
        });
    </script>




</body>

</html>