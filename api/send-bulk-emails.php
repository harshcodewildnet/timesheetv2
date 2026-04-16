<?php
// send-bulk-emails.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // path to PHPMailer autoload
include '../config/config.php'; // your DB connection ($conn)
require_once '../config/env.php'; // Load env constants
$config = require '../config/email.php';
// Allowed company domains (same as used in employee.php)
// $allowedDomains = ['wildnetedge.com', 'wildnettechnologies.com', 'wildnet.global'];
$allowedDomains = $config['allowed_domains'] ?? [];


// Function to check valid domain
function isValidCompanyEmail($email, $allowedDomains)
{
    $domain = substr(strrchr($email, "@"), 1);
    return in_array($domain, $allowedDomains) ? true : $domain;
}

// Simple file logger
function logMailStatus($message)
{
    $logFile = __DIR__ . "/bulk_mail_log.txt";
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

$query = "SELECT emp_id, name, email, password, role FROM employee where role in ('rm', 'executive') and emp_id IN ()";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($employeeData = $result->fetch_assoc()) {
        $empId = $employeeData['emp_id'];
        $name = $employeeData['name'];
        $email = $employeeData['email'];
        $password = $employeeData['password'];
        $role = $employeeData['role'];

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = defined('SMTP_USER') ? SMTP_USER : 'timesheet.owner@wildnettechnologies.com';
            $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : 'dkqk ytsd wspw rejp';
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail->smtpKeepAlive = true;

            // Recipients
            $mail->setFrom(defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com', 'WildNet Timesheet Portal');

            $domainCheck = isValidCompanyEmail($email, $allowedDomains);
            if ($domainCheck !== true) {
                $msg = "Skipped: $name <$email> - Blocked domain ($domainCheck)";
                echo $msg . "<br>";
                logMailStatus($msg);
                continue;
            }

            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Account Details for Wildnet Timesheet Portal';
            $loginUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : "https://timesheet.wildnettechnologies.com";

            $mail->Body = "
                <p><strong>Dear $name,</strong></p>
                <p>We are pleased to inform you that your account has been successfully created in the Wildnet Timesheet Portal. Please find your login details below.</p>
                <p>Your Login Credentials:</p>
                <ul>
                    <li>Email Id: $email</li>
                    <li>Password: $password</li>
                    <li>Timesheet Link: <a href='$loginUrl'>$loginUrl</a></li>
                </ul>
                                        
                <p><strong>Important Instructions:</strong></p>
                <ul>
                    <li>Use the above credentials to log in for the first time.</li>
                    <li>Change your password after log in to keep your account secure.</li>
                    <li>Keep your login credentials confidential and do not share them with anyone.</li>
                </ul>
                <p>If you encounter any issues during login or need assistance, please contact our support team at " . (defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com') . ".
                We are excited to have you onboard and look forward to your contribution to Wildnet.</p>
                <b>Best Regards,</b><br>
                <b>HR Team</b> <br>
                <b>Wildnet Technologies Ltd.</b>
            ";

            $mail->AltBody = "Dear $name,\n\nYour account has been successfully created.\n\nLogin Details:\nEmail: $email\nPassword: $password\n\nLogin here: $loginUrl\n\nPlease change your password after your first login.\n\nRegards,\nHR Team Wildnet";

            $subject = 'Account Details for Wildnet Timesheet Portal';
            $emailStatus = 'sent';
            
            if ($mail->send()) {
                $msg = "SUCCESS: Mail sent to $name <$email>";
                echo $msg . "<br>";
                logMailStatus($msg);
            } else {
                $emailStatus = 'failed';
                $msg = "FAIL: Could not send mail to $name <$email>";
                echo $msg . "<br>";
                logMailStatus($msg);
            }
            
            // Log email to database
            $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())");
            $emailType = 'account_created';
            $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
            $stmt->bind_param("issssss", $empId, $emailType, $senderEmail, $email, $name, $subject, $emailStatus);
            $stmt->execute();
            $stmt->close();
            
        } catch (Exception $e) {
            $msg = "ERROR: $name <$email> - " . $mail->ErrorInfo;
            echo $msg . "<br>";
            logMailStatus($msg);
            
            // Log failed email attempt
            $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, NOW())");
            $emailType = 'account_created';
            $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
            $subject = 'Account Details for Wildnet Timesheet Portal';
            $emailStatus = 'failed';
            $stmt->bind_param("issssss", $empId, $emailType, $senderEmail, $email, $name, $subject, $emailStatus);
            $stmt->execute();
            $stmt->close();
        }
        sleep(2); // throttle requests
    }
} else {
    echo "No employees found!";
    logMailStatus("No employees found in DB.");
}
