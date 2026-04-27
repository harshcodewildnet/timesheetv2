<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Task.php';
require_once '../classes/Project.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action not specified']);
    exit;
}

$taskObj = new Task($conn);
$emp_id  = $_SESSION['emp_id']   ?? null;
$role    = $_SESSION['emp_role'] ?? 'executive';

switch ($data['action']) {
    case 'add':
        $tasks = $data['tasks'] ?? [];

        if (empty($tasks)) {
            echo json_encode(['success' => false, 'message' => 'No tasks provided']);
            exit;
        }

        $allInserted = true;
        $projectObj  = new Project($conn);

        foreach ($tasks as $task) {
            $projectId = isset($task['projectId']) && $task['projectId'] !== '' ? (int)$task['projectId'] : null;
            $clientId  = (isset($task['clientId']) && $task['clientId'] !== '' && $task['clientId'] !== '-1') ? $task['clientId'] : null;

            // Auto-derive client_id from project if not sent directly
            if ($projectId && !$clientId) {
                $proj = $projectObj->getProjectById($projectId);
                if ($proj) $clientId = $proj['client_id'];
            }

            $result = $taskObj->addTask([
                'emp_id'          => $emp_id,
                'work_type'       => $task['workType'],
                'task_category'   => $task['taskCategoryId'],
                'task_subcategory'=> $task['taskBriefId'],
                'task_description'=> $task['taskDescription'] ?? null,
                'client_id'       => $clientId,
                'project_id'      => $projectId,
                'date'            => $task['date'],
                'time_taken'      => $task['timeTaken'],
                'status'          => 0,
                'user_role'       => $role
            ]);

            if (!$result['success']) {
                $allInserted = false;
                break;
            }
        }

        echo json_encode(['success' => $allInserted]);
        break;

    case 'edit':
        $task = $data['task'] ?? null;

        if (!$task || empty($task['task_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid task data']);
            exit;
        }

        $projectId = isset($task['projectId']) && $task['projectId'] !== '' ? (int)$task['projectId'] : null;
        $clientId  = (isset($task['clientId']) && $task['clientId'] !== '' && $task['clientId'] !== '-1') ? $task['clientId'] : null;

        // Auto-derive client_id from project
        if ($projectId && !$clientId) {
            $pObj = new Project($conn);
            $proj = $pObj->getProjectById($projectId);
            if ($proj) $clientId = $proj['client_id'];
        }

        $result = $taskObj->updateTask([
            'emp_id'          => $emp_id,
            'task_id'         => $task['task_id'],
            'work_type'       => $task['worktype'],
            'task_category'   => $task['cat_id'],
            'task_subcategory'=> $task['brief_id'],
            'task_description'=> $task['description'] ?? null,
            'client_id'       => $clientId,
            'project_id'      => $projectId,
            'date'            => $task['date'],
            'time_taken'      => $task['duration'],
            'status'          => 0,
            'user_role'       => $role
        ]);
        if (!$result['success']) {
            $_SESSION['success'] = false;
            $_SESSION['message'] = 'Task Update Failed!';
            echo json_encode(['success' => false]);
            break;
        }
        $_SESSION['success'] = true;
        $_SESSION['message'] = 'Task Updated Successfully!';
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $taskId = $data['task_id'] ?? null;
        $empId = $emp_id ?? null;
        if (!$taskId || !$empId) {
            echo json_encode(['success' => false, 'message' => 'Task ID is required']);
            exit;
        }

        $result = $taskObj->deleteTask($empId, $taskId, $role);
        $_SESSION['success'] = true;
        $_SESSION['message'] = 'Task Deleted Successfully!';
        echo json_encode(['success' => $result['success']]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
