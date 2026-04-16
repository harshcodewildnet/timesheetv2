<?php
// api/project.php
// Action-based API for Project Time Tracking & Classification module.
// Follows same pattern as api/employee.php

session_start();
require_once '../config/config.php';
require_once '../classes/Project.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Auth check — must be logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$role   = $_SESSION['emp_role'] ?? '';
$empId  = (int) ($_SESSION['emp_id'] ?? 0);
$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? ($_GET['action'] ?? '');

$projectObj = new Project($conn);

// ─────────────────────────────────────────────────────────────────────────────
// ROLE PERMISSION GROUPS
// ─────────────────────────────────────────────────────────────────────────────
$canCreate       = in_array($role, ['admin', 'sales_manager']);
$canManageMembers= in_array($role, ['admin', 'sales_manager', 'project_manager']);
$canView         = in_array($role, ['admin', 'sales_manager', 'project_manager', 'hod', 'rm', 'executive']);

// ─────────────────────────────────────────────────────────────────────────────
switch ($action) {

    // ─── CREATE PROJECT ───────────────────────────────────────────────────────
    case 'create':
        if (!$canCreate) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }

        $data['created_by'] = $empId;
        // managed_by defaults to creator if not set
        if (empty($data['managed_by'])) {
            $data['managed_by'] = null;
        }

        $result = $projectObj->createProject($data);
        echo json_encode($result);
        break;

    // ─── EDIT PROJECT ─────────────────────────────────────────────────────────
    case 'edit':
        if (!$canCreate && $role !== 'project_manager') {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $id = (int) ($data['project_id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'project_id is required.']);
            exit;
        }
        // Protect original_estimated_hours — remove from data if someone sends it
        unset($data['original_estimated_hours']);

        $result = $projectObj->updateProject($id, $data);
        echo json_encode($result);
        break;

    // ─── GET SINGLE PROJECT ───────────────────────────────────────────────────
    case 'get':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $id = (int) ($data['project_id'] ?? $_GET['project_id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'project_id is required.']);
            exit;
        }
        $project = $projectObj->getProjectById($id);
        if (!$project) {
            echo json_encode(['success' => false, 'message' => 'Project not found.']);
            exit;
        }
        echo json_encode(['success' => true, 'project' => $project]);
        break;

    // ─── LIST ALL PROJECTS (paginated) ────────────────────────────────────────
    case 'list':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $filters = [];
        if (!empty($data['client_id']))     $filters['client_id']    = $data['client_id'];
        if (!empty($data['project_type']))  $filters['project_type'] = $data['project_type'];
        if (isset($data['is_active']))      $filters['is_active']    = (int) $data['is_active'];
        if (!empty($data['managed_by']))    $filters['managed_by']   = (int) $data['managed_by'];
        if (!empty($data['created_by']))    $filters['created_by']   = (int) $data['created_by'];

        // SM sees only projects they created; PM sees only managed; admin sees all
        if ($role === 'sales_manager')   $filters['created_by'] = $empId;
        if ($role === 'project_manager') $filters['managed_by'] = $empId;

        $page  = (int) ($data['page'] ?? 1);
        $limit = (int) ($data['limit'] ?? 50);

        $result = $projectObj->getAllProjects($filters, $page, $limit);
        echo json_encode(['success' => true] + $result);
        break;

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────
    case 'toggle_status':
        if (!$canCreate) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $id     = (int) ($data['project_id'] ?? 0);
        $status = (int) ($data['status'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'project_id is required.']);
            exit;
        }
        $ok = $projectObj->toggleProjectStatus($id, $status);
        echo json_encode(['success' => $ok]);
        break;

    // ─── ADD MEMBER ───────────────────────────────────────────────────────────
    case 'add_member':
        if (!$canManageMembers) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $projectId      = (int) ($data['project_id'] ?? 0);
        $memberEmpId    = (int) ($data['emp_id'] ?? 0);
        $allocatedHours = isset($data['allocated_hours']) ? (float) $data['allocated_hours'] : null;
        $roleInProject  = $data['role_in_project'] ?? null;

        if (!$projectId || !$memberEmpId) {
            echo json_encode(['success' => false, 'message' => 'project_id and emp_id are required.']);
            exit;
        }

        $result = $projectObj->addProjectMember($projectId, $memberEmpId, $allocatedHours, $roleInProject);
        echo json_encode($result);
        break;

    // ─── EDIT MEMBER ──────────────────────────────────────────────────────────
    case 'edit_member':
        if (!$canManageMembers) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $memberId = (int) ($data['member_row_id'] ?? 0);
        if (!$memberId) {
            echo json_encode(['success' => false, 'message' => 'member_row_id is required.']);
            exit;
        }
        $result = $projectObj->updateProjectMember($memberId, $data);
        echo json_encode($result);
        break;

    // ─── REMOVE MEMBER ────────────────────────────────────────────────────────
    case 'remove_member':
        if (!$canManageMembers) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $projectId   = (int) ($data['project_id'] ?? 0);
        $memberEmpId = (int) ($data['emp_id'] ?? 0);
        if (!$projectId || !$memberEmpId) {
            echo json_encode(['success' => false, 'message' => 'project_id and emp_id are required.']);
            exit;
        }
        $ok = $projectObj->removeProjectMember($projectId, $memberEmpId);
        echo json_encode(['success' => $ok]);
        break;

    // ─── GET MEMBERS ──────────────────────────────────────────────────────────
    case 'get_members':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $id = (int) ($data['project_id'] ?? $_GET['project_id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'project_id is required.']);
            exit;
        }
        $members = $projectObj->getProjectMembers($id);
        echo json_encode(['success' => true, 'members' => $members]);
        break;

    // ─── GET UTILIZATION ──────────────────────────────────────────────────────
    case 'get_utilization':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $id = (int) ($data['project_id'] ?? $_GET['project_id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'project_id is required.']);
            exit;
        }
        $report = $projectObj->getUtilizationReport($id);
        echo json_encode(['success' => true, 'report' => $report]);
        break;

    // ─── GET PERFORMANCE ──────────────────────────────────────────────────────
    case 'get_performance':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $id = (int) ($data['project_id'] ?? $_GET['project_id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'project_id is required.']);
            exit;
        }
        $perf = $projectObj->getProjectPerformance($id);
        echo json_encode(['success' => true, 'performance' => $perf]);
        break;

    // ─── GET MONTHLY HOURS (T&M) ──────────────────────────────────────────────
    case 'get_monthly_hours':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $id    = (int) ($data['project_id'] ?? $_GET['project_id'] ?? 0);
        $month = $data['month'] ?? $_GET['month'] ?? date('Y-m');
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'project_id is required.']);
            exit;
        }
        $hours = $projectObj->getMonthlyActualHours($id, $month);
        echo json_encode(['success' => true, 'month' => $month, 'actual_hours' => $hours]);
        break;

    // ─── GET PROJECTS BY CLIENT (for dropdowns) ───────────────────────────────
    case 'get_projects_by_client':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $clientId = $data['client_id'] ?? $_GET['client_id'] ?? '';
        if (!$clientId) {
            echo json_encode(['success' => false, 'message' => 'client_id is required.']);
            exit;
        }
        $projects = $projectObj->getProjectsByClientId($clientId);
        echo json_encode(['success' => true, 'projects' => $projects]);
        break;

    // ─── GET ACTIVE PROJECTS FOR TASK FORM ───────────────────────────────────
    case 'get_active_projects':
        // Executives & RMs: only assigned projects
        // SM / PM / Admin: all active projects
        if (in_array($role, ['executive', 'rm'])) {
            $projects = $projectObj->getActiveProjectsForEmployee($empId);
        } else {
            $projects = $projectObj->getAllActiveProjects();
        }
        echo json_encode(['success' => true, 'projects' => $projects]);
        break;

    // ─── DASHBOARD FEED ───────────────────────────────────────────────────────
    case 'get_dashboard_projects':
        if (!$canView) {
            echo json_encode(['success' => false, 'message' => 'Permission denied.']);
            exit;
        }
        $projects = $projectObj->getProjectsForDashboard($empId, $role);
        echo json_encode(['success' => true, 'projects' => $projects]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}
