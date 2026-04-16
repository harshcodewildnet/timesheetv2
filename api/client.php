<?php
session_start();
require_once '../config/config.php';
require_once '../classes/Client.php';
// require_once '../classes/Employee.php';

header('Content-Type: application/json');

$clientObj = new Client($conn);
// $empObj = new Employee($conn);

$response = ['status' => 'error', 'message' => 'Invalid action'];

// Handle both FormData and JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$action = $data['action'] ?? '';

// Switch based on action
switch ($action) {
    // Add a new client
    case 'add':

        $client_id = $data['client_id'];
        $client_name = $data['client_name'];
        $email = isset($data['email']) ? trim($data['email']) : '';
        // $client_no = $data['contact'];

        // Validate email domains
        // $domainCheck = isValidCompanyEmail($email, $allowedDomains); // Add all allowed domains here
        // if ($domainCheck !== true) {
        //     $response = ['status' => 'error', 'message' => $domainCheck];
        //     break;
        // }

        // Check if client_id already exists
        $clientIdCheck = $conn->prepare("SELECT client_id FROM client WHERE client_id = ?");
        $clientIdCheck->bind_param('s', $client_id);
        $clientIdCheck->execute();
        $clientIdCheck->store_result();

        if ($clientIdCheck->num_rows > 0) {
            $response = ['status' => 'error', 'message' => 'Client / Project ID already exists'];
            break;
        }

        // Check if email already exists (only when provided)
        if ($email !== '') {
            $emailCheck = $conn->prepare("SELECT client_id FROM client WHERE email = ?");
            $emailCheck->bind_param('s', $email);
            $emailCheck->execute();
            $emailCheck->store_result();

            if ($emailCheck->num_rows > 0) {
                $response = ['status' => 'error', 'message' => 'Email is already registered'];
                break;
            }
        }


        $clientData = [
            'client_id' => $client_id,
            'client_name' => $client_name,
            'email' => $email !== '' ? $email : null,
            // 'contact' => $client_no ?? null,
            'status' => 1   // active by default
        ];

        if ($clientObj->addNewClient($clientData)) {
            $response = ['status' => 'success', 'message' => "New Client / Project Added Successfully !"];
            $_SESSION['success'] = true;
            $_SESSION['message'] = "New Client / Project Added Successfully !";
        } else {
            $response = ['status' => 'error', 'message' => "Failed to add Client"];
            $_SESSION['success'] = false;
            $_SESSION['message'] = "Failed to add new client";
        }
        break;

    // Edit a client
    case 'edit':
        $client_id = $data['id'];      // current (old) client_id used as WHERE key
        $new_client_id = isset($data['client_id']) ? trim($data['client_id']) : '';

        $clientData = [];

        $possibleFields = ['client_name', 'email'];

        foreach ($possibleFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $clientData[$field] = $data[$field];
            }
        }

        // Validate & check for duplicate new client_id (if it changed)
        if ($new_client_id !== '' && $new_client_id !== $client_id) {
            if (!preg_match('/^[A-Za-z0-9_\-]+$/', $new_client_id)) {
                $response = ['status' => 'error', 'message' => 'Invalid Client / Project ID (only letters, numbers, hyphens & underscores allowed)'];
                break;
            }
            $idCheck = $conn->prepare("SELECT client_id FROM client WHERE client_id = ?");
            $idCheck->bind_param('s', $new_client_id);
            $idCheck->execute();
            $idCheck->store_result();
            if ($idCheck->num_rows > 0) {
                $response = ['status' => 'error', 'message' => 'Client / Project ID already exists'];
                break;
            }
            $clientData['client_id'] = $new_client_id;
        }

        // Check for duplicate email (excluding current client)
        if (isset($clientData['email'])) {
            $emailCheck = $conn->prepare("SELECT client_id FROM client WHERE email = ? AND client_id != ?");
            $emailCheck->bind_param('ss', $clientData['email'], $client_id);
            $emailCheck->execute();
            $emailCheck->store_result();

            if ($emailCheck->num_rows > 0) {
                $response = ['status' => 'error', 'message' => 'Email is already registered to another Client / Project'];
                break;
            }
        }

        if ($clientObj->updateClient($client_id, $clientData)) {
            $response = ['status' => 'success', 'message' => "Client / Project updated successfully"];
            $_SESSION['success'] = true;
            $_SESSION['message'] = "Client / Project updated successfully";
        } else {
            $_SESSION['success'] = false;
            $_SESSION['message'] = "Failed to update Client";
            $response = ['status' => 'error', 'message' => "Failed to update Client"];
        }
        break;

    // Get one employee
    case 'get':
        $id = $data['id'];
        $client = $clientObj->getClientById($id);
        if ($client) {
            $response = ['status' => 'success', 'client' => $client];
        } else {
            $response = ['status' => 'error', 'message' => 'Client / Project not found'];
        }
        break;

    // Activate/Deactivate user
    case 'toggle_status':
        $client_id = $data['client_id'] ?? null;
        $status = $data['status'] ?? null;

        if ($client_id === null || $status === null) {
            $response = ['status' => 'error', 'message' => 'Invalid input'];
            break;
        }

        $stmt = $conn->prepare("UPDATE client SET is_active = ? WHERE client_id = ?");
        $stmt->bind_param("is", $status, $client_id);

        if ($stmt->execute()) {
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'error', 'message' => 'Database error'];
        }
        break;

    // Delete an client
    case 'delete':
        $id = $data['id'];
        $result = $clientObj->deleteClient($id);
        if ($result['status'] == 'success') {
            $response = ['status' => 'success', 'message' => "Client / Project with id: $id deleted"];
        } else {
            $response = ['status' => 'error', 'message' => "Failed to delete Client / Project with id: $id, msg: " . $result['message'] . ""];
        }
        break;
}

echo json_encode($response);
