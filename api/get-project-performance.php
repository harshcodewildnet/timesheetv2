<?php
// api/get-project-performance.php — Performance classification for all accessible projects
session_start();
require_once '../config/config.php';
require_once '../classes/Project.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) { echo json_encode([]); exit; }

$projectObj = new Project($conn);
$role       = $_SESSION['emp_role'] ?? '';
$empId      = (int)($_SESSION['emp_id'] ?? 0);
$projects   = $projectObj->getProjectsForDashboard($empId, $role);

$summary = ['highly_efficient' => 0, 'effective' => 0, 'poorly_managed' => 0, 'total' => 0];
$list = [];

foreach ($projects as $p) {
    $perf  = $p['performance'] ?? [];
    $label = $perf['classification'] ?? 'Unknown';
    $list[] = [
        'project_id'     => $p['project_id'],
        'project_name'   => $p['project_name'],
        'project_type'   => $p['project_type'],
        'classification' => $label,
        'utilization_pct'=> $perf['utilization_pct'] ?? 0,
        'actual_hours'   => $perf['actual_hours']    ?? 0,
        'budget_hours'   => $perf['budget_hours']    ?? 0,
    ];

    $summary['total']++;
    if ($label === 'Highly Efficient') $summary['highly_efficient']++;
    elseif ($label === 'Effective')    $summary['effective']++;
    elseif ($label === 'Poorly Managed') $summary['poorly_managed']++;
}

echo json_encode(['summary' => $summary, 'projects' => $list]);
