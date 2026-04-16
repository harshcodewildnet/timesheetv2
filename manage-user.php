<?php
header("X-Robots-Tag: noindex, nofollow", true);


// require_once 'config/session_init.php';
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Department.php';
require_once 'classes/SubDepartment.php';
require_once 'classes/Client.php';

requireRole(['admin', 'hod']);

$emp_id = $_SESSION['emp_id'];

$empObj = new Employee($conn);
$deptObj = new Department($conn);
$subDeptObj = new SubDepartment($conn);
$clientObj = new Client($conn);
$departments = $deptObj->getDepartments();
$subDepartments = $subDeptObj->getAllSubDepartments();
$employee = $empObj->getEmployeeById($emp_id);
$employees = $empObj->getAllEmployees();
$executives = array_values(array_filter($employees, fn($emp) => $emp['role'] === 'executive'));
$repManagers = array_values(array_filter($employees, fn($emp) => ($emp['role'] === 'rm')));
$deptHeads = array_values(array_filter($employees, fn($emp) => $emp['role'] === 'hod'));
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
    <meta name="robots" content="noindex, nofollow">
    <title>Home</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* .form-group span.client-badge {
            display: inline-block;
            min-width: 100px;
            height: 30px;
            background-color: #fff5cb;
            margin: 10px 6px;
            border-radius: 25px;
            text-align: center;
            font-size: .8rem;
            line-height: 30px;
        } */
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
        <a href="dashboard-<?= $_SESSION['emp_role'] ?>">
            <div class="logo">
                <img src="assets/images/wnet-image.png" alt="WildNet logo">
            </div>
        </a>
        <div class="search-area">

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
        <div class="content-area">
            <div class="tab-container tasklist">
                <div class="tab-links" role="tablist">
                    <?php
                    if ($_SESSION['emp_role'] === 'admin') {
                        ?>
                        <button class="tab-link active" role="tab" aria-selected="true"
                            data-tab="tab-dept">Department</button>
                        <button class="tab-link" role="tab" aria-selected="false" data-tab="tab-subdept">Sub-Dept.</button>
                        <button class="tab-link" role="tab" aria-selected="false" data-tab="tab-hod">HOD</button>
                        <?php
                    }
                    ?>
                    <button class="tab-link" role="tab" aria-selected="false" data-tab="tab-rm">RM</button>
                    <button class="tab-link" role="tab" aria-selected="false" data-tab="tab-exe">Executive</button>
                </div>
                <div class="tab-content">

                    <?php
                    if ($_SESSION['emp_role'] === 'admin') {
                        ?>

                        <!-- Departments Panel -->
                        <div class="tab-panel active" id="tab-dept" role="tabpanel">
                            <div class="top-row">
                                <div></div>

                                <button class="add-btn open-modal-btn" id="add-dept-btn" data-modal="department"
                                    data-mode="add"><i class="fa-solid fa-plus-circle"></i>Add
                                    New</button>
                            </div>
                            <div class="panel-table-container">
                                <table id="panel-table-dept" class="panel-table">
                                    <thead>
                                        <tr class="table-head">
                                            <!-- <th><input type="checkbox" id="select-all"></th> -->
                                            <th>S.No.</th>
                                            <!-- <th>Dept Id</th> -->
                                            <th>Dept Name</th>
                                            <!-- <th>Dept Head(Emp. Id)</th> -->
                                            <th>No. of Employees</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablebody-department">
                                        <?php
                                        if (!empty($departments)) {
                                            $i = 1;
                                            foreach ($departments as $department) {
                                                ?>
                                                <tr data-index="<?= $i ?>">
                                                    <!-- <td><input type="checkbox" id="<?= $department['dept_id'] ?>"></td> -->
                                                    <td><?= $i ?>.</td>
                                                    <!-- <td><?= $department['dept_id'] ?></td> -->
                                                    <td><?= $department['dept_name'] ?></td>
                                                    </td>
                                                    <td><?= $department['emp_count'] ?></td>
                                                    <td class="actions">
                                                        <i class="fa-solid fa-eye open-modal-btn" data-modal="department"
                                                            data-mode="view" data-id="<?= $department['dept_id'] ?>"></i>
                                                        <i class="fa-solid fa-pencil open-modal-btn" data-modal="department"
                                                            data-mode="edit" data-id="<?= $department['dept_id'] ?>"></i>
                                                        <!-- <i class="fa-solid fa-trash-can delete-btn delete-btn-dep"
                                                        data-mode="delete" data-entity="department"
                                                        data-id="<?= $department['dept_id'] ?>"></i> -->
                                                    </td>

                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- <div class="pagination" id="deptPagination">
                            <button class="first">First</button>
                            <button class="prev">Prev</button>
                            <button class="pages">1</button>
                            <button class="next">Next</button>
                            <button class="last">Last</button>
                        </div> -->
                        </div>
                        <!-- Sub-Departments Panel -->
                        <div class="tab-panel" id="tab-subdept" role="tabpanel">
                            <div class="top-row">
                                <div></div>
                                <button class="add-btn open-modal-btn" id="add-subdept-btn" data-modal="sub-department"
                                    data-mode="add"><i class="fa-solid fa-plus-circle"></i>Add
                                    New</button>
                            </div>
                            <div class="panel-table-container">
                                <table id="panel-table-subdept" class="panel-table">
                                    <thead>
                                        <tr class="table-head">
                                            <!-- <th><input type="checkbox" id="select-all"></th> -->
                                            <th>S.No.</th>
                                            <!-- <th>Sub-Dept Id</th> -->
                                            <th>Sub-Dept</th>
                                            <th>Department</th>
                                            <!-- <th>Dept Head(Emp. Id)</th> -->
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablebody-sub-department">
                                        <?php
                                        if (!empty($subDepartments)) {
                                            $i = 1;
                                            foreach ($subDepartments as $subDepartment) {
                                                ?>
                                                <tr data-index="<?= $i ?>">
                                                    <td><?= $i ?>.</td>
                                                    <!-- <td><?= $subDepartment['subdept_id'] ?></td> -->
                                                    <td><?= $subDepartment['subdept_name'] ?></td>
                                                    <td><?= $subDepartment['dept_name'] ?></td>
                                                    <td class="actions">
                                                        <i class="fa-solid fa-eye open-modal-btn" data-modal="sub-department"
                                                            data-mode="view" data-id="<?= $subDepartment['subdept_id'] ?>"></i>
                                                        <i class="fa-solid fa-pencil open-modal-btn" data-modal="sub-department"
                                                            data-mode="edit" data-id="<?= $subDepartment['subdept_id'] ?>"></i>
                                                        <!-- <i class="fa-solid fa-trash-can delete-btn delete-btn-dep"
                                                        data-modal="sub-department" data-mode="delete"
                                                        data-entity="sub-department"
                                                        data-id="<?= $subDepartment['subdept_id'] ?>"></i> -->
                                                    </td>
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                        } else {
                                            ?>
                                            <tr style="background-color: #e0f5ff;">
                                                <td colspan="5" style="text-align: center;">
                                                    <h3>No Entries</h3>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- <div class="pagination" id="subdeptPagination">
                            <button class="first">First</button>
                            <button class="prev">Prev</button>
                            <button class="pages">1</button>
                            <button class="next">Next</button>
                            <button class="last">Last</button>
                        </div> -->
                        </div>
                        <!-- HOD Panel -->
                        <div class="tab-panel" id="tab-hod" role="tabpanel">
                            <div class="top-row">
                                <div class="search-bar">
                                    <input type="text" class="search-input" placeholder="Search in dept heads">
                                    <!-- <button class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button> -->
                                </div>
                                <button class="add-btn open-modal-btn" id="add-hod-btn" data-modal="hod" data-mode="add"><i
                                        class="fa-solid fa-plus-circle"></i>Add
                                    New</button>
                            </div>
                            <div class="panel-table-container">
                                <table id="panel-table-hod" class="panel-table">
                                    <thead>
                                        <tr class="table-head">
                                            <!-- <th><input type="checkbox" id="select-all"></th> -->
                                            <th>S.No.</th>
                                            <th>Emp Id</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Dept</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablebody-hod">
                                        <?php
                                        if (!empty($deptHeads)) {
                                            $i = 1;
                                            foreach ($deptHeads as $deptHead) {
                                                ?>
                                                <tr data-index="<?= $i ?>">
                                                    <td><?= $i ?>.</td>
                                                    <!-- <td><input type="checkbox" id="<?= $deptHead['emp_id'] ?>"></td> -->
                                                    <td><?= $deptHead['emp_id'] ?></td>
                                                    <td><?= $deptHead['name'] ?></td>
                                                    <td><?= $deptHead['email'] ?></td>
                                                    <td><?= $deptHead['dept_name'] ?></td>
                                                    <td class="actions">
                                                        <i class="fa-solid fa-eye open-modal-btn" data-modal="hod" data-mode="view"
                                                            data-id="<?= $deptHead['emp_id'] ?>"></i>
                                                        <i class="fa-solid fa-pencil open-modal-btn" data-modal="hod"
                                                            data-mode="edit" data-id="<?= $deptHead['emp_id'] ?>"></i>
                                                        <!-- <i class="fa-solid fa-trash-can delete-btn delete-btn-emp" data-modal="hod"
                                                        data-mode="delete" data-entity="employee"
                                                        data-id="<?= $deptHead['emp_id'] ?>"></i> -->
                                                        <label class="switch">
                                                            <input type="checkbox" class="toggle-status"
                                                                data-id="<?= $deptHead['emp_id'] ?>" data-entity="employee"
                                                                <?= $deptHead['status'] ? 'checked' : '' ?>>
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </td>
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                            echo '<tr class="no-entries-row" style="background-color: #e0f5ff;display:none;">
                                                <td colspan="6" style="text-align: center;">
                                                    <h3>No Entries</h3>
                                                </td>
                                            </tr>';
                                        } else {
                                            ?>
                                            <tr style="background-color: #e0f5ff;">
                                                <td colspan="6" style="text-align: center;">
                                                    <h3>No Entries</h3>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination" id="hodPagination">
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

                        <?php
                    }

                    ?>
                    <!-- RM Panel -->
                    <div class="tab-panel" id="tab-rm" role="tabpanel">
                        <div class="top-row">
                            <div class="search-bar">
                                <input type="text" class="search-input" placeholder="Search in managers">
                                <!-- <button class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button> -->
                            </div>
                            <?php if ($_SESSION['emp_role'] === 'admin') {
                                ?>

                                <button class="add-btn open-modal-btn" id="add-rm-btn" data-modal="rm" data-mode="add"><i
                                        class="fa-solid fa-plus-circle"></i>Add
                                    New</button>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="panel-table-container">
                            <table id="panel-table-rm" class="panel-table">
                                <thead>
                                    <tr class="table-head">
                                        <!-- <th><input type="checkbox" id="select-all"></th> -->
                                        <th>S.No.</th>
                                        <th>Emp Id</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Dept</th>
                                        <th>R.M (Emp Id)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tablebody-rm">
                                    <?php
                                    if (!empty($repManagers)) {
                                        $i = 1;
                                        foreach ($repManagers as $repManager) {
                                            ?>
                                            <tr data-index="<?= $i ?>">
                                                <td><?= $i ?>.</td>
                                                <!-- <td><input type="checkbox" id="<?= $employee['emp_id'] ?>"></td> -->
                                                <td><?= $repManager['emp_id'] ?></td>
                                                <td><?= $repManager['name'] ?></td>
                                                <td><?= $repManager['email'] ?></td>
                                                <td><?= $repManager['dept_name'] ?></td>
                                                <td><?= $repManager['rm_name'] . " (" . $repManager['rm_id'] . ")" ?></td>
                                                <td class="actions">
                                                    <i class="fa-solid fa-eye open-modal-btn" data-modal="rm" data-mode="view"
                                                        data-id="<?= $repManager['emp_id'] ?>"></i>
                                                    <i class="fa-solid fa-pencil open-modal-btn" data-modal="rm"
                                                        data-mode="edit" data-id="<?= $repManager['emp_id'] ?>"></i>
                                                    <!-- <i class="fa-solid fa-trash-can delete-btn delete-btn-emp"
                                                        data-mode="delete" data-entity="employee"
                                                        data-id="<?= $repManager['emp_id'] ?>"></i> -->
                                                    <label class="switch">
                                                        <input type="checkbox" class="toggle-status"
                                                            data-id="<?= $repManager['emp_id'] ?>" data-entity="employee"
                                                            <?= $repManager['status'] ? 'checked' : '' ?>>
                                                        <span class="slider round"></span>
                                                    </label>
                                                </td>
                                            </tr>
                                            <?php
                                            $i++;
                                        }
                                        echo '<tr class="no-entries-row" style="background-color: #e0f5ff;display:none;">
                                                <td colspan="6" style="text-align: center;">
                                                    <h3>No Entries</h3>
                                                </td>
                                            </tr>';
                                    } else {
                                        ?>
                                        <tr style="background-color: #e0f5ff;">
                                            <td colspan="6" style="text-align: center;">
                                                <h3>No Entries</h3>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination" id="rmPagination">
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
                    <!-- Executive Panel -->
                    <div class="tab-panel" id="tab-exe" role="tabpanel">
                        <div class="top-row">
                            <div class="search-bar">
                                <input type="text" class="search-input" placeholder="Search in executives">
                                <!-- <button class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button> -->
                            </div>
                            <?php if ($_SESSION['emp_role'] === 'admin') {
                                ?>

                                <button class="add-btn open-modal-btn" id="add-executive-btn" data-modal="executive"
                                    data-mode="add"><i class="fa-solid fa-plus-circle"></i>Add
                                    New</button>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="panel-table-container">
                            <table id="panel-table-executive" class="panel-table">
                                <thead>
                                    <tr class="table-head">
                                        <!-- <th><input type="checkbox" id="select-all"></th> -->
                                        <th>S.No.</th>
                                        <th>Emp Id</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Dept</th>
                                        <!-- <th>Role</th> -->
                                        <th>R.M (Emp Id)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tablebody-executive">
                                    <?php
                                    if (!empty($executives)) {
                                        $i = 1;
                                        foreach ($executives as $executive) {
                                            ?>
                                            <tr data-index="<?= $i ?>">
                                                <td><?= $i . "." ?></td>
                                                <!-- <td><input type="checkbox" id="<?= $executive['emp_id'] ?>"></td> -->
                                                <td><?= $executive['emp_id'] ?></td>
                                                <td><?= $executive['name'] ?></td>
                                                <td><?= $executive['email'] ?></td>
                                                <td><?= $executive['dept_name'] ?></td>
                                                <!-- <td><?= $executive['role'] ?></td> -->
                                                <td><?= $executive['rm_name'] . " (" . $executive['rm_id'] . ")" ?></td>
                                                <td class="actions">
                                                    <i class="fa-solid fa-eye open-modal-btn" data-modal="executive"
                                                        data-mode="view" data-id="<?= $executive['emp_id'] ?>"></i>
                                                    <i class="fa-solid fa-pencil open-modal-btn" data-modal="executive"
                                                        data-mode="edit" data-id="<?= $executive['emp_id'] ?>"></i>
                                                    <!-- <i class="fa-solid fa-trash-can delete-btn delete-btn-emp"
                                                        data-mode="delete" data-entity="employee"
                                                        data-id="<?= $executive['emp_id'] ?>"></i> -->
                                                    <label class="switch">
                                                        <input type="checkbox" class="toggle-status"
                                                            data-id="<?= $executive['emp_id'] ?>" data-entity="employee"
                                                            <?= $executive['status'] ? 'checked' : '' ?>>
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
                        <div class="pagination" id="executivePagination">
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

    <!-- Department Modal for Add/View/Edit -->
    <div class="modal-dialog" id="modal-department">
        <div class="modal">
            <div class="modal-header">
                <h4 class="modal-title">Department</h4>
                <button class="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <hr>
            <div class="modal-body">
                <form class="profile-container">
                    <div class="row action-row">
                        <h4></h4>
                        <!-- <button class="edit-profile-btn"></button> -->
                        <!-- <i class="edit-profile-btn fa-regular fa-pen-to-square" id="edit-dep-btn"></i> -->
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="dept_name">Department Name<sup>*</sup></label>
                            <input type="text" id="dept_name" class="editable" name="dept_name" required>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                        <!-- <div class="form-group">
                            <label for="dept-head">Select Dept Head</label>
                            <select name="dept-head" id="dept-head" class="dept-head">
                                <option value="">Select Dept Head</option>
                            </select>
                        </div> -->
                    </div>
                    <!-- <div class="row">
                        <div class="form-group">
                            <label for="dept-name">Dept. Email</label>
                            <input type="email" id="dept-email" name="dept-email">
                        </div>
                    </div> -->
                    <div class="row bottom-row">

                        <button type="submit" class="save-profile-btn" id="save-dep-btn">Save</button>
                        <button type="button" class="cancel-btn" id="cancel-dep-btn">Cancel</button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sub-Department Modal for Add/View/Edit -->
    <div class="modal-dialog" id="modal-sub-department">
        <div class="modal">
            <div class="modal-header">
                <h4 class="modal-title">Sub-Department</h4>
                <button class="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <hr>
            <div class="modal-body">
                <form class="profile-container">
                    <div class="row action-row">
                        <h4></h4>
                        <!-- <button class="edit-profile-btn"></button> -->
                        <!-- <i class="edit-profile-btn fa-regular fa-pen-to-square" id="edit-subdept-btn"></i> -->
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="dept">Department<sup>*</sup></label>
                            <select name="dept_id" id="dept-subdept" class="editable" required disabled>
                                <option value="">Select Department</option>
                                <?php
                                if ($departments) {
                                    foreach ($departments as $department) {
                                        ?>
                                        <option value="<?= $department['dept_id'] ?>"><?= $department['dept_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="subdept_name">Sub Department Name<sup>*</sup></label>
                            <input type="text" id="subdept_name" class="editable" name="subdept_name" required>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row bottom-row">
                        <button type="submit" class="save-profile-btn" id="save-subdept-btn">Save</button>
                        <button type="button" class="cancel-btn" id="cancel-subdept-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- HOD Modal for Add/View/Edit -->
    <div class="modal-dialog" id="modal-hod">
        <div class="modal">
            <div class="modal-header">
                <h4 class="modal-title">HOD</h4>
                <button class="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <hr>
            <div class="modal-body">
                <form class="profile-container">
                    <!-- <form action=""> -->
                    <!-- <div class="row action-row">
                        <h4>Emp Id: 1912</h4>
                        <button class="edit-profile-btn"></button>
                        <i class="edit-profile-btn fa-regular fa-pen-to-square" id="edit-hod-btn"></i>
                    </div> -->
                    <div class="row">
                        <div class="form-group">
                            <label for="role-hod">Role<sup>*</sup></label>
                            <select name="role" id="role-hod" class="editable role-select" required>
                                <option value="hod" selected>HOD</option>
                                <option value="rm">RM</option>
                                <option value="executive">Executive</option>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                        <div class="form-group">
                            <label for="emp-id-hod">Emp Id<sup>*</sup></label>
                            <input type="text" id="emp-id-hod" class="" name="emp_id" value=""
                                placeholder="Enter Emp. Id" required disabled>
                            <small class="error" style="color:red;display:none;">Please Enter a valid Id (only
                                numbers allowed)</small>
                        </div>
                        <div class="form-group">
                            <label for="hod-name">Name<sup>*</sup></label>
                            <input type="text" id="hod-name" class="editable" name="name" value=""
                                placeholder="Enter Name" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid name (only
                                alphabets allowed)</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="email">Email<sup>*</sup></label>
                            <input type="email" id="email-hod" class="editable" name="email" value=""
                                placeholder="Enter Email" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid email</small>
                        </div>
                        <div class="form-group">
                            <label for="dept">Department<sup>*</sup></label>
                            <select name="dept_id" id="dept-hod" class="editable" required>
                                <option value="">Select Department</option>
                                <?php
                                if ($departments) {
                                    foreach ($departments as $department) {
                                        ?>
                                        <option value="<?= $department['dept_id'] ?>"><?= $department['dept_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row dynamic-fields" style="display:none;">
                        <div class="form-group">
                            <label for="subdept-hod">Sub Department</label>
                            <select name="subdept_id" id="subdept-hod" class="editable">
                                <option value="">Select Sub Department</option>
                                <!-- Dynamically Populated on Dept. Selection -->
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                        <div class="form-group">
                            <label for="rm-hod">Rep. Manager<sup>*</sup></label>
                            <select name="rm_id" id="rm-hod" class="editable">
                                <option value="">Select Manager</option>
                                <?php
                                if (!empty($repManagers)) {
                                    foreach ($repManagers as $repManager) {
                                        ?>
                                        <option value="<?= $repManager['emp_id'] ?>"><?= $repManager['name'] ?></option>
                                    <?php }
                                }
                                if (!empty($deptHeads)) {
                                    foreach ($deptHeads as $deptHead) {
                                        ?>
                                        <option value="<?= $deptHead['emp_id'] ?>"><?= $deptHead['name'] ?></option>
                                    <?php }
                                }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group password-field">
                            <label for="password">Password<sup>*</sup></label>
                            <!-- <input type="password" id="password-hod" class="editable" name="password" value=""
                                placeholder="Enter Password" required disabled>
                            <small class="error" style="color:red;display:none;">Please Enter a valid password (min
                                length 6 chars)</small> -->
                            <div class="password-wrapper">
                                <input type="password" name="password" class="editable" id="password-hod"
                                    placeholder="Enter Password (min 6 chars)" required>
                                <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                            </div>
                            <small class="error" style="color:red;display:none;">Please Enter a valid password (min
                                length 6 chars)</small>
                        </div>
                        <div class="form-group password-field">
                            <label for="confirm-password-hod">Confirm Password<sup>*</sup></label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm-password-hod" class="editable"
                                    name="confirm-password" value="" placeholder="Re-enter Password" required disabled>
                                <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                            </div>
                            <small class="error" style="color:red;display:none;">Passwords donot match</small>
                        </div>
                    </div>
                    <div class="row bottom-row">
                        <!-- <div class="form-group"> -->
                        <button type="submit" class="save-profile-btn" id="save-hod-btn">Save</button>
                        <button type="button" class="cancel-btn" id="cancel-hod-btn">Cancel</button>
                        <!-- </div> -->
                    </div>
                    <!-- </form> -->
                </form>
            </div>
        </div>
    </div>


    <!-- RM Modal for Add/View/Edit -->
    <div class="modal-dialog" id="modal-rm">
        <div class="modal">
            <div class="modal-header">
                <h4 class="modal-title">RM</h4>
                <button class="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <hr>
            <div class="modal-body">
                <form class="profile-container">
                    <div class="row">
                        <div class="form-group">
                            <label for="role-rm">Role<sup>*</sup></label>
                            <select name="role" id="role-rm" class="editable role-select" required>
                                <option value="hod">HOD</option>
                                <option value="rm" selected>RM</option>
                                <option value="executive">Executive</option>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                        <div class="form-group">
                            <label for="emp-id-rm">Emp Id<sup>*</sup></label>
                            <input type="text" id="emp-id-rm" class="" name="emp_id" value=""
                                placeholder="Enter Emp. Id" required disabled>
                            <small class="error" style="color:red;display:none;">Please Enter a valid Id (only
                                numbers allowed)</small>
                        </div>
                        <div class="form-group">
                            <label for="rm-name">Name<sup>*</sup></label>
                            <input type="text" id="rm-name" class="editable" name="name" value=""
                                placeholder="Enter Name" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid name (only
                                alphabets allowed)</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="email">Email<sup>*</sup></label>
                            <input type="email" id="email-rm" class="editable" name="email" value=""
                                placeholder="Enter Email" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid email</small>
                        </div>
                        <div class="form-group">
                            <label for="dept">Department<sup>*</sup></label>
                            <select name="dept_id" id="dept-rm" class="editable" required>
                                <option value="">Select Department</option>
                                <?php
                                if ($departments) {
                                    foreach ($departments as $department) {
                                        ?>
                                        <option value="<?= $department['dept_id'] ?>"><?= $department['dept_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row dynamic-fields">
                        <div class="form-group">
                            <label for="subdept-rm">Sub Department</label>
                            <select name="subdept_id" id="subdept-rm" class="editable">
                                <option value="">Select Sub Department</option>
                                <!-- Dynamically Populated on Dept. Selection -->
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                        <div class="form-group">
                            <label for="rm">Rep. Manager<sup>*</sup></label>
                            <select name="rm_id" id="rm-rm" class="editable" required>
                                <option value="">Select Manager</option>
                                <?php
                                // skipping static population of rm
                                // if (false) {
                                if (!empty($repManagers)) {
                                    foreach ($repManagers as $repManager) {
                                        ?>
                                        <option value="<?= $repManager['emp_id'] ?>"><?= $repManager['name'] ?></option>
                                    <?php }
                                }
                                if (!empty($deptHeads)) {
                                    foreach ($deptHeads as $deptHead) {
                                        ?>
                                        <option value="<?= $deptHead['emp_id'] ?>"><?= $deptHead['name'] ?></option>
                                    <?php }
                                }
                                // }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="client-select">Select Client / Project(s)</label>
                            <select name="client-select" class="form-control client-select client-select-rm">
                                <option value="">Select Client / Project(s)</option>
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
                        <div class="form-group">
                            <div class="client-badges client-badges-rm"></div>
                            <!-- hidden input to store multiple selected clients -->
                            <input type="hidden" name="clients" class="clients-hidden clients-hidden-rm">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group password-field">
                            <label for="password">Password<sup>*</sup></label>
                            <div class="password-wrapper">
                                <input type="password" name="password" class="editable" id="password-rm"
                                    placeholder="Enter Password" required>
                                <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                            </div>
                            <small class="error" style="color:red;display:none;">Please Enter a valid password (min
                                length 6 chars)</small>
                        </div>
                        <!-- </div>
                    <div class="row"> -->
                        <div class="form-group password-field">
                            <label for="confirm-password-rm">Confirm Password<sup>*</sup></label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm-password-rm" class="editable" name="confirm-password"
                                    value="" placeholder="Re-enter Password" required disabled>
                                <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                            </div>
                            <small class="error" style="color:red;display:none;">Passwords donot match</small>
                        </div>
                        <!-- <div class="form-group"></div> -->
                    </div>
                    <div class="row bottom-row">
                        <!-- <div class="form-group"> -->
                        <button type="submit" class="save-profile-btn" id="save-rm-btn">Save</button>
                        <button type="button" class="cancel-btn" id="cancel-rm-btn">Cancel</button>
                        <!-- </div> -->
                    </div>
                    <!-- </form> -->
                </form>
            </div>
        </div>
    </div>


    <!-- Executive Modal for Add/View/Edit -->
    <div class="modal-dialog" id="modal-executive">
        <div class="modal">
            <div class="modal-header">
                <h4 class="modal-title">Executive</h4>
                <button class="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
            </div>
            <hr>
            <div class="modal-body">
                <form class="profile-container">
                    <!-- <form action=""> -->
                    <!-- <div class="row action-row">
                        <h4></h4>
                        <button class="edit-profile-btn"></button>
                        <i class="edit-profile-btn fa-regular fa-pen-to-square" id="edit-executive-btn"></i>
                    </div> -->
                    <div class="row">
                        <div class="form-group">
                            <label for="role-exe">Role<sup>*</sup></label>
                            <select name="role" id="role-exe" class="editable role-select" required>
                                <option value="hod">HOD</option>
                                <option value="rm">RM</option>
                                <option value="executive" selected>Executive</option>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                        <div class="form-group">
                            <label for="emp-id">Emp Id<sup>*</sup></label>
                            <input type="text" id="emp-id-exe" class="" name="emp_id" value=""
                                placeholder="Enter Emp. Id" required disabled>
                            <small class="error" style="color:red;display:none;">Please Enter a valid Id (only
                                numbers allowed)</small>
                        </div>
                        <div class="form-group">
                            <label for="exec-name">Name<sup>*</sup></label>
                            <input type="text" id="exec-name" class="editable" name="name" value=""
                                placeholder="Enter Name" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid name (only
                                alphabets allowed)</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="email">Email<sup>*</sup></label>
                            <input type="email" id="email-exec" class="editable" name="email" value=""
                                placeholder="Enter Email" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid email</small>
                        </div>
                        <!-- </div>
                    <div class="row"> -->
                        <div class="form-group">
                            <label for="dept">Department<sup>*</sup></label>
                            <select name="dept_id" id="dept-exec" class="editable" required>
                                <option value="">Select Department</option>
                                <?php
                                if ($departments) {
                                    foreach ($departments as $department) {
                                        ?>
                                        <option value="<?= $department['dept_id'] ?>"><?= $department['dept_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row dynamic-fields">
                        <div class="form-group">
                            <label for="subdept-exe">Sub Department</label>
                            <select name="subdept_id" id="subdept-exe" class="editable">
                                <option value="">Select Sub Department</option>
                                <!-- Dynamically Populated on Dept. Selection -->
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                        <!-- </div>
                    <div class="row"> -->
                        <div class="form-group">
                            <label for="rm">Rep. Manager<sup>*</sup></label>
                            <select name="rm_id" id="rm" class="editable" required>
                                <option value="">Select Manager</option>
                                <?php
                                // skipping static population of rm
                                // if (false) {
                                if (!empty($repManagers)) {
                                    foreach ($repManagers as $repManager) {
                                        ?>
                                        <option value="<?= $repManager['emp_id'] ?>"><?= $repManager['name'] ?></option>
                                    <?php }
                                }
                                if (!empty($deptHeads)) {
                                    foreach ($deptHeads as $deptHead) {
                                        ?>
                                        <option value="<?= $deptHead['emp_id'] ?>"><?= $deptHead['name'] ?></option>
                                    <?php }
                                }
                                // }
                                ?>
                            </select>
                            <small class="error" style="color:red;display:none;"></small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="client-select">Select Client / Project(s)</label>
                            <select name="client-select"
                                class="form-control client-select client-select-executive editable">
                                <option value="">Select Client / Project(s)</option>
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
                        <div class="form-group">
                            <div class="client-badges client-badges-executive"></div>
                            <!-- hidden input to store multiple selected clients -->
                            <input type="hidden" name="clients" class="clients-hidden clients-hidden-executive">
                        </div>
                    </div>
                    <div class="row">
                        <!-- </div> -->
                        <!-- <div class="row">
                        <div class="form-group">
                            <label for="password">Password : </label>
                            <input type="password" id="password" class="editable" name="password"
                                value="<?= $employee['password'] ?>" placeholder="Enter Password" required>
                            <small class="error" style="color:red;display:none;">Please Enter a valid password (min
                                length 6
                                chars)</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirm Password : </label>
                            <input type="password" id="confirm-password" class="editable" name="confirm-password"
                                value="" placeholder="Re-enter Password" required>
                            <small class="error" style="color:red;display:none;">Passwords donot match</small>
                        </div>
                    </div> -->
                        <!-- <div class="row"> -->
                        <div class="form-group password-field">
                            <label for="password">Password<sup>*</sup></label>
                            <!-- <input type="password" id="password-exec" class="editable" name="password" value=""
                                placeholder="Enter Password" required disabled>
                            <small class="error" style="color:red;display:none;">Please Enter a valid password (min
                                length 6 chars)</small> -->
                            <div class="password-wrapper">
                                <input type="password" name="password" class="editable" id="password-exe"
                                    placeholder="Enter Password" required>
                                <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                            </div>
                            <small class="error" style="color:red;display:none;">Please Enter a valid password (min
                                length 6 chars)</small>
                        </div>
                        <!-- </div>
                    <div class="row"> -->
                        <div class="form-group password-field">
                            <label for="confirm-password">Confirm Password<sup>*</sup></label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm-password-exec" class="editable"
                                    name="confirm-password" value="" placeholder="Re-enter Password" required disabled>
                                <span class="eye-icon"><i class="fa-solid fa-eye-slash"></i></span>
                            </div>
                            <small class="error" style="color:red;display:none;">Passwords donot match</small>
                        </div>
                    </div>
                    <div class="row">
                        <!-- <div class="form-group"></div> -->
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

    <!-- Graphs Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- <script src="assets/js/graph.js"></script> -->

    <!-- <script src="assets/js/filters.js"></script> -->

    <script src="assets/js/profile.js"></script>

    <script>
        const executivesData = <?= json_encode($executives) ?>;
        const repManagersData = <?= json_encode($repManagers) ?>;
        const deptHeadsData = <?= json_encode($deptHeads) ?>;
    </script>


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
            paginateTable('tablebody-hod', 'hodPagination', 10);
            paginateTable('tablebody-rm', 'rmPagination', 10);
            paginateTable('tablebody-executive', 'executivePagination', 20);
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

    <!-- <script>
        document.addEventListener("DOMContentLoaded", function () {
            const select = document.getElementById("client-select");
            const badgesContainer = document.getElementById("client-badges");
            const hiddenInput = document.getElementById("clients-hidden");

            let selectedClients = [];

            // Preload assigned clients (server-side output)
            <?php if (!empty($assignedClients)): ?>
                selectedClients = <?php echo json_encode($assignedClients); ?>;
                <?php foreach ($assignedClients as $clientId): ?>
                        // create badge for each pre-assigned client
                        (function () {
                            const text = "<?php echo $clients[array_search($clientId, array_column($clients, 'client_id'))]['client_name']; ?>";
                            const badge = document.createElement("span");
                            badge.className = "client-badge";
                            badge.innerHTML = text + ' <span class="remove" data-value="<?php echo $clientId; ?>">&times;</span>';
                            document.getElementById("client-badges").appendChild(badge);
                        })();
                <?php endforeach; ?>
                hiddenInput.value = selectedClients.join(",");
            <?php endif; ?>

            // Handle new selection
            select.addEventListener("change", function () {
                const value = this.value;
                const text = this.options[this.selectedIndex].text;

                if (value && !selectedClients.includes(value)) {
                    selectedClients.push(value);

                    const badge = document.createElement("span");
                    badge.className = "client-badge";
                    badge.innerHTML = text + ' <span class="remove" data-value="' + value + '">&times;</span>';
                    badgesContainer.appendChild(badge);

                    updateHiddenInput();
                }

                this.value = "";
            });

            // Remove badge
            badgesContainer.addEventListener("click", function (e) {
                if (e.target.classList.contains("remove")) {
                    const value = e.target.getAttribute("data-value");
                    selectedClients = selectedClients.filter(v => v !== value);

                    e.target.parentElement.remove();
                    updateHiddenInput();
                }
            });

            function updateHiddenInput() {
                hiddenInput.value = selectedClients.join(",");
            }
        });
    </script> -->


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
        // echo $_SESSION['client_data'];
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