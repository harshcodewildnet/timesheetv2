<?php

require_once '../config/config.php';
require_once '../classes/Task.php';

session_start();

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$emp_ids = $input['emp_ids'] ?? null;

if (!$emp_ids || !is_array($emp_ids)) {
    echo json_encode(['error' => 'Unauthorized or invalid input']);
    exit;
}

$taskObj = new Task($conn);
$data = $taskObj->getAverageWeeklyHoursByEmployees($emp_ids);  // updated method

echo json_encode(value: $data);
