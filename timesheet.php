<?php
header("X-Robots-Tag: noindex, nofollow", true);

// require_once 'config/session_init.php';
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Client.php';
require_once 'classes/Task.php';
require_once 'classes/TaskCategory.php';

requireRole(['rm', 'admin']);

$emp_id = $_SESSION['emp_id'];

$empObj = new Employee($conn);
$taskObj = new Task($conn);
$clientObj = new Client($conn);

// $taskCategoryObj = new TaskCategory($conn);
$employee = $empObj->getEmployeeById($emp_id);
$tasks = $taskObj->getEmployeeTasks($emp_id);
$taskCategories = $taskObj->getCategoriesByDept($employee['dept_id'], $employee['subdept_id']);
// $taskCategories = $taskObj->getCategoriesByDept($employee['dept_id']);
// $taskCategories = $taskCategoryObj->getCategoriesByDept($employee['dept_id']);
$taskCategoryIds = array_column($taskCategories, 'cat_id');
// print_r($taskCategories);die;
$briefsByCategory = $taskObj->getTaskBriefsByCategories($taskCategoryIds);
$employeeClients = $clientObj->getActiveClients();

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
    <title>My Timesheet</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <!-- <link rel="stylesheet" href="assets/css/team.css"> -->
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

        #alert-message {
            display: flex;
            flex-direction: column;
            font-weight: 500;
        }

        #alert-message .form-group {
            margin-bottom: 10px;
        }

        #alert-message .form-group label {
            margin-bottom: 4px;
            margin-right: 4px;
            font-weight: 500;
        }

        #alert-message .form-group select {
            padding: 8px 12px;
        }

        #popup-budget-input {
            padding: 8px 12px;
        }
    </style>
</head>

<body>
    <!-- Header Dblist-->
    <!-- Header removed -->
    <?php
    include_once "includes/sidebar-" . $employee['role'] . ".php";
    ?>
    <main>
        <div class="top-row">
            <!-- <h3 class="">Home</h3> -->
            <div class="left">
                <!-- Filter Boxes -->
                <!-- <div class="filters"> -->
                <div class="filter-multiselect">
                    <div class="select-box">Timeline<i class="fa-solid fa-caret-down"></i></div>
                    <div class="options">
                        <!-- <label><input type="checkbox" class="select-all timeline-filter" value="">Select All</label> -->
                        <label><input type="checkbox" class="filter-option timeline-filter" value="today"> Today</label>
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
                <button class="add-btn action-btn" id="open-modal-btn"><i class="fa-solid fa-plus-circle"></i>Add
                    New</button>
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
                                <th>Date</th>
                                <th>Working/Leave</th>
                                <th>Duration</th>
                                <th><?= getClientLabel($employee['dept_id'] ?? null) ?></th>
                                <th>Nature of Task</th>
                                <th>Task Brief</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tasktablebody">
                            <?php
                            if (!empty($tasks)) {
                                foreach ($tasks as $task) {
                                    ?>
                                    <tr>
                                        <td class="task-date"><?= $task['date'] ?></td>
                                        <td><?= $task['worktype'] ?></td>
                                        <td><?= substr($task['duration'], 0, 5) ?></td>
                                        <td><?= $task['client_name'] ?? '-' ?></td>
                                        <td><?= $task['cat_name'] ?></td>
                                        <td><?= $task['brief'] ?></td>
                                        <td>-</td>
                                        <td><?= ($task['status'] == 1) ? 'Approved' : 'Not Approved' ?></td>
                                        <td>-</td>
                                        <td><i class="fa-solid fa-pencil"></i><i class="fa-regular fa-trash-can"></i></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
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
            <div id="alert-message">Alert Message</div>
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
    include_once 'task-modal.php';
    // Edit Profile Photo Modal 
    include_once 'photo-modal.php';
    ?>


    <!-- Show Toast Script -->
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
    <script>
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
    </script>

    <script src="assets/js/filters.js"></script>
    <!-- Graphs Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="assets/js/graph.js"></script>

    <script>
        const loggedUserId = <?= $emp_id ?>;
    </script>


    <script>

        let currentPage = 1;
        let limit = 100; // default rows per page

        document.addEventListener('DOMContentLoaded', function () {
            const filterInputs = document.querySelectorAll('.filter-option, .timeline-filter, .status-filter, .custom-from-date, .custom-to-date');
            const taskTableContainer = document.querySelector('.task-table-wrapper');

            filterInputs.forEach(input => {
                input.addEventListener('change', applyFilters);
            });

            // Exposing applyFilters() to window scope to call globally
            window.applyFilters = applyFilters;

            function applyFilters() {
                renderFilterTags();
                // // Get selected employee names
                // const selectedEmployees = Array.from(document.querySelectorAll('.employee-filter:checked')).map(el => el.value);
                // // Get selected departments
                // const selectedDepartments = Array.from(document.querySelectorAll('.department-filter:checked')).map(el => el.value);

                const selectedEmployees = [loggedUserId];
                const selectedDepartments = [];
                // Get selected Approval Status
                const selectedStatus = Array.from(document.querySelectorAll('.status-filter:checked')).map(el => el.value);

                // Timeline Logic
                const customCheckbox = document.querySelector('.custom-range-checkbox');
                let timeline = '';
                let customFrom = '';
                let customTo = '';

                if (customCheckbox.checked) {
                    customFrom = document.querySelector('.custom-from-date').value;
                    customTo = document.querySelector('.custom-to-date').value;
                    timeline = 'custom'; // Mark it as custom in the request
                } else {
                    const selectedTimelines = Array.from(document.querySelectorAll('.timeline-filter:checked')).map(el => el.value);
                    if (selectedTimelines.length > 0) {
                        timeline = selectedTimelines[0]; // Only one allowed at a time
                    }
                }
                // console.log({ timeline, customFrom, customTo });
                fetch('api/filter-tasks', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        employees: selectedEmployees,
                        departments: selectedDepartments,
                        status: selectedStatus,
                        timeline: timeline,
                        customFrom: customFrom,
                        customTo: customTo,
                        page: currentPage,
                        limit: limit
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        updateTaskTable(data.tasks);
                        renderPagination(data.total, data.page, data.limit);
                        taskTableContainer.scrollTo({ top: 0 });
                        renderFilterTags();
                        const contentArea = document.querySelector('.content-area');
                        if (!contentArea.classList.contains('graphs-hidden')) {
                            loadGraphs([loggedUserId]);
                        }


                    })
                    .catch(error => console.error('Error:', error));
            }

            function updateTaskTable(tasks) {
                const tableBody = document.querySelector('#tasktablebody');
                tableBody.innerHTML = '';
                if (!tasks.length == 0) {
                    tasks.forEach(task => {
                        const row = document.createElement('tr');
                        row.dataset.taskId = task.task_id;

                        row.innerHTML = `
                            <td>${formatDate(task.date)}</td>
                            <td>${task.worktype}</td>
                            <td>${formatTime(task.duration)}</td>
                            <td>${task.client_name || '-'}</td>
                            <td>${task.cat_name}</td>
                            <td>${task.brief || '-'}</td>
                            <td class="description-cell" data-full="${task.description || '-'}">
                                ${task.description?.substring(0, 50) || ''}${task.description?.length > 50 ? '...' : ''}
                            </td>
                            <td>${task.status == 0 ? 'Pending' : task.status == 1 ? 'Approved' : 'Not Approved'}</td>
                            <td>${task.comment || ''}</td>
                            <td class="actions">
                                ${task.status !== 1 ? `<i class="fa-solid fa-pencil edit-icon" data-id="${task.task_id}"></i>` : '-'}
                                ${task.status === 0 ? `<i class="fa-regular fa-trash-can delete-icon" data-id="${task.task_id}"></i>` : ''}
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = `
                        <tr style="background-color: #e0f5ff;">
                            <td colspan="8" style="text-align: center;">
                                <h3>No Tasks</h3>
                            </td>
                        </tr>
                    `;
                }
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
                { selector: '.timeline-filter:checked:not(.custom-range-checkbox)', category: 'Timeline', labelFn: el => el.closest('label').textContent.trim() },
                { selector: '.status-filter:checked:not(.select-all)', category: 'Status', labelFn: el => el.closest('label').textContent.trim() },
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

            // Custom date range tag
            const customCb = document.querySelector('.custom-range-checkbox');
            if (customCb && customCb.checked) {
                const from = document.querySelector('.custom-from-date')?.value;
                const to = document.querySelector('.custom-to-date')?.value;
                if (from || to) {
                    const label = `${from || '?'} → ${to || '?'}`;
                    const tag = buildTag('Date Range', label, () => {
                        customCb.checked = false;
                        customCb.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    container.appendChild(tag);
                }
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
            document.querySelectorAll('.filter-option:checked, .timeline-filter:checked, .status-filter:checked').forEach(el => {
                el.checked = false;
            });
            const fromDate = document.querySelector('.custom-from-date');
            const toDate = document.querySelector('.custom-to-date');
            if (fromDate) fromDate.value = '';
            if (toDate) toDate.value = '';
            document.querySelector('.custom-date-range') && (document.querySelector('.custom-date-range').style.display = 'none');
            currentPage = 1;
            applyFilters();
        });

    </script>

    <script src="assets/js/profile.js"></script>
    <script>
        window.onload = function () {
            document.querySelectorAll('.task-date').forEach(cell => {
                cell.textContent = formatDate(cell.textContent);
            });
        }

        // Date formatting function
        function formatDate(dateString) {
            const parts = dateString.split('-'); // ["YYYY", "MM", "DD"]
            return `${parts[2]}-${parts[1]}-${parts[0]}`; // "DD-MM-YYYY"
        }



    </script>
    <script>
        const taskCategories = <?php echo json_encode($taskCategories) ?>;
        const briefsByCategory = <?php echo json_encode($briefsByCategory); ?>;
        const employeeClients = <?php echo json_encode($employeeClients); ?>;
        const deptId = <?= intval($employee['dept_id'] ?? 0) ?>;
    </script>

    <script>
        function showSpinner() {
            document.getElementById('spinner-overlay').style.display = 'block';
        }

        function hideSpinner() {
            document.getElementById('spinner-overlay').style.display = 'none';
        }
    </script>
    <script src="assets/js/add.js"></script>
    <script src="assets/js/alert.js"></script>
    <script src="assets/js/main.js"></script>
    <?php
    if (!empty($_SESSION['message'])) {
        echo "
                <script>
                    showToast('" . $_SESSION['message'] . "', " . !$_SESSION['success'] . ");
                </script>  
            ";
        unset($_SESSION['message']);
        unset($_SESSION['success']);
    }
    ?>


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
    <script>
        function showBudgetPrompt() {
            // Step 1: Ask Yes/No
            showAlert("Is there any budget change?", function () {
                // Yes → show budget input
                showBudgetInput();
            }, function () {
                // No → just close
                hideAlert();
            }, "Yes", "No");
            // Add <h4> heading in Step 1
            const messageEl = document.getElementById("alert-message");
            if (messageEl) {
                messageEl.insertAdjacentHTML('afterbegin', '<h4 id="budget-heading" style="margin-bottom:10px;"><i class="fa-solid fa-circle-exclamation"></i>Budget Update</h4>');
            }
        }

        function showBudgetInput(initialValue = '') {

            // Remove the heading if present
            const headingEl = document.getElementById("budget-heading");
            if (headingEl) headingEl.remove();

            showAlert("Enter new budget:", function () {
                const budget = document.getElementById("popup-budget-input").value.trim();
                const errorEl = document.getElementById("popup-budget-error");
                const clientSelect = document.getElementById("client-select");

                // get client values safely
                const clientId = clientSelect ? clientSelect.value.trim() : '';
                const clientName = clientSelect && clientSelect.selectedIndex > 0
                    ? clientSelect.options[clientSelect.selectedIndex].text.trim()
                    : '';

                // reset previous error
                errorEl.textContent = "";

                // client must be selected
                if (!clientId || clientId == "0") {
                    errorEl.textContent = "Please select a client before proceeding.";
                    return;
                }

                if (budget === '') {
                    errorEl.textContent = "Budget cannot be empty.";
                    return;
                } else if (isNaN(budget) || Number(budget) <= 0) {
                    errorEl.textContent = "Please enter a valid positive number.";
                    return;
                }
                errorEl.textContent = "";

                // Step 3: Confirmation popup
                showAlert(
                    `Are you sure you want to set budget to ${budget} for ${clientName}?`,
                    function () {
                        console.log("Budget confirmed:", budget, clientId, clientName);
                        sendBudgetChangeMail(budget, loggedUserId, clientId, clientName);
                        hideAlert();
                    },
                    function () {
                        // Cancel → go back to input again
                        showBudgetInput(budget);
                    },
                    "Confirm",
                    "Cancel"
                );
            }, function () {
                // Cancel → go back to Yes/No prompt
                showBudgetPrompt();
            }, "Submit", "Cancel");


            // Replace message content with input field
            document.getElementById("alert-message").innerHTML = `
                    <div class="form-group">
                        <label for="client-select">Select Client</label>
                        <select name="emp-client" id="client-select">
                            ${getEmployeeClientsOptions()}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>New Budget</label>
                        <input type="number" id="popup-budget-input" 
                            placeholder="Enter budget" 
                            value="${initialValue.replace(/"/g, '&quot;')}">
                    </div>
                    <p id="popup-budget-error" style="color:red;font-size:13px;margin-top:5px;"></p>
                `;
        }

        function sendBudgetChangeMail(budget, loggedUserId, clientId, clientName) {

            showSpinner();
            fetch('api/send-budget-mail', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ budget, loggedUserId, clientId, clientName })
            })
                .then(res => res.text())
                .then(text => {

                    try {
                        const data = JSON.parse(text);
                        // console.log(data);
                        if (data.success) {
                            console.log("Budget change email sent successfully.");
                            showAlert("Budget updated and notification sent successfully.", hideAlert, null, "OK");
                        } else {
                            console.error("Error sending email:", data.message);
                            showAlert("Budget updated but failed to send notification.", hideAlert, null, "OK");
                        }
                    } catch {
                        console.error('Non-JSON response:', text);
                    }


                    // if (data.success) {
                    //     console.log("Budget change email sent successfully.");
                    //     showAlert("Budget updated and notification sent successfully.", hideAlert, null, "OK");
                    // } else {
                    //     console.error("Error sending email:", data.message);
                    //     showAlert("Budget updated but failed to send notification.", hideAlert, null, "OK");
                    // }
                })
                .catch(err => {
                    console.error("Fetch error:", err);
                    showAlert("Error while sending email notification.", hideAlert, null, "OK");
                }).finally(() => {
                    hideSpinner();
                });
        }


        // Trigger on login
        // document.addEventListener("DOMContentLoaded", () => {
        //     // Only for exe/rm dashboards
        //     // const role = document.body.getAttribute("data-role"); // example: set from PHP
        //     // if (role === "exe" || role === "rm") {
        //     showBudgetPrompt();
        //     // }
        // });
        // showBudgetPrompt();

        ////////////////

        document.addEventListener("DOMContentLoaded", () => {
            // Example: you can also fetch this from backend-rendered data
            const userId = loggedUserId;
            const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
            const key = `budgetPromptShown_${userId}_${today}`;
            console.log('Inside event listener block', employeeClients);

            // Check if prompt already shown today
            if (!localStorage.getItem(key) && (employeeClients.length > 0)) {
                showBudgetPrompt();
                localStorage.setItem(key, "true");
            }
            // showBudgetPrompt();
        });


        /////////////
    </script>

</body>

</html>
</body>

</html>