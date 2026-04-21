<?php
header("X-Robots-Tag: noindex, nofollow", true);

require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Client.php';
require_once 'classes/Project.php';

requireRole(['admin', 'sales_manager', 'project_manager', 'hod']);

$emp_id   = $_SESSION['emp_id'];
$emp_role = $_SESSION['emp_role'];

$empObj     = new Employee($conn);
$clientObj  = new Client($conn);
$projectObj = new Project($conn);

$employee   = $empObj->getEmployeeById($emp_id);
$allClients = $clientObj->getActiveClients();

// Fetch all PMs for the "managed_by" dropdown
$pmQuery = $conn->query("SELECT emp_id, name FROM employee WHERE role IN ('project_manager','admin') AND status=1 ORDER BY name ASC");
$allPMs  = $pmQuery ? $pmQuery->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all active employees for member assignment (non-hod/non-admin)
$membersQuery = $conn->query("SELECT emp_id, name, role FROM employee WHERE status=1 AND role IN ('executive','rm','project_manager') ORDER BY name ASC");
$allEmployees = $membersQuery ? $membersQuery->fetch_all(MYSQLI_ASSOC) : [];
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
    <title>Manage Projects — WildNet Timesheet</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        /* ── Project Type Badges ── */
        .badge-type {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .badge-fixed       { background: #e0f0ff; color: #1565c0; }
        .badge-tm          { background: #fff3e0; color: #e65100; }
        .badge-staff       { background: #f3e5f5; color: #7b1fa2; }

        /* ── Performance Classification Badges ── */
        .badge-perf {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-efficient   { background: #e8f5e9; color: #2e7d32; }
        .badge-effective   { background: #e3f2fd; color: #1565c0; }
        .badge-poor        { background: #ffebee; color: #c62828; }

        /* ── Hours progress bar ── */
        .hours-bar-wrap    { min-width: 140px; }
        .hours-bar-bg      { background: #e0e0e0; border-radius: 6px; height: 8px; overflow: hidden; margin-top: 3px; }
        .hours-bar-fill    { height: 100%; border-radius: 6px; transition: width 0.5s ease; }
        .fill-ok           { background: #4caf50; }
        .hours-bar-fill.fill-warn { background-color: #f9a825; }
        .hours-bar-fill.fill-over { background-color: #c62828; }

        /* ── Modern Filter UI ── */
        .tab-panel .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #edf2f7;
        }
        .search-bar-wrapper {
            position: relative;
            flex: 1;
            max-width: 400px;
        }
        .search-bar-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 0.9rem;
        }
        .search-input {
            width: 100%;
            padding: 10px 12px 10px 36px !important;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            background-color: #f7fafc;
        }
        .search-input:focus {
            outline: none;
            border-color: #FEAD17;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(254, 173, 23, 0.1);
        }
        .filter-controls {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .filter-select {
            padding: 9px 32px 9px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.88rem;
            color: #4a5568;
            background: #f7fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%234a5568' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right 12px center;
            appearance: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-select:hover { border-color: #cbd5e0; }
        .filter-select:focus {
            outline: none;
            border-color: #FEAD17;
            box-shadow: 0 0 0 3px rgba(254, 173, 23, 0.1);
        }

        .add-btn {
            background-color: #FEAD17;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(254, 173, 23, 0.2);
        }
        .add-btn:hover {
            background-color: #e69a15;
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(254, 173, 23, 0.3);
        }
        .add-btn:active { transform: translateY(0); }

        /* ── Member table ── */
        .member-status-over     { color: #c62828; font-weight: 600; }
        .member-status-under    { color: #1565c0; }
        .member-status-on_track { color: #2e7d32; font-weight: 600; }

        /* ── Dynamic fields ── */
        .dynamic-section { display: none; }
        .dynamic-section.visible { display: block; }

        /* ── Project detail panel ── */
        #project-detail-panel {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px 24px;
            margin-top: 18px;
            display: none;
        }
        #project-detail-panel.visible { display: block; }
        .detail-summary-cards {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .summary-card {
            flex: 1;
            min-width: 160px;
            background: #f5f7fa;
            border-radius: 8px;
            padding: 14px 18px;
            text-align: center;
        }
        .summary-card .val { font-size: 1.6rem; font-weight: 700; }
        .summary-card .lbl { font-size: 0.78rem; color: #888; margin-top: 3px; }

        /* ── Active Project Highlight ── */
        .active-project-row {
            background-color: rgba(254, 173, 23, 0.12) !important;
            border-left: 4px solid #FEAD17 !important;
            box-shadow: inset 0 0 10px rgba(254, 173, 23, 0.05);
            transition: all 0.3s ease;
        }
        .active-project-row td {
            font-weight: 500;
        }
        .active-project-row td strong {
            color: #FEAD17;
        }

        /* ── Error Animations & Highlights ── */
        .input-error {
            border: 1.5px solid #c62828 !important;
            background-color: #fff8f8 !important;
        }
        .error-banner {
            background-color: #fce4e4;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 15px;
            animation: fadeInError 0.4s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        @keyframes fadeInError {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Centered Modal ── */
        .modal-dialog {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-dialog.show { display: flex; }
        .modal {
            background: #fff !important;
            border-radius: 12px !important;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2) !important;
            overflow: hidden;
            border: none !important;
            animation: modalSlideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalSlideUp {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
<header>
    <a href="dashboard-<?= $employee['role'] ?>">
        <div class="logo">
            <img src="assets/images/wnet-image.png" alt="WildNet logo">
        </div>
    </a>
    <div class="search-area"></div>
    <div class="actions"></div>
</header>

<?php include_once "includes/sidebar-" . $employee['role'] . ".php"; ?>

<main>
    <div class="top-row">
        <div class="left"><h3>Manage Projects</h3></div>
    </div>

    <div class="content-area">
        <div class="tab-container tasklist">
            <div class="tab-content">
                <div class="tab-panel active" id="tab-projects">

                    <!-- Top bar: search + filters + add button -->
                    <div class="top-row">
                        <div class="search-bar-wrapper">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="project-search" class="search-input" placeholder="Search projects by name, client or type…">
                        </div>
                        <div class="filter-controls">
                            <select id="filter-type" class="filter-select" style="width:180px;">
                                <option value="">All Types</option>
                                <option value="fixed_cost">Fixed Cost</option>
                                <option value="time_material">Time &amp; Material</option>
                                <option value="staff_augmentation">Staff Augmentation</option>
                            </select>
                            <select id="filter-status" class="filter-select" style="width:130px;">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <?php if (in_array($emp_role, ['admin', 'sales_manager'])): ?>
                            <button class="add-btn" id="add-project-btn">
                                <i class="fa-solid fa-plus"></i> Add Project
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Projects Table -->
                    <div class="panel-table-container">
                        <table id="project-table" class="panel-table">
                            <thead>
                                <tr class="table-head">
                                    <th>S.No.</th>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Budget / Allocation</th>
                                    <th>Actual Hours</th>
                                    <th>Performance</th>
                                    <th>Project Manager</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="project-table-body">
                                <tr>
                                    <td colspan="10" style="text-align:center;padding:24px;">
                                        <i class="fa-solid fa-spinner fa-spin"></i> Loading projects…
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Project Detail Panel (rendered below table on row click) -->
                    <div id="project-detail-panel">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                            <h4 id="detail-project-name" style="margin:0;"></h4>
                            <button class="cancel-btn" onclick="closeDetailPanel()">
                                <i class="fa-solid fa-circle-xmark"></i> Close
                            </button>
                        </div>

                        <!-- Summary Cards -->
                        <div class="detail-summary-cards" id="detail-summary-cards"></div>

                        <!-- Hours Progress Bar -->
                        <div id="detail-hours-bar" style="margin-bottom:18px;"></div>

                        <!-- Members Section -->
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <h5 style="margin:0;">Team Members</h5>
                            <?php if (in_array($emp_role, ['admin', 'sales_manager', 'project_manager'])): ?>
                            <button class="add-btn" id="add-member-btn" style="font-size:0.82rem;padding:7px 14px;">
                                <i class="fa-solid fa-user-plus"></i> Add Member
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="panel-table-container">
                            <table class="panel-table" id="member-table">
                                <thead>
                                    <tr class="table-head">
                                        <th>Name</th>
                                        <th>Role in Project</th>
                                        <th>Allocated Hrs</th>
                                        <th>Actual Hrs</th>
                                        <th>Utilization %</th>
                                        <th>Status</th>
                                        <?php if (in_array($emp_role, ['admin', 'sales_manager', 'project_manager'])): ?>
                                        <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="member-table-body">
                                    <tr><td colspan="7" style="text-align:center;">No members assigned yet.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- /.tab-panel -->
            </div>
        </div>
    </div>
</main>

<!-- ══════════════════════════════════════════════════════════
     ADD / EDIT PROJECT MODAL
══════════════════════════════════════════════════════════════ -->
<div class="modal-dialog" id="modal-project">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h4 class="modal-title" id="project-modal-title">Add Project</h4>
            <button class="close-btn" id="close-project-modal"><i class="fa-solid fa-circle-xmark"></i></button>
        </div>
        <hr>
        <div class="modal-body">
            <form id="project-form" class="profile-container">
                <input type="hidden" id="project-id-hidden">

                <!-- Common Fields -->
                <div class="row">
                    <div class="form-group">
                        <label for="f-project-name">Project Name <sup>*</sup></label>
                        <input type="text" id="f-project-name" class="editable" placeholder="Enter project name" required>
                        <small class="error" style="color:red;display:none;">Project name is required.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="f-client-id">Client <sup>*</sup></label>
                        <select id="f-client-id" class="editable" required>
                            <option value="">— Select Client —</option>
                            <?php foreach ($allClients as $cl): ?>
                            <option value="<?= htmlspecialchars($cl['client_id']) ?>">
                                <?= htmlspecialchars($cl['client_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="error" style="color:red;display:none;">Client is required.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="f-project-type">Project Type <sup>*</sup></label>
                        <select id="f-project-type" class="editable" required>
                            <option value="">— Select Type —</option>
                            <option value="fixed_cost">Fixed Cost Project</option>
                            <option value="time_material">Time &amp; Material (T&amp;M)</option>
                            <option value="staff_augmentation">Staff Augmentation</option>
                        </select>
                        <small class="error" style="color:red;display:none;">Project type is required.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="f-contact-email">Contact Email</label>
                        <input type="email" id="f-contact-email" class="editable" placeholder="Contact email (optional)">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="f-managed-by">Project Manager</label>
                        <select id="f-managed-by" class="editable">
                            <option value="">— Not Assigned —</option>
                            <?php foreach ($allPMs as $pm): ?>
                            <option value="<?= $pm['emp_id'] ?>">
                                <?= htmlspecialchars($pm['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- ── DYNAMIC: Fixed Cost Fields ── -->
                <div class="dynamic-section" id="section-fixed">
                    <hr style="margin:14px 0;">
                    <p style="font-size:0.8rem;color:#888;margin-bottom:10px;">Fixed Cost Project Details</p>
                    <div class="row">
                        <div class="form-group">
                            <label for="f-total-hours">Total Estimated Hours <sup>*</sup></label>
                            <input type="number" id="f-total-hours" class="editable" placeholder="e.g. 120" min="1" step="0.5">
                            <small class="error" style="color:red;display:none;">Required for Fixed Cost projects.</small>
                        </div>
                    </div>
                    <div class="row" id="original-hours-row" style="display:none;">
                        <div class="form-group">
                            <label>Original Estimated Hours <small style="color:#888;">(locked at creation)</small></label>
                            <input type="number" id="f-original-hours" class="editable" readonly
                                style="background:#f5f5f5;cursor:not-allowed;">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="f-start-date">Project Start Date</label>
                            <input type="date" id="f-start-date" class="editable">
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group">
                            <label for="f-end-date">Project End Date <sup>*</sup></label>
                            <input type="date" id="f-end-date" class="editable">
                            <small class="error" style="color:red;display:none;">End date required for Fixed Cost projects.</small>
                        </div>
                    </div>
                </div>

                <!-- ── DYNAMIC: T&M / Staff Aug Fields ── -->
                <div class="dynamic-section" id="section-monthly">
                    <hr style="margin:14px 0;">
                    <p style="font-size:0.8rem;color:#888;margin-bottom:10px;" id="section-monthly-label">
                        Monthly Allocation Details
                    </p>
                    <div class="row">
                        <div class="form-group">
                            <label for="f-monthly-hours">Monthly Allocated Hours <sup>*</sup></label>
                            <input type="number" id="f-monthly-hours" class="editable" placeholder="e.g. 80" min="1" step="0.5">
                            <small class="error" style="color:red;display:none;">Required for T&amp;M / Staff Augmentation projects.</small>
                        </div>
                    </div>
                </div>

                <div class="row bottom-row" style="margin-top:18px;">
                    <button type="submit" class="save-profile-btn" id="save-project-btn">Save Project</button>
                    <button type="button" class="cancel-btn" id="cancel-project-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     ADD / EDIT MEMBER MODAL
══════════════════════════════════════════════════════════════ -->
<div class="modal-dialog" id="modal-member">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h4 class="modal-title">Assign Team Member</h4>
            <button class="close-btn" id="close-member-modal"><i class="fa-solid fa-circle-xmark"></i></button>
        </div>
        <hr>
        <div class="modal-body">
            <form id="member-form" class="profile-container">
                <input type="hidden" id="member-project-id">
                <input type="hidden" id="member-row-id">
                <div class="row">
                    <div class="form-group">
                        <label for="f-member-emp">Employee <sup>*</sup></label>
                        <select id="f-member-emp" class="editable" required>
                            <option value="">— Select Employee —</option>
                            <?php foreach ($allEmployees as $em): ?>
                            <option value="<?= $em['emp_id'] ?>">
                                <?= htmlspecialchars($em['name']) ?> (<?= $em['role'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="f-member-role">Role in Project</label>
                        <input type="text" id="f-member-role" class="editable" placeholder="e.g. SEO Analyst, Developer">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label for="f-member-hours">Allocated Hours</label>
                        <input type="number" id="f-member-hours" class="editable" placeholder="e.g. 40" min="1" step="0.5">
                        <div id="allocation-hint" style="font-size:0.8rem; color:#666; margin-top:5px; font-weight:500;"></div>
                    </div>
                </div>

                <!-- Error Banner positioned just above actions -->
                <div id="member-error-msg" class="error-banner" style="display:none;"></div>

                <div class="row bottom-row" style="margin-top:14px;">
                    <button type="submit" class="save-profile-btn" id="save-member-btn">Confirm Assignment</button>
                    <button type="button" class="cancel-btn" id="cancel-member-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     CONFIRM DELETE MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal-dialog" id="modal-member-delete">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <h4 class="modal-title">Remove Team Member</h4>
            <button class="close-btn" onclick="closeModal('member-delete')"><i class="fa-solid fa-circle-xmark"></i></button>
        </div>
        <hr>
        <div class="modal-body" style="text-align:center; padding: 30px 24px;">
            <div style="margin-bottom: 20px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 3.8rem; color: #FEAD17;"></i>
            </div>
            <h3 style="margin-bottom: 12px; font-weight: 700; color: #333;">Are you sure?</h3>
            <p style="color: #666; font-size: 1rem; margin-bottom: 30px; line-height: 1.5;">This action will remove the member from the project. <br>This cannot be undone.</p>
            
            <div style="display:flex; gap:16px; justify-content:center;">
                <button type="button" class="add-btn" id="confirm-member-remove" 
                    style="flex: 1; justify-content: center; padding: 12px;">Remove</button>
                <button type="button" class="cancel-btn" onclick="closeModal('member-delete')" 
                    style="flex: 1; justify-content: center; padding: 12px; border-radius: 8px;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Shared UI Elements -->
<div id="toast">Saved!</div>
<div id="spinner-overlay"><div class="spinner"></div></div>
<div id="custom-alert" class="alert-overlay">
    <div class="alert-box" id="alert-box">
        <div class="alert-header"><h4>Alert</h4></div>
        <p id="alert-message">Alert Message</p>
        <div class="alert-actions">
            <button id="alert-confirm-btn">Yes</button>
            <button id="alert-cancel-btn">No</button>
        </div>
    </div>
</div>

<?php include_once 'photo-modal.php'; ?>

<script>
// ─────────────────────────────────────────────────────────────────
// UTILITY HELPERS
// ─────────────────────────────────────────────────────────────────
const userRole = '<?= $emp_role ?>';
const canManage = ['admin','sales_manager','project_manager'].includes(userRole);
let currentProjectReport = null; // Store report for budget checks in UI

function triggerNotification(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.backgroundColor = isError ? '#ef828c' : '#5eda7b';
    t.className = 'show';
    setTimeout(() => t.classList.remove('show'), 3000);
}
function showSpinner()  { document.getElementById('spinner-overlay').style.display = 'block'; }
function hideSpinner()  { document.getElementById('spinner-overlay').style.display = 'none'; }
function openModal(id)  { document.getElementById('modal-' + id).classList.add('show'); }
function closeModal(id) { document.getElementById('modal-' + id).classList.remove('show'); }

const typeBadge = {
    fixed_cost:         '<span class="badge-type badge-fixed">Fixed Cost</span>',
    time_material:      '<span class="badge-type badge-tm">T&amp;M</span>',
    staff_augmentation: '<span class="badge-type badge-staff">Staff Aug</span>'
};
const perfBadge = {
    'Highly Efficient': '<span class="badge-perf badge-efficient">🟢 Highly Efficient</span>',
    'Effective':        '<span class="badge-perf badge-effective">🔵 Effective</span>',
    'Poorly Managed':   '<span class="badge-perf badge-poor">🔴 Poorly Managed</span>'
};

function hoursBar(actual, budget, label) {
    const pct   = budget > 0 ? Math.min((actual / budget) * 100, 120) : 0;
    const fillClass = pct > 100 ? 'fill-over' : pct > 85 ? 'fill-warn' : 'fill-ok';
    return `
        <div class="hours-bar-wrap">
            <div class="hours-bar-text">${label}</div>
            <div class="hours-bar-bg">
                <div class="hours-bar-fill ${fillClass}" style="width:${Math.min(pct,100)}%"></div>
            </div>
        </div>`;
}

// ─────────────────────────────────────────────────────────────────
// LOAD PROJECTS TABLE
// ─────────────────────────────────────────────────────────────────
let activeFilters = {};

async function loadProjects() {
    showSpinner();
    try {
        const res  = await fetch('api/project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'list', ...activeFilters })
        });
        const json = await res.json();
        if (!json.success) { triggerNotification(json.message || 'Failed to load projects.', true); return; }

        renderProjectTable(json.projects);
    } catch(e) {
        triggerNotification('Network error.', true);
    } finally {
        hideSpinner();
    }
}

function renderProjectTable(projects) {
    const tbody = document.getElementById('project-table-body');
    if (!projects || !projects.length) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:24px;"><h3>No Projects Found</h3></td></tr>`;
        return;
    }

    tbody.innerHTML = projects.map((p, i) => {
        const perf    = p.performance || {};
        const pLabel  = perf.classification || '—';
        const actual  = perf.actual_hours  || 0;
        const budget  = perf.budget_hours  || 0;

        let budgetCell = '—';
        if (p.project_type === 'fixed_cost') {
            budgetCell = `${p.total_estimated_hours || 0} hrs total`;
        } else {
            budgetCell = `${p.monthly_allocated_hours || 0} hrs/month`;
        }

        const toggleChecked = p.is_active == 1 ? 'checked' : '';
        const actions = canManage
            ? `<i class="fa-solid fa-pencil project-edit-btn" data-id="${p.project_id}" title="Edit"></i>
               <i class="fa-solid fa-users project-members-btn" data-id="${p.project_id}"
                  data-name="${p.project_name}" title="View Members"></i>`
            : `<i class="fa-solid fa-eye project-members-btn" data-id="${p.project_id}"
                  data-name="${p.project_name}" title="View"></i>`;

        const isActiveClass = (currentDetailProjectId === p.project_id) ? 'active-project-row' : '';

        return `
        <tr data-id="${p.project_id}" class="${isActiveClass}">
            <td>${i + 1}.</td>
            <td><strong>${p.project_name}</strong></td>
            <td>${p.client_name || '—'}</td>
            <td>${typeBadge[p.project_type] || p.project_type}</td>
            <td>${budgetCell}</td>
            <td>${actual} hrs</td>
            <td>${perfBadge[pLabel] || pLabel}</td>
            <td>${p.managed_by_name || '<em style="color:#aaa;">—</em>'}</td>
            <td>
                <label class="switch">
                    <input type="checkbox" class="project-toggle-status"
                        data-id="${p.project_id}" ${toggleChecked}
                        ${canManage ? '' : 'disabled'}>
                    <span class="slider round"></span>
                </label>
            </td>
            <td class="actions">${actions}</td>
        </tr>`;
    }).join('');

    attachTableEvents();
}

function attachTableEvents() {
    // Edit project buttons
    document.querySelectorAll('.project-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => openEditProjectModal(parseInt(btn.dataset.id)));
    });
    // Members panel buttons
    document.querySelectorAll('.project-members-btn').forEach(btn => {
        btn.addEventListener('click', () => openDetailPanel(parseInt(btn.dataset.id), btn.dataset.name));
    });
    // Toggle status
    document.querySelectorAll('.project-toggle-status').forEach(chk => {
        chk.addEventListener('change', async () => {
            const id     = parseInt(chk.dataset.id);
            const status = chk.checked ? 1 : 0;
            const res  = await fetch('api/project.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_status', project_id: id, status })
            });
            const json = await res.json();
            triggerNotification(json.success ? (status ? 'Project activated.' : 'Project deactivated.') : (json.message || 'Failed.'), !json.success);
        });
    });
}

// ─────────────────────────────────────────────────────────────────
// PROJECT FORM — Dynamic Fields
// ─────────────────────────────────────────────────────────────────
function updateDynamicFields() {
    const type         = document.getElementById('f-project-type').value;
    const secFixed     = document.getElementById('section-fixed');
    const secMonthly   = document.getElementById('section-monthly');
    const monthLabel   = document.getElementById('section-monthly-label');
    const totalHours   = document.getElementById('f-total-hours');
    const endDate      = document.getElementById('f-end-date');
    const monthlyHours = document.getElementById('f-monthly-hours');

    // Hide all dynamic sections first
    secFixed.classList.remove('visible');
    secMonthly.classList.remove('visible');

    // Remove required from all dynamic fields
    [totalHours, endDate, monthlyHours].forEach(el => el.removeAttribute('required'));

    if (type === 'fixed_cost') {
        secFixed.classList.add('visible');
        totalHours.setAttribute('required', 'required');
        endDate.setAttribute('required', 'required');
    } else if (type === 'time_material') {
        secMonthly.classList.add('visible');
        monthLabel.textContent = 'Time & Material Details';
        monthlyHours.setAttribute('required', 'required');
    } else if (type === 'staff_augmentation') {
        secMonthly.classList.add('visible');
        monthLabel.textContent = 'Staff Augmentation Details';
        monthlyHours.setAttribute('required', 'required');
    }
}

document.getElementById('f-project-type').addEventListener('change', updateDynamicFields);

// ─────────────────────────────────────────────────────────────────
// ADD PROJECT MODAL
// ─────────────────────────────────────────────────────────────────
document.getElementById('add-project-btn')?.addEventListener('click', () => {
    document.getElementById('project-modal-title').textContent = 'Add Project';
    document.getElementById('project-form').reset();
    document.getElementById('project-id-hidden').value = '';
    document.getElementById('original-hours-row').style.display = 'none';
    updateDynamicFields();
    openModal('project');
});

document.getElementById('close-project-modal').addEventListener('click', () => closeModal('project'));
document.getElementById('cancel-project-btn').addEventListener('click', () => closeModal('project'));

// ─────────────────────────────────────────────────────────────────
// EDIT PROJECT MODAL
// ─────────────────────────────────────────────────────────────────
async function openEditProjectModal(projectId) {
    showSpinner();
    try {
        const res  = await fetch('api/project.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get', project_id: projectId })
        });
        const json = await res.json();
        if (!json.success) { triggerNotification(json.message || 'Failed.', true); return; }

        const p = json.project;
        document.getElementById('project-modal-title').textContent = 'Edit Project';
        document.getElementById('project-id-hidden').value     = p.project_id;
        document.getElementById('f-project-name').value         = p.project_name     || '';
        document.getElementById('f-client-id').value            = p.client_id        || '';
        document.getElementById('f-project-type').value         = p.project_type     || '';
        document.getElementById('f-contact-email').value        = p.contact_email    || '';
        document.getElementById('f-managed-by').value           = p.managed_by       || '';
        document.getElementById('f-total-hours').value          = p.total_estimated_hours   || '';
        document.getElementById('f-original-hours').value       = p.original_estimated_hours || '';
        document.getElementById('f-start-date').value           = p.project_start_date      || '';
        document.getElementById('f-end-date').value             = p.project_end_date        || '';
        document.getElementById('f-monthly-hours').value        = p.monthly_allocated_hours || '';

        // Show original hours row in edit mode
        document.getElementById('original-hours-row').style.display = 'block';

        updateDynamicFields();
        openModal('project');
    } finally {
        hideSpinner();
    }
}

// ─────────────────────────────────────────────────────────────────
// SAVE PROJECT (Create / Update)
// ─────────────────────────────────────────────────────────────────
document.getElementById('project-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    showSpinner();

    const projectId = document.getElementById('project-id-hidden').value;
    const isEdit    = !!projectId;

    const payload = {
        action:                  isEdit ? 'edit' : 'create',
        project_id:              projectId || undefined,
        project_name:            document.getElementById('f-project-name').value.trim(),
        client_id:               document.getElementById('f-client-id').value,
        project_type:            document.getElementById('f-project-type').value,
        contact_email:           document.getElementById('f-contact-email').value.trim(),
        managed_by:              document.getElementById('f-managed-by').value || null,
        total_estimated_hours:   document.getElementById('f-total-hours').value || null,
        project_start_date:      document.getElementById('f-start-date').value || null,
        project_end_date:        document.getElementById('f-end-date').value   || null,
        monthly_allocated_hours: document.getElementById('f-monthly-hours').value || null,
    };

    try {
        const res  = await fetch('api/project.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.success) {
            closeModal('project');
            triggerNotification(isEdit ? 'Project updated!' : 'Project created!');
            loadProjects();
        } else {
            triggerNotification(json.message || 'Failed to save project.', true);
        }
    } catch(err) {
        triggerNotification('Network error.', true);
    } finally {
        hideSpinner();
    }
});

// ─────────────────────────────────────────────────────────────────
// PROJECT DETAIL PANEL (Members + Hours)
// ─────────────────────────────────────────────────────────────────
let currentDetailProjectId = null;

async function openDetailPanel(projectId, projectName) {
    currentDetailProjectId = projectId;
    document.getElementById('detail-project-name').textContent = projectName;
    document.getElementById('member-project-id').value = projectId;

    // Remove highlight from all rows and add to current
    document.querySelectorAll('#project-table-body tr').forEach(row => {
        row.classList.toggle('active-project-row', parseInt(row.dataset.id) === projectId);
    });

    const panel = document.getElementById('project-detail-panel');
    panel.classList.add('visible');
    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });

    await loadProjectDetail(projectId);
}

function closeDetailPanel() {
    document.getElementById('project-detail-panel').classList.remove('visible');
    currentDetailProjectId = null;
    // Clear highlight from all rows
    document.querySelectorAll('#project-table-body tr').forEach(row => {
        row.classList.remove('active-project-row');
    });
}

async function loadProjectDetail(projectId) {
    showSpinner();
    try {
        const [utilRes, membersRes] = await Promise.all([
            fetch('api/project.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_utilization', project_id: projectId })
            }),
            fetch('api/project.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_members', project_id: projectId })
            })
        ]);

        const utilJson    = await utilRes.json();
        const membersJson = await membersRes.json();

        if (utilJson.success) renderDetailSummary(utilJson.report);
        if (membersJson.success) renderMemberTable(membersJson.members);
    } finally {
        hideSpinner();
    }
}

function renderDetailSummary(report) {
    currentProjectReport = report;
    const cards = document.getElementById('detail-summary-cards');
    const bar   = document.getElementById('detail-hours-bar');

    const pBadge = perfBadge[report.classification] || report.classification;
    const statusColor = report.status === 'over' ? '#c62828' : report.status === 'under' ? '#1565c0' : '#2e7d32';

    cards.innerHTML = `
        <div class="summary-card">
            <div class="val">${report.budget_hours || 0}</div>
            <div class="lbl">Budget Hours</div>
        </div>
        <div class="summary-card">
            <div class="val">${report.actual_hours || 0}</div>
            <div class="lbl">Actual Hours</div>
        </div>
        <div class="summary-card">
            <div class="val" style="color:${statusColor}">${report.remaining_hours || 0}</div>
            <div class="lbl">Remaining Hours</div>
        </div>
        <div class="summary-card">
            <div class="val">${report.utilization_pct || 0}%</div>
            <div class="lbl">Utilization</div>
        </div>
        <div class="summary-card">
            <div style="margin-top:6px;">${pBadge}</div>
            <div class="lbl">Performance</div>
        </div>`;

    const budgetLabel = report.project_type === 'fixed_cost'
        ? `${report.actual_hours} / ${report.budget_hours} hrs`
        : `${report.actual_hours} / ${report.budget_hours} hrs this month`;
    bar.innerHTML = hoursBar(report.actual_hours, report.budget_hours, budgetLabel);
}

function renderMemberTable(members) {
    const tbody = document.getElementById('member-table-body');
    if (!members || !members.length) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;">No members assigned yet.</td></tr>`;
        return;
    }

    tbody.innerHTML = members.map(m => {
        const statusClass = `member-status-${m.status_label}`;
        const statusText  = m.status_label === 'over' ? '⚠️ Over' :
                            m.status_label === 'under' ? '🔵 Under' : '✅ On Track';
        const actionsCell = canManage
            ? `<td class="actions">
                 <i class="fa-solid fa-pencil member-edit-btn" data-id="${m.member_row_id}"
                    data-emp="${m.emp_id}" data-hours="${m.allocated_hours}" data-role="${m.role_in_project||''}"></i>
                 <i class="fa-solid fa-trash-can member-remove-btn"
                    data-project="${currentDetailProjectId}" data-emp="${m.emp_id}"></i>
               </td>`
            : '';

        return `
        <tr>
            <td>${m.emp_name}</td>
            <td>${m.role_in_project || '—'}</td>
            <td>${m.allocated_hours ?? '—'}</td>
            <td>${m.actual_hours}</td>
            <td>${m.utilization_pct}%</td>
            <td class="${statusClass}">${statusText}</td>
            ${actionsCell}
        </tr>`;
    }).join('');

    // Attach edit events
    document.querySelectorAll('.member-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('member-form').reset();
            const errDiv = document.getElementById('member-error-msg');
            errDiv.style.display = 'none';
            document.getElementById('f-member-hours').classList.remove('input-error');

            document.getElementById('member-row-id').value = btn.dataset.id;
            document.getElementById('f-member-emp').value   = btn.dataset.emp;
            document.getElementById('f-member-emp').setAttribute('disabled', 'disabled'); // Lock emp during edit
            document.getElementById('f-member-hours').value = btn.dataset.hours;
            document.getElementById('f-member-role').value  = btn.dataset.role;

            // Show allocation hint
            const hint = document.getElementById('allocation-hint');
            if (currentProjectReport && currentProjectReport.project_type === 'fixed_cost') {
                const available = (currentProjectReport.remaining_allocation || 0) + parseFloat(btn.dataset.hours || 0);
                hint.innerHTML = `<i class="fa-solid fa-info-circle"></i> Max ${available.toFixed(2)} hrs available for this member.`;
                hint.style.color = "#FEAD17";
            } else {
                hint.textContent = '';
            }

            openModal('member');
        });
    });

    // Attach remove events
    let pendingRemoval = null;
    document.querySelectorAll('.member-remove-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            pendingRemoval = { 
                projectId: parseInt(btn.dataset.project), 
                empId: parseInt(btn.dataset.emp) 
            };
            openModal('member-delete');
        });
    });

    document.getElementById('confirm-member-remove').onclick = async () => {
        if (!pendingRemoval) return;
        closeModal('member-delete');
        await removeMember(pendingRemoval.projectId, pendingRemoval.empId);
        pendingRemoval = null;
    };
}

// ─────────────────────────────────────────────────────────────────
// ADD / EDIT MEMBER
// ─────────────────────────────────────────────────────────────────
document.getElementById('add-member-btn')?.addEventListener('click', () => {
    document.getElementById('member-form').reset();
    document.getElementById('member-error-msg').style.display = 'none';
    document.getElementById('f-member-hours').classList.remove('input-error');
    document.getElementById('f-member-emp').removeAttribute('disabled'); // Ensure emp can be selected for new add
    document.getElementById('member-row-id').value = '';
    document.getElementById('member-project-id').value = currentDetailProjectId;

    // Show allocation hint for new member
    const hint = document.getElementById('allocation-hint');
    if (currentProjectReport && currentProjectReport.project_type === 'fixed_cost') {
        const available = (currentProjectReport.remaining_allocation || 0);
        hint.innerHTML = `<i class="fa-solid fa-info-circle"></i> ${available.toFixed(2)} hrs remaining to allocate in this project.`;
        hint.style.color = available > 0 ? "#2e7d32" : "#c62828";
    } else {
        hint.textContent = '';
    }

    openModal('member');
});
document.getElementById('close-member-modal').addEventListener('click', () => closeModal('member'));
document.getElementById('cancel-member-modal').addEventListener('click', () => closeModal('member'));

document.getElementById('member-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    showSpinner();
    const projectId = parseInt(document.getElementById('member-project-id').value);
    const memberId  = document.getElementById('member-row-id').value;
    const empId     = parseInt(document.getElementById('f-member-emp').value);
    const role      = document.getElementById('f-member-role').value.trim();
    const hours     = parseFloat(document.getElementById('f-member-hours').value) || null;

    if (!memberId && !empId) { triggerNotification('Please select an employee.', true); hideSpinner(); return; }

    const isEdit = !!memberId;
    const action = isEdit ? 'edit_member' : 'add_member';
    const payload = isEdit 
        ? { action, member_row_id: memberId, role_in_project: role, allocated_hours: hours }
        : { action, project_id: projectId, emp_id: empId, role_in_project: role, allocated_hours: hours };

    try {
        const res  = await fetch('api/project.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        const errDiv = document.getElementById('member-error-msg');
        const hoursInput = document.getElementById('f-member-hours');

        if (json.success) {
            errDiv.style.display = 'none';
            hoursInput.classList.remove('input-error');
            closeModal('member');
            triggerNotification(isEdit ? 'Member updated!' : 'Member assigned!');
            await loadProjectDetail(projectId);
        } else {
            console.error("Budget Validation Error:", json.message);
            errDiv.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> <span>${json.message || 'Failed.'}</span>`;
            errDiv.style.display = 'flex';
            hoursInput.classList.add('input-error');
        }
    } finally { hideSpinner(); }
});

// Clear error state when user changes the hours
document.getElementById('f-member-hours').addEventListener('input', function() {
    this.classList.remove('input-error');
    const errDiv = document.getElementById('member-error-msg');
    if (errDiv.style.display !== 'none') {
        errDiv.style.display = 'none';
    }
});

async function removeMember(projectId, empId) {
    showSpinner();
    try {
        const res  = await fetch('api/project.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove_member', project_id: projectId, emp_id: empId })
        });
        const json = await res.json();
        triggerNotification(json.success ? 'Member removed.' : (json.message || 'Failed.'), !json.success);
        if (json.success) await loadProjectDetail(projectId);
    } finally { hideSpinner(); }
}

// ─────────────────────────────────────────────────────────────────
// FILTERS
// ─────────────────────────────────────────────────────────────────
document.getElementById('filter-type').addEventListener('change', function() {
    activeFilters.project_type = this.value || undefined;
    loadProjects();
});
document.getElementById('filter-status').addEventListener('change', function() {
    if (this.value !== '') activeFilters.is_active = parseInt(this.value);
    else delete activeFilters.is_active;
    loadProjects();
});
document.getElementById('project-search').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#project-table-body tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ─────────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', loadProjects);
</script>

<script src="assets/js/main.js"></script>
<script src="assets/js/alert.js"></script>

<?php if (!empty($_SESSION['message'])): ?>
<script>
    triggerNotification('<?= $_SESSION['message'] ?>', <?= !$_SESSION['success'] ? 'true' : 'false' ?>);
</script>
<?php unset($_SESSION['message'], $_SESSION['success']); endif; ?>
</body>
</html>
