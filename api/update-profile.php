<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Employee.php';

$empId = $_SESSION['emp_id'];

$data = json_decode(file_get_contents('php://input'), true);

$firstName = trim($data['first_name']);
$lastName = trim($data['last_name']);
// $email = trim($data['email']);
$contact = trim($data['contact']);
$password = trim($data['password']);
$fullName = $firstName . ' ' . $lastName;

$employee = [
    'name' => $fullName,
    'password' => $password,
    'contact' => $contact
];

$empObj = new Employee($conn);
$result = $empObj->updateEmployee($empId, $employee);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Profile Updated Successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update Profile !']);
}

