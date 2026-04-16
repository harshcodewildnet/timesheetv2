<?php
// api/get-project-hours.php — Estimated vs Actual hours per project (for bar chart)
session_start();
require_once '../config/config.php';
require_once '../classes/Project.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) { echo json_encode([]); exit; }

$projectObj = new Project($conn);
$role       = $_SESSION['emp_role'] ?? '';
$empId      = (int)($_SESSION['emp_id'] ?? 0);
$projects   = $projectObj->getProjectsForDashboard($empId, $role);

$output = [];
foreach ($projects as $p) {
    $perf = $p['performance'] ?? [];
    $output[] = [
        'name'          => $p['project_name'],
        'type'          => $p['project_type'],
        'budget_hours'  => $perf['budget_hours']  ?? 0,
        'actual_hours'  => $perf['actual_hours']  ?? 0,
        'classification'=> $perf['classification'] ?? 'Unknown',
        'utilization'   => $perf['utilization_pct'] ?? 0,
    ];
}
echo json_encode($output);
