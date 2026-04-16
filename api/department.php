<?php

require_once '../config/config.php';
require_once '../classes/Department.php';

header('Content-Type: application/json');

$deptObj = new Department($conn);
$response = ['status' => 'error', 'message' => 'Invalid action'];

// Detect input type: JSON or FormData
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$data = (stripos($contentType, 'application/json') !== false)
    ? json_decode(file_get_contents('php://input'), true)
    : $_POST;

$action = $data['action'] ?? '';

switch ($action) {

    case 'add':
        $dept_email = "contact.dept@wildnet.com";
        $departmentData = [
            'dept_name' => trim($data['dept_name']),
            'dept_email' => $dept_email
        ];
        $result = $deptObj->addDepartment($departmentData);

        if ($result === 'success') {
            $response = ['status' => 'success', 'message' => 'Department Added Successfully'];
        } elseif ($result === 'exists') {
            $response = ['status' => 'warning', 'message' => 'Duplicate department entry'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to add department'];
        }
        break;

    case 'edit':
        $id = $data['id'];
        $departmentData = [
            'dept_name' => trim($data['dept_name']),
            // 'hod_id' => $data['hod_id'] ?? null // if needed
        ];
        if ($deptObj->updateDepartment($id, $departmentData)) {
            $response = ['status' => 'success', 'message' => 'Department Updated Successfully'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to update department'];
        }
        break;

    case 'delete':
        $id = $data['id'];
        if ($deptObj->deleteDepartment($id)) {
            $response = ['status' => 'success', 'message' => "Department with id: $id Deleted"];
        } else {
            $response = ['status' => 'error', 'message' => "Failed to delete Department with id: $id"];
        }
        break;

    case 'get':
        $id = $data['id'];
        $dept = $deptObj->getDepartmentById($id);
        if ($dept) {
            $response = ['status' => 'success', 'dept' => $dept];
        } else {
            $response = ['status' => 'error', 'message' => 'Department not found'];
        }
        break;

    case 'list':
        $departments = $deptObj->getAllDepartments();
        $response = ['status' => 'success', 'departments' => $departments];
        break;
}

echo json_encode($response);
