<?php
// api/get-resource-utilization.php — Allocated vs Actual per member across all PM's projects
session_start();
require_once '../config/config.php';
require_once '../classes/Project.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) { echo json_encode([]); exit; }

$projectObj = new Project($conn);
$role       = $_SESSION['emp_role'] ?? '';
$empId      = (int)($_SESSION['emp_id'] ?? 0);
$projectId  = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);

$members = $projectId
    ? $projectObj->getProjectMembers($projectId)
    : [];

// If no specific project, aggregate by member across all PM's projects
if (!$projectId) {
    $projects = $projectObj->getProjectsForDashboard($empId, $role);
    $memberMap = [];
    foreach ($projects as $p) {
        $mems = $projectObj->getProjectMembers((int)$p['project_id']);
        foreach ($mems as $m) {
            $key = $m['emp_id'];
            if (!isset($memberMap[$key])) {
                $memberMap[$key] = [
                    'name'          => $m['emp_name'],
                    'allocated_hrs' => 0,
                    'actual_hrs'    => 0,
                ];
            }
            $memberMap[$key]['allocated_hrs'] += floatval($m['allocated_hours']);
            $memberMap[$key]['actual_hrs']    += floatval($m['actual_hours']);
        }
    }
    echo json_encode(array_values($memberMap));
    exit;
}

$output = array_map(fn($m) => [
    'name'          => $m['emp_name'],
    'allocated_hrs' => (float)$m['allocated_hours'],
    'actual_hrs'    => (float)$m['actual_hours'],
    'utilization'   => $m['utilization_pct'],
    'status'        => $m['status_label'],
], $members);

echo json_encode($output);
