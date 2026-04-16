<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Employee.php';

// Check if user is logged in
if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$employeeId = $_SESSION['emp_id'];
$uploadDir = '../assets/uploads/profile_photos/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxFileSize = 12 * 1024 * 1024; // 2MB

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profileImage'])) {
    $file = $_FILES['profileImage'];

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error.']);
        exit;
    }

    // Validate file type
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF files are allowed.']);
        exit;
    }

    // Validate file size
    if ($file['size'] > $maxFileSize) {
        echo json_encode(['success' => false, 'message' => "File size exceeds the 12MB limit."]);
        exit;
    }

    // Generate unique file name
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueFileName = 'profile_' . $employeeId . '_' . time() . '.' . $fileExt;
    $targetPath = $uploadDir . $uniqueFileName;

    // Ensure the uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Move uploaded file to server directory
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Save image path to the database
        $relativePath = 'assets/uploads/profile_photos/' . $uniqueFileName;

        $employee = new Employee($conn);
        if ($employee->updateProfilePhoto($employeeId, $relativePath)) {
            echo json_encode(['success' => true, 'imagePath' => $relativePath]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move the uploaded file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
