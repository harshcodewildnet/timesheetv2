<?php
require_once '../config/config.php';
require_once '../classes/Task.php';

$data = json_decode(file_get_contents('php://input'), true);
$tasks = $data['tasks'] ?? [];

if (empty($tasks)) {
    echo json_encode(['success' => false, 'message' => 'No tasks provided']);
    exit;
}

$taskObj = new Task($conn);
$result = $taskObj->bulkUpdateTaskStatus($tasks);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>
