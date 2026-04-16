<?php
require_once '../config/config.php';
require_once '../classes/Task.php';

session_start();

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$employees = $input['employees'] ?? [];
$departments = $input['departments'] ?? [];
$repManagers = $input['repManagers'] ?? [];
$allowedEmployees = $input['allowedEmployees'] ?? [];
$startDate = $input['startDate'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $input['endDate'] ?? date('Y-m-d');

$taskObj = new Task($conn);
$data = $taskObj->getProjectUtilization($employees, $departments, $repManagers, $allowedEmployees, $startDate, $endDate);

echo json_encode($data);
