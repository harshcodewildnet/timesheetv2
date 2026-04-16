<?php
session_start();
require_once '../config/config.php';
require '../vendor/autoload.php';
require_once '../classes/Employee.php';
require_once '../classes/Client.php';
require_once '../includes/utilities.php';
$config = require '../config/email.php';
$allowedDomains = $config['allowed_domains'] ?? [];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$empObj = new Employee($conn);
$clientObj = new Client($conn);
$response = ['status' => 'error', 'message' => 'Invalid action'];

// Handle both FormData and JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), associative: true);
} else {
    $data = $_POST;
}

// sendAccountEmail moved to includes/utilities.php

// === Log Email to DB ===
function logEmail($empId, $type, $recipientEmail, $recipientName, $subject, $date = null, $senderEmail = null, $status = 'sent')
{
    global $conn;

    if ($senderEmail === null) {
        $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
    }

    try {
        $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("isssssss", $empId, $type, $senderEmail, $recipientEmail, $recipientName, $subject, $status, $date);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Log to file instead if DB logging fails
        error_log("Email log failed for emp_id $empId: " . $e->getMessage());
    }
}

// To Log add user mails to log file
// logMailStatus moved to includes/utilities.php

$action = $data['action'] ?? '';


try {

    // Switch based on action
    switch ($action) {

        // ---------------------------------------
        // ADD EMPLOYEE
        // ---------------------------------------

        case 'add':

            try {

                $role = $data['role'] ?? 'executive';
                $dept_id = $data['dept_id'];
                // Convert empty strings to null for integer fields
                $subdept_id = !empty($data['subdept_id']) ? $data['subdept_id'] : null;
                $emp_id = $data['emp_id'];
                $email = $data['email'];
                $clients = isset($data['clients'])
                    ? array_filter(array_map('trim', explode(',', $data['clients'])))
                    : [];

                // Validate required fields
                if (empty($emp_id) || empty($email) || empty($data['name']) || empty($dept_id)) {
                    $response = ['status' => 'error', 'message' => 'Employee ID, Name, Email, and Department are required'];
                    break;
                }

                // Validate email domain
                $domainCheck = isValidCompanyEmail($email, $allowedDomains);
                if ($domainCheck !== true) {
                    $response = ['status' => 'error', 'message' => $domainCheck];
                    break;
                }

                // Check duplicate employee ID
                $empIdCheck = $conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ?");
                $empIdCheck->bind_param('i', $emp_id);
                $empIdCheck->execute();
                $empIdCheck->store_result();
                if ($empIdCheck->num_rows > 0) {
                    $response = ['status' => 'error', 'message' => 'Employee ID already exists'];
                    break;
                }

                // Check duplicate email
                $emailCheck = $conn->prepare("SELECT emp_id FROM employee WHERE email = ?");
                $emailCheck->bind_param('s', $email);
                $emailCheck->execute();
                $emailCheck->store_result();
                if ($emailCheck->num_rows > 0) {
                    $response = ['status' => 'error', 'message' => 'Email already registered'];
                    break;
                }

                // Generate default password
                $randStr = generateRandomString(6);
                $firstName = explode(" ", trim($data['name']))[0];
                $randomPassword = $firstName . '@' . $randStr;

                $employeeData = [
                    'emp_id' => $emp_id,
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => $randomPassword,
                    'role' => $role,
                    'dept_id' => $dept_id,
                    'subdept_id' => $subdept_id,
                    'rm_id' => null,
                    'doj' => date('Y-m-d')
                ];

                // $employeeData['rm_id'] = $data['rm_id'] ?? null;
                // $employeeData['rm_id'] = $data['rm_id'] ?? 1;

                if ($role === 'executive' || $role === 'rm') {
                    $employeeData['rm_id'] = !empty($data['rm_id']) ? $data['rm_id'] : null;
                } elseif ($role === 'hod') {
                    // Only one HOD per dept
                    $hodCheck = $conn->prepare("SELECT emp_id FROM employee WHERE role = 'hod' AND status = 1 AND dept_id = ?");
                    $hodCheck->bind_param('i', $dept_id);
                    $hodCheck->execute();
                    $hodCheck->store_result();

                    if ($hodCheck->num_rows > 0) {
                        $response = ['status' => 'error', 'message' => 'An Active HOD already exists for this department'];
                        break;
                    }

                    $employeeData['rm_id'] = 1; // Admin
                }

                // INSERT employee

                // $result = $empObj->addEmployee($employeeData);
                // if (!$result['success']) {
                //     $response = ['status' => 'error', 'message' => $result['error']];
                //     break;
                // }

                try {
                    $empObj->addEmployee($employeeData);
                    $response = ['status' => 'success', 'message' => "Employee added successfully"];
                } catch (Exception $e) {
                    $response = ['status' => 'error', 'message' => "Failed to add employee: " . $e->getMessage()];
                    $_SESSION['success'] = false;
                    $_SESSION['message'] = "Failed to add employee: " . $e->getMessage();
                    break; // Stop execution if employee creation fails
                }

                // Assign clients if applicable
                if (!empty($clients)) {
                    $clientResult = $clientObj->addClientsToEmployeeId($clients, $emp_id);
                    if ($clientResult['status'] !== 'success') {
                        $response = ['status' => 'warning', 'message' => "Employee added but failed to assign some clients."];
                        $_SESSION['success'] = false;
                        $_SESSION['message'] = "Employee added but failed to assign some clients.";
                        break;
                    }
                }

                // If HOD, update dept head
                if ($role === 'hod') {
                    $updateDept = $conn->prepare("UPDATE department SET dept_head = ? WHERE dept_id = ?");
                    $updateDept->bind_param('ii', $emp_id, $dept_id);
                    $updateDept->execute();
                }

                // Send email notification (non-blocking - employee creation succeeds regardless)
                $emailStatus = false;
                try {
                    $emailStatus = sendAccountEmail($email, $employeeData['name'], $randomPassword);
                } catch (Exception $e) {
                    // Catch any unexpected exceptions from email function
                    error_log("Unexpected exception in sendAccountEmail: " . $e->getMessage());
                    $emailStatus = false;
                }

                // Log email attempt to database (non-blocking)
                try {
                    logEmail($emp_id, 'account_created', $email, $employeeData['name'], 'Account Details for Wildnet Timesheet Portal', null, null, $emailStatus ? 'sent' : 'failed');
                } catch (Exception $logEx) {
                    // Silently fail email logging to not disrupt the process
                    error_log("Email logging to database failed: " . $logEx->getMessage());
                }

                // Set response based on email status
                if ($emailStatus) {
                    $response = [
                        'status' => 'success',
                        'message' => "$role added successfully. Welcome email sent to $email"
                    ];
                    $_SESSION['success'] = true;
                    $_SESSION['message'] = "$role added successfully. Mail sent!";
                } else {
                    $response = [
                        'status' => 'warning',
                        'message' => "$role added successfully, but email sending failed. Please share credentials manually."
                    ];
                    $_SESSION['success'] = true;
                    $_SESSION['message'] = "$role added, but email sending failed.";
                }
                // $mailResult = sendAccountEmail($email, $employeeData['name'], $randomPassword);

                // if (!$mailResult) {
                //     $response = [
                //         'status' => 'warning',
                //         'message' => "$role added, but email sending failed.",
                //         'error' => $mailResult['error']
                //     ];
                // } else {
                //     $response = [
                //         'status' => 'success',
                //         'message' => "$role added successfully. Mail sent!"
                //     ];
                // }

                ///////////////////////////////////////////////
                // Send email
                // try {
                //     $mail = new PHPMailer(true);
                //     $mail->isSMTP();
                //     $mail->Host = 'smtp.gmail.com';
                //     $mail->SMTPAuth = true;
                //     $mail->Username = 'timesheet.owner@wildnettechnologies.com';
                //     $mail->Password = 'nhik tmto wspf iskz';
                //     $mail->SMTPSecure = 'tls';
                //     $mail->Port = 587;

                //     $mail->setFrom('timesheet.owner@wildnettechnologies.com', 'WildNet Timesheet Portal');
                //     $mail->addAddress($email, $employeeData['name']);

                //     $loginUrl = "https://timesheet.wildnettechnologies.com";

                //     $mail->isHTML(true);
                //     $mail->Subject = 'Account Details for Wildnet Timesheet Portal';

                //     $mail->Body = "
                //     <p><strong>Dear $name,</strong></p>
                //     <p>We are pleased to inform you that your account has been successfully created in the Wildnet Timesheet Portal. Please find your login details below.</p>
                //     <p>Your Login Credentials:</p>
                //     <ul>
                //         <li>Email Id: $email</li>
                //         <li>Password: $randomPassword</li>
                //         <li>Timesheet Link: <a href='$loginUrl'>$loginUrl</a></li>
                //     </ul>

                //     <p><strong>Important Instructions:</strong></p>
                //     <ul>
                //         <li>Use the above credentials to log in for the first time.</li>
                //         <li>Change your password after log in to keep your account secure.</li>
                //         <li>Keep your login credentials confidential and do not share them with anyone.</li>
                //     </ul>
                //     <p>If you encounter any issues during login or need assistance, please contact our support team at timesheet.owner@wildnettechnologies.com.
                //     We are excited to have you onboard and look forward to your contribution to Wildnet.</p>
                //     <b>Best Regards,</b><br>
                //     <b>HR Team</b> <br>
                //     <b>Wildnet Technologies Ltd.</b>
                // ";

                //     $mail->send();

                //     $response = [
                //         'status' => 'success',
                //         'message' => "$role added successfully. Mail sent!",
                //         'client_data' => $clients
                //     ];

                // } catch (Exception $e) {
                //     $response = [
                //         'status' => 'warning',
                //         'message' => "$role added, but email sending failed.",
                //         'error_detail' => $e->getMessage()
                //     ];
                // }

            } catch (Exception $e) {

                $response = [
                    'status' => 'error',
                    'message' => "Unexpected server error.",
                    'error_detail' => $e->getMessage()
                ];
                $_SESSION['success'] = false;
                $_SESSION['message'] = "Unexpected server error. " . $e->getMessage();

            }

            break;


        // ---------------------------------------
        // EDIT EMPLOYEE
        // ---------------------------------------
        // case 'edit':
        //     $id = $data['id'];
        //     $role = $data['role'] ?? 'executive';
        //     $dept_id = $data['dept_id'] ?? null;
        //     $subdept_id = $data['subdept_id'] ?? null;

        //     $employeeData = [];

        //     $possibleFields = ['name', 'email', 'password', 'role', 'dept_id', 'subdept_id'];

        //     foreach ($possibleFields as $field) {
        //         if (isset($data[$field]) && trim($data[$field]) !== '') {
        //             $employeeData[$field] = trim($data[$field]);
        //         }
        //     }

        //     // Force subdept_id to 0 if not sent or empty
        //     if (!isset($employeeData['subdept_id']) || $employeeData['subdept_id'] === '') {
        //         $employeeData['subdept_id'] = 0;
        //     }

        //     // Check for duplicate email (excluding current employee)
        //     if (isset($employeeData['email'])) {

        //         // Validate email domains
        //         $domainCheck = isValidCompanyEmail($employeeData['email'], $allowedDomains); // Add all allowed domains here
        //         if ($domainCheck !== true) {
        //             $response = ['status' => 'error', 'message' => $domainCheck];
        //             break;
        //         }

        //         $emailCheck = $conn->prepare("SELECT emp_id FROM employee WHERE email = ? AND emp_id != ?");
        //         $emailCheck->bind_param('si', $employeeData['email'], $id);
        //         $emailCheck->execute();
        //         $emailCheck->store_result();

        //         if ($emailCheck->num_rows > 0) {
        //             $response = ['status' => 'error', 'message' => 'Email is already registered to another employee'];
        //             break;
        //         }
        //     }

        //     // Role-based RM assignment logic
        //     if ($role === 'executive' || $role === 'rm') {
        //         $employeeData['rm_id'] = $data['rm_id'] ?? null;
        //     }
        //     // elseif ($role === 'rm') {
        //     //     // Assign HOD of dept as RM
        //     //     $hodQuery = $conn->prepare("SELECT emp_id FROM employee WHERE role = 'hod' AND dept_id = ? ORDER BY emp_id DESC");
        //     //     $hodQuery->bind_param('i', $dept_id);
        //     //     $hodQuery->execute();
        //     //     $hodResult = $hodQuery->get_result();
        //     //     if ($hodRow = $hodResult->fetch_assoc()) {
        //     //         $employeeData['rm_id'] = $hodRow['emp_id'];
        //     //     } else {
        //     //         $response = ['status' => 'error', 'message' => 'No HOD found for selected department. Please add a HOD first.'];
        //     //         break;
        //     //     }
        //     // }
        //     elseif ($role === 'hod') {
        //         // Check if another HOD exists for the department (excluding current user)
        //         $hodCheck = $conn->prepare("SELECT emp_id FROM employee WHERE role = 'hod' AND dept_id = ? AND emp_id != ?");
        //         $hodCheck->bind_param('ii', $dept_id, $id);
        //         $hodCheck->execute();
        //         $hodCheck->store_result();

        //         if ($hodCheck->num_rows > 0) {
        //             $response = ['status' => 'error', 'message' => 'Another HOD already exists for this department'];
        //             break;
        //         }

        //         $employeeData['rm_id'] = 1; // Admin is RM for HOD
        //     }

        //     $clients = isset($data['clients']) ? array_filter(array_map('trim', explode(',', $data['clients']))) : [];

        //     $role = ucfirst($role);
        //     if ($empObj->updateEmployee($id, $employeeData)) {

        //         $clientResult = $clientObj->addClientsToEmployeeId($clients, $id);
        //         if ($clientResult['status'] !== 'success') {
        //             $response = [
        //                 'status' => 'warning',
        //                 'message' => "$role updated, but client assignment failed."
        //             ];
        //             $_SESSION['success'] = false;
        //             $_SESSION['message'] = "$role updated, but client assignment failed.";
        //             break;
        //         }

        //         // Assign clients if any
        //         // if (!empty($clients)) {
        //         //         $response = ['status' => 'warning', 'message' => "$roleName added but failed to assign some clients."];
        //         //     $clientResult = $clientObj->addClientsToEmployeeId($clients, $id);
        //         //     if ($clientResult['status'] !== 'success') {
        //         //         $_SESSION['success'] = false;
        //         //         $_SESSION['message'] = "$roleName added but failed to assign some clients.";
        //         //         break;
        //         //     }
        //         // }

        //         // If updated employee is HOD, update dept_head in department table
        //         if (strtolower($role) === 'hod') {
        //             $updateDept = $conn->prepare("UPDATE department SET dept_head = ? WHERE dept_id = ?");
        //             $updateDept->bind_param('ii', $id, $dept_id);
        //             $updateDept->execute();
        //         }

        //         $response = ['status' => 'success', 'message' => "$role updated successfully"];
        //         $_SESSION['success'] = true;
        //         $_SESSION['message'] = "$role updated successfully";
        //     } else {
        //         $_SESSION['success'] = false;
        //         $_SESSION['message'] = "Failed to update $role";
        //         $response = ['status' => 'error', 'message' => "Failed to update $role"];
        //     }
        //     break;

        // Activate/Deactivate user
        // case 'toggle_status':
        //     $emp_id = $data['employee_id'] ?? null;
        //     $status = $data['status'] ?? null;

        //     if ($emp_id === null || $status === null) {
        //         $response = ['status' => 'error', 'message' => 'Invalid input'];
        //         break;
        //     }

        //     $stmt = $conn->prepare("UPDATE employee SET status = ? WHERE emp_id = ?");
        //     $stmt->bind_param("ii", $status, $emp_id);

        //     if ($stmt->execute()) {
        //         $response = ['status' => 'success'];
        //     } else {
        //         $response = ['status' => 'error', 'message' => 'Database error'];
        //     }
        //     break;

        // Activate/Deactivate user
        case 'toggle_status':
            $emp_id = $data['employee_id'] ?? null;
            $status = $data['status'] ?? null;

            if ($emp_id === null || $status === null) {
                $response = ['status' => 'error', 'message' => 'Invalid input'];
                break;
            }

            // Step 1: Fetch role and department of current employee
            $stmt = $conn->prepare("SELECT role, dept_id FROM employee WHERE emp_id = ?");
            $stmt->bind_param("i", $emp_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            if (!$result) {
                $response = ['status' => 'error', 'message' => 'Employee not found'];
                break;
            }

            $role = $result['role'];
            $dept_id = $result['dept_id'];

            // Step 2: If role is HOD and trying to activate (status=1)
            if ($role === 'hod' && $status == 1) {

                // Check if another active HOD already exists in this department
                $stmt2 = $conn->prepare("
            SELECT emp_id 
            FROM employee 
            WHERE role = 'hod' AND dept_id = ? AND status = 1 AND emp_id != ?
            LIMIT 1
        ");
                $stmt2->bind_param("ii", $dept_id, $emp_id);
                $stmt2->execute();
                $activeHOD = $stmt2->get_result()->fetch_assoc();

                // If another active HOD exists → block update
                if ($activeHOD) {
                    $response = [
                        'status' => 'error',
                        'message' => 'An active HOD already exists for this department'
                    ];
                    break;
                }
            }

            // Step 3: Safe to update
            $stmt = $conn->prepare("UPDATE employee SET status = ? WHERE emp_id = ?");
            $stmt->bind_param("ii", $status, $emp_id);

            if ($stmt->execute()) {
                $response = ['status' => 'success'];
            } else {
                $response = ['status' => 'error', 'message' => 'Database error'];
            }
            break;


        // Delete an employee
        case 'delete':
            $id = $data['id'];
            if ($empObj->deleteEmployee($id)) {
                $response = ['status' => 'success', 'message' => "Employee with id: $id deleted"];
            } else {
                $response = ['status' => 'error', 'message' => "Failed to delete employee with id: $id"];
            }
            break;

        // Get one employee
        case 'get':
            $id = $data['id'];
            $emp = $empObj->getEmployeeById($id);
            if ($emp) {
                $assignedClients = $clientObj->getClientsByEmployeeId($id) ?? null;
                $response = ['status' => 'success', 'emp' => $emp, 'assignedClients' => $assignedClients];
            } else {
                $response = ['status' => 'error', 'message' => 'Employee not found'];
            }
            break;

        ////////////////////////////

        // ---------------------------------------
        // EDIT EMPLOYEE
        // ---------------------------------------
        case 'edit':

            $id = $data["id"];
            $role = $data["role"];
            $dept_id = $data["dept_id"];
            $subdept_id = $data["subdept_id"] ?? 0;

            $employeeData = [];

            $editable = ["name", "email", "password", "role", "dept_id", "subdept_id", "rm_id"];
            foreach ($editable as $f) {
                if (!empty($data[$f])) {
                    $employeeData[$f] = trim($data[$f]);
                }
            }

            // Email duplicate check
            if (isset($employeeData["email"])) {
                $domainCheck = isValidCompanyEmail($employeeData["email"], $allowedDomains);
                if ($domainCheck !== true) {
                    $response = ['status' => 'error', 'message' => $domainCheck];
                    break;
                }

                $stmt = $conn->prepare("SELECT emp_id FROM employee WHERE email=? AND emp_id!=?");
                $stmt->bind_param("si", $employeeData["email"], $id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $response = ['status' => 'error', 'message' => 'Email is already registered'];
                    break;
                }
            }

            // HOD unique check
            if ($role === "hod") {
                $check = $conn->prepare("SELECT emp_id FROM employee WHERE role='hod' AND dept_id=? AND emp_id!=?");
                $check->bind_param("ii", $dept_id, $id);
                $check->execute();
                $check->store_result();
                if ($check->num_rows > 0) {
                    $response = ['status' => 'error', 'message' => 'Another HOD already exists for this department'];
                    break;
                }
                $employeeData["rm_id"] = 1;
            }

            // RM/HOD role RM mapping
            if ($role === "executive" || $role === "rm") {
                $employeeData["rm_id"] = $data["rm_id"] ?? null;
            }

            $update = $empObj->updateEmployee($id, $employeeData);

            if (!$update) {
                $response = ['status' => 'error', 'message' => "Failed to update $role"];
                break;
            }

            // Update department HOD
            if ($role === "hod") {
                $stmt = $conn->prepare("UPDATE department SET dept_head=? WHERE dept_id=?");
                $stmt->bind_param("ii", $id, $dept_id);
                $stmt->execute();
            }

            // Assign clients
            $clients = isset($data["clients"])
                ? array_filter(array_map("trim", explode(",", $data["clients"])))
                : [];

            $clientResult = $clientObj->addClientsToEmployeeId($clients, $id);

            if ($clientResult["status"] !== "success") {
                $response = [
                    'status' => 'warning',
                    'message' => "$role updated but client assignment failed"
                ];
            } else {
                $response = ['status' => 'success', 'message' => "$role updated successfully"];
            }

            break;


        ///////////////////////////
        // List all employees
        case 'list':
            $employees = $empObj->getAllEmployees();
            $employeeHTML = '';

            ob_start(); // Start output buffering

            if (!empty($executives)) {
                $i = 1;
                foreach ($executives as $executive) {
                    ?>
                    <tr>
                        <td><?= $i . "." ?></td>
                        <td><?= $executive['emp_id'] ?></td>
                        <td><?= $executive['name'] ?></td>
                        <td><?= $executive['email'] ?></td>
                        <td><?= $executive['dept_name'] ?></td>
                        <td><?= $executive['rm_name'] . " (" . $executive['rm_id'] . ")" ?></td>
                        <td class="actions">
                            <i class="fa-solid fa-eye open-modal-btn" data-modal="executive" data-mode="view"
                                data-id="<?= $executive['emp_id'] ?>"></i>
                            <i class="fa-solid fa-pencil open-modal-btn" data-modal="executive" data-mode="edit"
                                data-id="<?= $executive['emp_id'] ?>"></i>
                            <i class="fa-solid fa-trash-can delete-btn-emp" data-mode="delete" data-id="<?= $executive['emp_id'] ?>"></i>
                            <label class="switch">
                                <input type="checkbox" class="toggle-status" data-id="<?= $executive['emp_id'] ?>" <?= $executive['status'] ? 'checked' : '' ?>>
                                <span class="slider round"></span>
                            </label>
                        </td>
                    </tr>
                    <?php
                    $i++;
                }
                echo '<tr class="no-entries-row" style="background-color: #e0f5ff;display:none;">
            <td colspan="7" style="text-align: center;">
                <h3>No Entries</h3>
            </td>
        </tr>';
            } else {
                ?>
                <tr style="background-color: #ffe0e0ff;">
                    <td colspan="7" style="text-align: center;">
                        <h3>No Entries</h3>
                    </td>
                </tr>
                <?php
            }

            $html = ob_get_clean(); // Get buffered content

            echo json_encode([
                "success" => true,
                "data" => $html
            ]);
            exit;

        // // Get RMs by department
        // case 'rmsByDept':
        //     $dept_id = $data['dept_id'];
        //     $rms = $empObj->getRMsByDepartment($dept_id);
        //     $response = ['status' => 'success', 'rms' => array_values($rms)];
        //     break;

        // // Get RMs by Subdepartment
        // case 'rmsBySubdept':
        //     $subdept_id = $data['id'];
        //     $rms = $empObj->getRMsBySubDepartment($subdept_id);
        //     $response = ['status' => 'success', 'rms' => array_values($rms)];
        //     break;

        // Unified RM fetcher based on dept or subdept
        case 'get-rms':
            $dept_id = $data['dept_id'] ?? null;
            $subdept_id = $data['subdept_id'] ?? null;

            if ($subdept_id) {
                $rms = $empObj->getRMsBySubDepartment($subdept_id);
            } else {
                $rms = $empObj->getRMsByDepartment($dept_id);
            }

            if ($rms) {
                $response = ['status' => 'success', 'rms' => array_values($rms)];

            } else {
                $response = ['status' => 'error', 'message' => 'No Managers Found'];
            }
            // $response = ['status' => 'success', 'rms' => array_values($rms)];
            break;

        case 'update-employee-clients':

            $id = $data['id'];
            $clients = isset($data['clients']) ? array_filter(array_map('trim', explode(',', $data['clients']))) : [];
            // echo 'here';
            // print_r($clients);
            // exit;
            $clientResult = $clientObj->addClientsToEmployeeId($clients, $id);
            if ($clientResult['status'] !== 'success') {
                $response = [
                    'status' => 'error',
                    'message' => "client assignment failed for emp-id:$id : " . $clientResult['message'],
                    'client_data' => $clients
                ];
                $_SESSION['success'] = false;
                $_SESSION['message'] = "client assignment failed for emp-id:$id " . $clientResult['message'];
                break;
            } else {
                $response = [
                    'status' => 'success',
                    'message' => "clients updated for empid: $id " . $clientResult['message'],
                    'client_data' => $clients
                ];
                $_SESSION['success'] = true;
                $_SESSION['message'] = "clients updated for empid: $id ";
            }

            break;
    }

} catch (Exception $e) {

    // GLOBAL error catch
    $response = [
        'status' => 'error',
        'message' => 'Unexpected server error',
        'error' => $e->getMessage()
    ];
}


// Final JSON response
echo json_encode($response);
