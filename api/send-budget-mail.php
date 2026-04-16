<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'cron/mailer.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $budget = $input['budget'] ?? null;
    $loggedUserId = $input['loggedUserId'] ?? null;
    $clientId = $input['clientId'] ?? null;
    $clientName = $input['clientName'] ?? null;

    if (!$budget || !$loggedUserId || !$clientId) {
        throw new Exception("Missing parameters");
    }

    // Fetch employee, RM, and HOD info
    $sql = "
        SELECT 
            e.name AS emp_name, e.email AS emp_email,
            rm.name AS rm_name, rm.email AS rm_email,
            hod.name AS hod_name, hod.email AS hod_email,
            e.dept_id
        FROM employee e
        LEFT JOIN employee rm ON rm.emp_id = e.rm_id
        LEFT JOIN department d ON d.dept_id = e.dept_id
        LEFT JOIN employee hod ON hod.emp_id = d.dept_head
        WHERE e.emp_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loggedUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $emp = $result->fetch_assoc();

    if (!$emp) {
        throw new Exception("Employee not found");
    }

    // Prepare email data
    $emp_name = $emp['emp_name'];
    $rm_name = $emp['rm_name'];
    $hod_name = $emp['hod_name'];
    $dept_id = $emp['dept_id'];

    // Include the email template
    include '../includes/email_templates/budget_change.php'; // $html is created inside

    $subject = "High Alert - Budget Update - $clientName ($emp_name)";
    $label = getClientLabel($dept_id);
    $subject = str_replace('Budget', "$label Budget", $subject);

    // Send mail
    $mail = getMailer();
    $recipients = [];

    if (!empty($emp['rm_email'])) {
        $mail->addAddress($emp['rm_email'], $rm_name);
        $recipients[] = ['email' => $emp['rm_email'], 'name' => $rm_name];
    }
    if (!empty($emp['hod_email'])) {
        $mail->addAddress($emp['hod_email'], $hod_name);
        $recipients[] = ['email' => $emp['hod_email'], 'name' => $hod_name];
    }

    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->isHTML(true);

    $emailStatus = 'sent';
    try {
        $mail->send();

        // Log email for each recipient
        foreach ($recipients as $recip) {
            $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())");
            $emailType = 'budget_change';
            $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
            $stmt->bind_param("issssss", $loggedUserId, $emailType, $senderEmail, $recip['email'], $recip['name'], $subject, $emailStatus);
            $stmt->execute();
            $stmt->close();
        }

    } catch (Exception $e) {
        $emailStatus = 'failed';

        // Log failed attempt
        foreach ($recipients as $recip) {
            $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())");
            $emailType = 'budget_change';
            $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
            $stmt->bind_param("issssss", $loggedUserId, $emailType, $senderEmail, $recip['email'], $recip['name'], $subject, $emailStatus);
            $stmt->execute();
            $stmt->close();
        }

        throw $e; // Re-throw to be caught by outer try-catch
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
