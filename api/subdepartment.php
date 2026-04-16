<?php
require_once '../config/config.php';
require_once '../classes/SubDepartment.php';

header('Content-Type: application/json');

$subdeptObj = new SubDepartment($conn);
$response = ['status' => 'error', 'message' => 'Invalid action'];

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$data = (stripos($contentType, 'application/json') !== false)
    ? json_decode(file_get_contents('php://input'), true)
    : $_POST;

$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        $subdept = [
            'subdept_name' => $data['subdept_name'],
            'dept_id' => $data['dept_id']
        ];

        if ($subdeptObj->subdeptExists($subdept['subdept_name'], $subdept['dept_id'])) {
            $response = ['status' => 'error', 'message' => 'Subdepartment already exists in this department'];
            break;
        }

        if ($subdeptObj->addSubdepartment($subdept)) {
            $response = ['status' => 'success', 'message' => 'Subdepartment added successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to add subdepartment'];
        }
        break;

    case 'edit':
        $id = $data['id'];
        $subdept = [
            'subdept_name' => $data['subdept_name'],
            'dept_id' => $data['dept_id']
        ];

        if ($subdeptObj->updateSubdepartment($id, $subdept)) {
            $response = ['status' => 'success', 'message' => 'Subdepartment updated successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to update subdepartment'];
        }
        break;

    case 'delete':
        $id = $data['id'];
        if ($subdeptObj->deleteSubdepartment($id)) {
            $response = ['status' => 'success', 'message' => "Subdepartment with id: $id deleted"];
        } else {
            $response = ['status' => 'error', 'message' => "Failed to delete subdepartment"];
        }
        break;

    case 'get':
        $id = $data['id'];
        $subdept = $subdeptObj->getSubdepartmentById($id);
        if ($subdept) {
            $response = ['status' => 'success', 'subdept' => $subdept];
        } else {
            $response = ['status' => 'error', 'message' => 'Subdepartment not found'];
        }
        break;

    case 'getByDepartment':
        $id = $data['id'];
        $subdepts = $subdeptObj->getSubDepartmentsByDeptId($id);
        if ($subdepts) {
            $response = ['status' => 'success', 'subdepartments' => $subdepts];
        } else {
            $response = ['status' => 'error', 'message' => 'No subdepartments found'];
        }
        break;

    case 'list':
        $subdepts = $subdeptObj->getAllSubdepartments();
        $response = ['status' => 'success', 'subdepartments' => $subdepts];
        break;
}

echo json_encode($response);
