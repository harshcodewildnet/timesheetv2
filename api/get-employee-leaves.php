<?php

require_once '../config/config.php';
require_once '../classes/Task.php';

session_start();
header('Content-Type: application/json');

try {

    // Validate method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST allowed.");
    }

    // Parse JSON input
    $raw = file_get_contents('php://input');
    if (!$raw) {
        throw new Exception("No input received.");
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception("Invalid JSON input.");
    }

    // Validate input emp_ids
    $emp_ids = $data['emp_ids'] ?? null;

    if (!$emp_ids || !is_array($emp_ids)) {
        throw new Exception("Invalid or missing employee IDs.");
    }

    if (empty($emp_ids)) {
        throw new Exception("Employee ID list cannot be empty.");
    }

    // Validate that all IDs are numeric
    foreach ($emp_ids as $id) {
        if (!is_numeric($id)) {
            throw new Exception("Invalid employee ID: " . json_encode($id));
        }
    }

    $taskObj = new Task($conn);
    $result = $taskObj->getLeavesByEmployeeIds($emp_ids);

    echo json_encode([
        "status" => "success",
        "data" => $result
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
