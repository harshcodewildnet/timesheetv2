<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Client.php';

// requireRole(['rm', 'admin']);

$emp_id = $_SESSION['emp_id'];

$empObj = new Employee($conn);
$clientObj = new Client($conn);

// $taskObj = new Task($conn);
// $taskCategoryObj = new TaskCategory($conn);

$employee = $empObj->getEmployeeById($emp_id);
$deptEmployees = $empObj->getEmployeesByDeptId($employee['dept_id']);
$deptEmployeeIds = array_column($deptEmployees, 'emp_id');
$teamEmployees = array_values(array_filter($deptEmployees, fn($emp) => $emp['role'] !== 'hod'));
$allClients = $clientObj->getActiveClients();

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
    <title>Home</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <link rel="stylesheet" href="assets/css/team.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .card {
            position: relative;
        }

        .card span {
            position: absolute;
            border: 1px solid;
            border-radius: 50%;
            padding: 6px;
            line-height: 1rem;
            color: #353535;
            top: 10%;
            right: 10%;
            cursor: pointer;
        }

        .card span:hover {
            color: #fff;
            background-color: #353535;
        }

        #modal-client {
            /* opacity: 1;
            pointer-events: all; */
        }

        #modal-client .modal {
            max-width: 40vw;
            min-height: auto;
        }

        #modal-client .row {
            /* background-color: blue; */
        }

        .row.select-row {
            /* flex-direction: column; */
            display: block;
        }

        #modal-client .row .col {
            display: flex;
            flex: 1;
            /* background-color: gray; */
        }

        #modal-client .row h4 span {
            font-weight: normal;
            margin-left: 10px;
        }

        .client-badges {
            height: 100%;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .client-badge {
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff3cd;
            color: #614901ff;
            padding: 4px 10px;
            border-radius: 20px;
            margin: 5px 5px 0 0;
            font-size: 12px;
            cursor: default;
        }

        .client-badge button.btn-remove {
            margin-left: 8px;
            color: #856404;
            cursor: pointer;
            font-size: 1.2rem;
            /* font-weight: bold; */
            /* line-height: 1.4rem; */
            background: none;
            border: none;
        }
    </style>
</head>

<body>
    <!-- Header Dblist-->
    <header>
        <a href="dashboard">
            <div class="logo">
                <img src="assets/images/wnet-image.png" alt="WildNet logo">
            </div>
        </a>
    </header>
    <?php
    include_once "includes/sidebar-" . $employee['role'] . ".php";
    ?>
    <main>
        <div class="top-row">
            <div class="left">
                <h3>My Team</h3>
            </div>
        </div>
        <div class="content-area">
            <div class="team-container">
                <?php
                if ($teamEmployees) {
                    foreach ($teamEmployees as $teamEmployee) {
                        ?>
                        <!-- <a href="timesheet?emp_id=<?= $teamEmployee['emp_id'] ?>"> -->
                        <div class="card">
                            <div class="left"><img src="<?= $teamEmployee['profile_photo'] ?>"
                                    onerror="this.onerror=null; this.src='assets/images/profile-picture.png';"
                                    alt="Profile Photo"></div>
                            <div class="right">
                                <h5 class="head1">Emp Id : <?= $teamEmployee['emp_id'] ?></h5>
                                <h4 class="head2"><?= $teamEmployee['name'] ?></h4>
                                <h5 class="head3"><?= $teamEmployee['dept_name'] ?></h5>
                                <h5 class="head4"><?= $teamEmployee['email'] ?></h5>
                                <span class="open-modal-btn" data-id="<?= $teamEmployee['emp_id'] ?>"><i
                                        class="fa-solid fa-pencil"></i></span>
                            </div>
                        </div>
                        <!-- </a> -->

                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </main>


    <!-- Executive Modal for Add/View/Edit -->
    <div class="modal-dialog" id="modal-client">
        <div class="modal">
            <div class="modal-header">
                <h4 class="modal-title">Add <?= getClientLabel($employee['dept_id'] ?? null) ?>s</h4>
                <button class="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <hr>
            <div class="modal-body">
                <form class="profile-container">

                    <div class="row">
                        <div class="col">
                            <h4>Emp Id : <span id="emp-id"></span></h4>
                        </div>
                        <div class="col">
                            <h4>Emp Name : <span id="emp-name"></span></h4>
                        </div>
                    </div>
                    <div class="row select-row">
                        <div class="form-group">
                            <label for="client-select">Select
                                <?= getClientLabel($employee['dept_id'] ?? null) ?>s</label>
                            <select name="client-select"
                                class="form-control client-select client-select-executive editable">
                                <option value="">Select <?= getClientLabel($employee['dept_id'] ?? null) ?>(s)</option>
                                <?php
                                // Example: populate from clients table
                                if (!empty($allClients)) {
                                    foreach ($allClients as $client) {
                                        echo '<option value="' . $client['client_id'] . '">' . $client['client_name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>

                        <!-- </div>
                    <div class="row"> -->
                        <div class="form-group">
                            <div class="client-badges client-badges-executive"></div>
                            <!-- hidden input to store multiple selected clients -->
                            <input type="hidden" name="clients" class="clients-hidden clients-hidden-executive">
                        </div>
                    </div>
                    <div class="row bottom-row">
                        <button type="submit" class="save-profile-btn" id="save-executive-btn">Save</button>
                        <button type="button" class="cancel-btn" id="cancel-executive-btn">Cancel</button>
                    </div>
                    <!-- </form> -->
                </form>
            </div>
        </div>
    </div>
    <!-- <footer></footer> -->
    <?php
    // Edit Profile Photo Modal 
    include_once 'photo-modal.php';
    ?>

    <!-- Message Toast -->
    <div id="toast">Saved Successfully!</div>

    <!-- Loading Spinner -->
    <div id="spinner-overlay">
        <div class="spinner"></div>
    </div>

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



    <script>
        // modal popup open buttons logic
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".open-modal-btn").forEach(btn => {
                btn.addEventListener("click", (e) => {
                    // console.log('event called');
                    e.preventDefault();
                    e.stopPropagation();
                    // const modalType = btn.getAttribute("data-modal");
                    // const mode = btn.getAttribute("data-mode") || "add";
                    const id = btn.getAttribute("data-id") || null;
                    openModal(null, null, id);
                });
            });
        });


        // open modal popups
        function openModal(modalType = null, mode = null, id = null) {
            // const modalId = 'modal-client';
            const modal = document.getElementById('modal-client');
            const form = modal.querySelector("form.profile-container");
            const empId = modal.querySelector("span#emp-id");
            const empName = modal.querySelector("span#emp-name");
            const submitBtn = modal.querySelector(".save-profile-btn");
            // const inputs = modal.querySelectorAll("input, select, textarea");
            // const passwordFields = modal.querySelectorAll('.password-field');
            // Reset form
            form.reset();
            // Clear errors
            form.querySelectorAll("small.error").forEach(err => {
                err.textContent = "";
                err.style.display = "none";   // or visibility = "hidden"
            });


            // form.dataset.mode = mode;
            // form.dataset.modalType = modalType;
            if (id) form.dataset.id = id; else delete form.dataset.id;
            const bodyData = `action=get&id=${id}`;

            fetch('api/employee', {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: bodyData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        const emp = data.emp;
                        empId.textContent = id;
                        empName.textContent = emp.name;
                        updateDynamicLabels(form, emp.dept_id);
                        setupClientBadgeHandler(modal, data);

                    } else {
                        alert(data.message || "Data not found");
                    }
                })
                .catch(err => {
                    console.error("Fetch error:", err);
                    alert("Error loading data");
                });



            // setupClientBadgeHandler(modal);

            // Show modal
            modal.classList.add("show");
        }

        document.querySelectorAll(".cancel-btn").forEach(cancelBtn => {
            cancelBtn.addEventListener("click", () => {
                const modal = cancelBtn.closest(".modal-dialog");
                modal.classList.remove("show");
            });
        });

        // Close modal logic
        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.modal-dialog').classList.remove('show');
            });
        });
    </script>
    <script>

        function setupClientBadgeHandler(modal, data = {}) {
            console.log('setupClientBadgeHandler run');

            const select = modal.querySelector(`.client-select`);
            const badgesContainer = modal.querySelector('.client-badges');
            const hiddenInput = modal.querySelector('.clients-hidden');

            if (!select || !badgesContainer || !hiddenInput) return;
            // console.log('here');

            // const mode = modal.querySelector('form.profile-container')?.dataset.mode || 'add';
            // const isViewOnly = mode === 'view';

            // Clear old badges & hidden input
            badgesContainer.innerHTML = '';
            hiddenInput.value = '';

            // Track selected client IDs
            let selectedValues = new Set();

            // Pre-fill if editing (from backend data)
            if (data.assignedClients && Array.isArray(data.assignedClients)) {
                data.assignedClients.forEach(client => {
                    selectedValues.add(client.client_id);
                });
            }
            // else if (hiddenInput.value) {
            //     // fallback if value is already present (e.g. modal reopened)
            //     hiddenInput.value.split(',').forEach(v => selectedValues.add(v));
            // }

            // Function to refresh badges UI
            function renderBadges() {
                // console.log('renderBadges run');

                badgesContainer.innerHTML = '';

                selectedValues.forEach(value => {
                    const option = select.querySelector(`option[value="${value}"]`);
                    const label = option ? option.textContent : value;

                    const badge = document.createElement('span');
                    badge.className = 'client-badge';
                    badge.textContent = label;

                    // if (!isViewOnly) {
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn-remove';
                    removeBtn.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
                    // removeBtn.style.fontSize = '0.7rem';
                    removeBtn.addEventListener('click', () => {
                        selectedValues.delete(value);
                        updateHiddenInput();
                        renderBadges();
                    });

                    badge.appendChild(removeBtn);
                    // }

                    badgesContainer.appendChild(badge);
                });
            }

            // Update hidden input value
            function updateHiddenInput() {
                console.log('updateHiddenInput run');
                hiddenInput.value = Array.from(selectedValues).join(',');
            }


            // if (!isViewOnly) { // Add/Edit Mode
            // Handle select change (adding new clients)
            select.addEventListener('change', () => {
                const value = select.value;
                if (value && !selectedValues.has(value)) {
                    selectedValues.add(value);
                    updateHiddenInput();
                    renderBadges();
                }
                select.value = ''; // reset dropdown
            });
            // } else { // View Mode
            //     select.disabled = true;
            // }

            // Initialize hidden input & badges
            updateHiddenInput();
            renderBadges();
        }

        function updateDynamicLabels(form, deptId) {
            if (!form) return;
            const isProject = (parseInt(deptId) === 5);
            const targetLabel = isProject ? 'Project' : 'Client';
            const sourceLabel = isProject ? 'Client' : 'Project';

            // Update labels like "Select Clients" -> "Select Projects"
            form.querySelectorAll('label').forEach(label => {
                if (label.textContent.includes(sourceLabel)) {
                    label.textContent = label.textContent.replace(new RegExp(sourceLabel, 'g'), targetLabel);
                }
            });

            // Update select options (placeholders)
            form.querySelectorAll('select[name="client-select"]').forEach(select => {
                if (select.options.length > 0 && select.options[0].value === "") {
                    if (select.options[0].textContent.includes(sourceLabel)) {
                        select.options[0].textContent = select.options[0].textContent.replace(new RegExp(sourceLabel, 'g'), targetLabel);
                    }
                }
            });

            // Update modal title if needed
            const modalTitle = form.closest('.modal-dialog')?.querySelector('.modal-title');
            if (modalTitle && modalTitle.textContent.includes(sourceLabel)) {
                modalTitle.textContent = modalTitle.textContent.replace(new RegExp(sourceLabel, 'g'), targetLabel);
            }
        }
    </script>

    <?php
    if (!empty($_SESSION['message'])) {
        echo "
                <script>
                    showToast('" . $_SESSION['message'] . "', " . !$_SESSION['success'] . ");
                </script>  
            ";
        // echo $_SESSION['client_data'];
        unset($_SESSION['message']);
        unset($_SESSION['success']);
    }
    ?>



    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.querySelector(".profile-container");
            const saveBtn = document.getElementById("save-executive-btn");

            saveBtn.addEventListener("click", async (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                const id = form.dataset.id || '';

                if (id) formData.append('id', id);
                formData.append('action', 'update-employee-clients');
                console.log(id);

                try {
                    const response = await fetch("api/employee", {
                        method: "POST",
                        body: formData
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || "Unexpected server error");
                    }

                    // SUCCESS
                    if (data.status === "success") {
                        // showToast(data.message);  // if you already use your custom toast
                        // OPTIONAL: close modal or reload page
                        location.reload();
                    }
                    // VALIDATION ERROR
                    else if (data.status === "error") {
                        const errorEl = form.querySelector("small.error");
                        errorEl.textContent = data.message;
                        errorEl.style.display = "block";
                    }

                } catch (err) {
                    console.error("Fetch error:", err);
                    alert("Something went wrong. Please try again.");
                }
            });
        });

    </script>

    <script src="assets/js/profile.js"></script>


</body>

</html>