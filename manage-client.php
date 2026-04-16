<?php
header("X-Robots-Tag: noindex, nofollow", true);

require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
// require_once 'classes/Department.php';
require_once 'classes/Client.php';

requireRole(['admin', 'hod']);

$emp_id = $_SESSION['emp_id'];

$empObj = new Employee($conn);
$employee = $empObj->getEmployeeById($emp_id);
$clientObj = new Client($conn);
$allClients = $clientObj->getAllClients();

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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">

</head>

<body>
    <!-- Header Dblist-->
    <header>
        <a href="dashboard-<?= $_SESSION['emp_role'] ?>">
            <div class="logo">
                <img src="assets/images/wnet-image.png" alt="WildNet logo">
            </div>
        </a>
        <div class="search-area">
        </div>
        <div class="actions">
            <!-- <label id="profile-label">
                    <i class="fa-solid fa-user"></i>
                    <button id="profile-toggle">
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                    <div id="profile-dropdown" class="profile-dropdown">
                        <a href="manage-profile" class="dropdown-item">My Profile</a>
                        <hr>
                        <a href="manage-profile" class="dropdown-item">Reset Password</a>
                    </div>
                </label>
                <a href="#" id="logout-link">
                    <i class="fa-solid fa-power-off"></i>
                    <span>Logout</span>
                </a> -->
        </div>
        </div>
    </header>
    <?php
    include_once "includes/sidebar-" . $employee['role'] . ".php";
    ?>
    <main>
        <div class="top-row">
            <div class="left">
                <h3>Our Clients / Projects</h3>
            </div>
        </div>
        <div class="content-area">
            <div class="tab-container tasklist">
                <div class="tab-content">
                    <!-- Client Panel -->
                    <div class="tab-panel active" id="tab-client" role="tabpanel">
                        <div class="top-row">
                            <div class="search-bar">
                                <input type="text" class="search-input" placeholder="Search in Clients / Projects">
                                <!-- <button class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button> -->
                            </div>
                            <button class="add-btn open-modal-btn" id="add-client-btn" data-modal="client"
                                data-mode="add"><i class="fa-solid fa-plus-circle"></i>Add
                                New</button>
                        </div>
                        <div class="panel-table-container">
                            <table id="panel-table-client" class="panel-table">
                                <thead>
                                    <tr class="table-head">
                                        <!-- <th><input type="checkbox" id="select-all"></th> -->
                                        <th>S.No.</th>
                                        <th>Client / Project Id</th>
                                        <th>Client / Project Name</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tablebody-client">
                                    <?php
                                    if (!empty($allClients)) {
                                        $i = 1;
                                        foreach ($allClients as $client) {
                                            ?>
                                            <tr data-index="<?= $i ?>">
                                                <td><?= $i . "." ?></td>
                                                <!-- <td><input type="checkbox" id="<?= $client['client_id'] ?>"></td> -->
                                                <td><?= $client['client_id'] ?></td>
                                                <td><?= $client['client_name'] ?></td>
                                                <td><?= $client['email'] ?></td>
                                                <td class="actions">
                                                    <i class="fa-solid fa-eye open-modal-btn" data-modal="client"
                                                        data-mode="view" data-id="<?= $client['client_id'] ?>"></i>
                                                    <i class="fa-solid fa-pencil open-modal-btn" data-modal="client"
                                                        data-mode="edit" data-id="<?= $client['client_id'] ?>"></i>
                                                    <!-- <i class="fa-solid fa-trash-can delete-btn" data-mode="delete"
                                                        data-entity="client" data-id="<?= $client['client_id'] ?>"></i> -->
                                                    <label class="switch">
                                                        <input type="checkbox" class="toggle-status"
                                                            data-id="<?= $client['client_id'] ?>" data-entity="client"
                                                            <?= $client['is_active'] ? 'checked' : '' ?>>
                                                        <span class="slider round"></span>
                                                    </label>
                                                </td>
                                            </tr>
                                            <?php
                                            $i++;
                                        }
                                        echo '<tr class="no-entries-row" style="background-color: #e0f5ff;display:none;">
                                                <td colspan="7" style="text-align: center;">
                                                    <h3>No Entries</h3>
                                                </td>
                                            </tr>';
                                    } else {
                                        ?>
                                        <tr style="background-color: #e0f5ff;">
                                            <td colspan="7" style="text-align: center;">
                                                <h3>No Entries</h3>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination" id="clientPagination">
                            <div class="rows-per-page"></div>
                            <div class="pagination-controls">
                                <button class="first">First</button>
                                <button class="prev">Prev</button>
                                <span class="pages"><button class="page-btn active">1</button></span>
                                <button class="next">Next</button>
                                <button class="last">Last</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
            <div class="alert-header">
                <h4>Alert</h4>
            </div>
            <p id="alert-message">Alert Message</p>
            <div class="alert-actions">
                <button id="alert-confirm-btn">Yes</button>
                <button id="alert-cancel-btn">No</button>
            </div>
        </div>
    </div>

    <!-- Client Modal for Add/View/Edit -->
    <div class="modal-dialog" id="modal-client">
        <div class="modal" style="max-width: 50vw;">
            <div class="modal-header">
                <h4 class="modal-title">Client / Project</h4>
                <button class="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <hr>
            <div class="modal-body">
                <form class="profile-container">
                    <!-- <form action=""> -->
                    <!-- <div class="row action-row">
                        <h4></h4>
                        <button class="edit-profile-btn"></button>
                        <i class="edit-profile-btn fa-regular fa-pen-to-square" id="edit-client-btn"></i>
                    </div> -->
                    <div class="row">
                        <div class="form-group">
                            <!-- <input type="text" name="role" value="client" hidden> -->
                            <label for="client-id">Client / Project Id<sup>*</sup></label>
                            <input type="text" id="client-id" class="editable" name="client_id" value=""
                                placeholder="Enter Client Id" required disabled>
                            <small class="error" style="color:red;display:none;">Please Enter a valid Id (Combination of
                                Alphabets & Numbers)</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="client-name">Client / Project Name<sup>*</sup></label>
                            <input type="text" id="client-name" class="editable" name="client_name" value=""
                                placeholder="Enter Client Name" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid name (only
                                alphabets allowed)</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="client-email">Contact Email</label>
                            <input type="email" id="client-email" class="editable" name="email" value=""
                                placeholder="Enter Contact Email (Optional)">
                            <small class="error" style="color:red;display:none;">Please Enter a valid email</small>
                        </div>
                    </div>
                    <!-- <div class="row">
                        <div class="form-group">
                            <label for="client-no">Contact No.</label>
                            <input type="text" id="client-no" class="editable" name="contact" value=""
                                placeholder="Enter Contact No.">
                            <small class="error" style="color:red;display:none;">Please Enter a valid Contact No.</small>
                        </div>
                    </div> -->
                    <div class="row bottom-row">
                        <button type="submit" class="save-profile-btn" id="save-client-btn">Save</button>
                        <button type="button" class="cancel-btn" id="cancel-client-btn">Cancel</button>
                    </div>
                    <!-- </form> -->
                </form>
            </div>
        </div>
    </div>

    <?php
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


    <!-- <script src="assets/js/graph.js"></script> -->

    <!-- <script src="assets/js/filters.js"></script> -->

    <script src="assets/js/profile.js"></script>

    <script>
        function paginateTable(tableBodyId, paginationId, rowsPerPage = 10) {
            const tableBody = document.getElementById(tableBodyId);
            const pagination = document.getElementById(paginationId);
            const rows = Array.from(tableBody.querySelectorAll('tr:not(.no-entries-row)'));
            let currentPage = 1;
            const totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));

            function renderPageButtons() {
                const pagesContainer = pagination.querySelector('.pages');
                pagesContainer.innerHTML = '';
                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
                    btn.textContent = i;
                    btn.addEventListener('click', () => {
                        currentPage = i;
                        renderPage(currentPage);
                    });
                    pagesContainer.appendChild(btn);
                }
            }

            function renderPage(page) {
                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;
                rows.forEach((row, i) => {
                    row.style.display = (i >= start && i < end) ? '' : 'none';
                });
                renderPageButtons();
            }

            function attachEvents() {
                pagination.querySelector('.first').onclick = () => {
                    currentPage = 1;
                    renderPage(currentPage);
                };
                pagination.querySelector('.prev').onclick = () => {
                    if (currentPage > 1) currentPage--;
                    renderPage(currentPage);
                };
                pagination.querySelector('.next').onclick = () => {
                    if (currentPage < totalPages) currentPage++;
                    renderPage(currentPage);
                };
                pagination.querySelector('.last').onclick = () => {
                    currentPage = totalPages;
                    renderPage(currentPage);
                };
            }

            renderPage(currentPage);
            attachEvents();
        }

        document.addEventListener('DOMContentLoaded', () => {
            paginateTable('tablebody-client', 'clientPagination', 20);
        });

    </script>

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

    <script>
        function showSpinner() {
            document.getElementById('spinner-overlay').style.display = 'block';
        }

        function hideSpinner() {
            document.getElementById('spinner-overlay').style.display = 'none';
        }

    </script>
    <!-- <script src="assets/js/add.js"></script> -->
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/alert.js"></script>
    <!-- <script src="assets/js/approve.js"></script> -->
    <script src="assets/js/main.js"></script>
    <script>
        function showCommentPopup(onConfirm, onCancel) {
            showAlert('Please enter comment for disapproval:', function () {
                const comment = document.getElementById('popup-comment-input').value;
                onConfirm(comment);
            }, function () {
                if (onCancel) onCancel(); // Call onCancel when user clicks Cancel
            }, 'Submit', 'Cancel');

            document.getElementById('alert-message').innerHTML = `
        Please enter comment for disapproval:
        <input type="text" id="popup-comment-input" placeholder="Enter comment" style="margin-top:10px;width:90%;" />
    `;
        }


    </script>


    <!-- Multiple Task Script -->
    <!-- <script>
        document.getElementById('select-all').addEventListener('change', function () {
            const allCheckboxes = document.querySelectorAll('input.select-task');
            allCheckboxes.forEach(cb => cb.checked = this.checked);
        });
    </script> -->

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
        /* body {
            user-select: none;
        } */
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



</body>

</html>