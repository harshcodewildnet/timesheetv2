<?php
session_start();
header('Content-Type: application/json');
require '../config/config.php';

if (!isset($_SESSION['verified_email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$password = trim($data['password'] ?? '');
$email = $_SESSION['verified_email'];

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

// $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$hashedPassword = $password;

try {
    $stmt = $conn->prepare("UPDATE employee SET password = ? WHERE email = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Statement Prepare Failed']);
        exit;
    }
    $stmt->bind_param('ss', $hashedPassword, $email);
    if ($stmt->execute()) {
        $update = $conn->prepare("
            UPDATE employee 
            SET password_changed_at = NOW(), password_reminder_sent = 0
            WHERE email = ?
        ");
        $update->bind_param("s", $email);
        $update->execute();

        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
        // Destroy session so OTP can't be reused
        session_destroy();
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error! Updating Password Failed!.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
