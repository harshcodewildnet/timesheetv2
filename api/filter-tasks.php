<?php

require_once '../config/config.php';
require_once '../classes/Task.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
// $employeeRole = $input['role'] ?? 'executive';
// $loggedUserId = $input['loggedUserId'] ?? '';
$allowedEmployees = $input['allowedEmployees'] ?? [];
$employees = $input['employees'] ?? [];
$departments = $input['departments'] ?? [];
$managers = $input['managers'] ?? [];
$status = $input['status'] ?? '';
$timeline = $input['timeline'] ?? '';
$customFrom = $input['customFrom'] ?? '';
$customTo = $input['customTo'] ?? '';

$page = isset($input['page']) ? (int) $input['page'] : 1;
$limit = isset($input['limit']) ? (int) $input['limit'] : 100;

// --- Tab type (All, Missed, Pending, etc.) ---
$taskType = $input['taskType'] ?? 'all';

$taskObj = new Task($conn);


$tasks = $taskObj->getFilteredTasks($employees, $departments, $managers, $status, $timeline, $customFrom, $customTo, $allowedEmployees, $page, $limit, $taskType);
// $tasks = $taskObj->getFilteredTasks($employees, $departments, $managers, $status, $timeline, $customFrom, $customTo, $allowedEmployees, $page, $limit);
echo json_encode($tasks);


