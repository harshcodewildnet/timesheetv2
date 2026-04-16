<?php
// api/get-monthly-trend.php — Monthly actual hours for T&M / Staff Aug projects (last 6 months)
session_start();
require_once '../config/config.php';
require_once '../classes/Project.php';
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in'])) { echo json_encode([]); exit; }

$projectObj = new Project($conn);
$role       = $_SESSION['emp_role'] ?? '';
$empId      = (int)($_SESSION['emp_id'] ?? 0);
$projects   = $projectObj->getProjectsForDashboard($empId, $role);

// Build last 6 months labels
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
}

$datasets = [];
foreach ($projects as $p) {
    if (!in_array($p['project_type'], ['time_material', 'staff_augmentation'])) continue;

    $data = [];
    foreach ($months as $month) {
        $data[] = $projectObj->getMonthlyActualHours((int)$p['project_id'], $month);
    }
    $budget = floatval($p['monthly_allocated_hours'] ?? 0);

    $datasets[] = [
        'project_id'            => $p['project_id'],
        'project_name'          => $p['project_name'],
        'monthly_budget'        => $budget,
        'monthly_actuals'       => $data,   // [ Jan, Feb, Mar, Apr, May, Jun ]
        'months'                => $months,
    ];
}

echo json_encode(['months' => $months, 'datasets' => $datasets]);
