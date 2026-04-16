<?php
require_once '../config/config.php';
require_once '../classes/Task.php';

$data = json_decode(file_get_contents('php://input'), true);
$taskId = $data['task_id'] ?? null;
$status = $data['status'] ?? null;
$comment = $data['comment'] ?? '';

if ($taskId && $status !== null) {
    $taskObj = new Task($conn);
    $result = $taskObj->updateTaskStatus($taskId, $status, $comment);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
