<?php

require_once '../config/config.php';
require_once '../classes/Task.php';

header('Content-Type: application/json');

$empIds = isset($_GET['emp_ids']) ? explode(',', $_GET['emp_ids']) : [];

if (empty($empIds)) {
    echo json_encode(['error' => 'No employee IDs provided']);
    exit;
}

$taskObj = new Task($conn);
$data = $taskObj->getWorkingDaysByEmployees($empIds);

echo json_encode($data);


// require_once '../config/config.php';
// require_once '../classes/Task.php';

// session_start();
// $emp_id = $_GET['emp_id'] ?? null;

// if (!$emp_id) {
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

// $taskObj = new Task($conn);
// $data = $taskObj->getWorkingDaysByEmployee($emp_id);

// echo json_encode($data);