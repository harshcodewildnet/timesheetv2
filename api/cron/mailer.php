<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// require_once  __DIR__ . '/../../vendor/autoload.php';
// require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/env.php';

// === Mail Config ===
function getMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0; // Disable verbose debug output
    $mail->isSMTP();
    $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = defined('SMTP_USER') ? SMTP_USER : 'timesheet.owner@wildnettechnologies.com';
    $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : 'dkqk ytsd wspw rejp';
    $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
    $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
    // $mail->smtpKeepAlive = true;

    // Recipients
    $mail->setFrom(defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com', 'WildNet Timesheet Portal');
    $mail->isHTML(true);
    return $mail;
}

// === Send Missing Timesheet Email To RM ===
function sendMissingTimesheetEmail($rmEmail, $rmName, $employees)
{
    $subject = "Missing Timesheet Alert";
    $body = generateEmployeeTableHtml($employees, 'Missing Timesheets', 'missing');

    $template = file_get_contents(__DIR__ . '/../../includes/email_templates/missing_timesheet.html');
    $message = str_replace(
        ['{{manager_name}}', '{{employee_table}}'],
        [$rmName, $body],
        $template
    );

    $status = sendEmail($rmEmail, $rmName, $subject, $message);

    foreach ($employees as $emp) {
        logEmail($emp['emp_id'], 'missing', $rmEmail, $rmName, $subject, date('Y-m-d', strtotime('-1 day')), null, $status);
    }
}

// === Send Missing Timesheet Email To Executive ===
function sendMissingTimesheetEmailToExecutive($emp)
{
    $subject = "Timesheet Not Submitted for " . $emp['task_date'];
    $body = generateEmployeeTableHtml([$emp], 'Your Missing Timesheet', 'missing');

    $template = file_get_contents(__DIR__ . '/../../includes/email_templates/missing_timesheet_individual.html'); // create this
    $message = str_replace(
        ['{{employee_name}}', '{{employee_table}}'],
        [$emp['emp_name'], $body],
        $template
    );

    $status = sendEmail($emp['emp_email'], $emp['emp_name'], $subject, $message);

    // Log email as well (same as RM email log)
    logEmail($emp['emp_id'], 'missing', $emp['emp_email'], $emp['emp_name'], $subject, $emp['task_date'], null, $status);
}


// === Send Pending Approval Email ===
function sendPendingApprovalEmail($rmEmail, $rmName, $employees)
{
    $subject = "Timesheet Pending Approval";
    $body = generateEmployeeTableHtml($employees, 'Pending Approvals', 'pending');

    $template = file_get_contents(__DIR__ . '/../../includes/email_templates/pending_approval.html');
    $message = str_replace(
        ['{{manager_name}}', '{{employee_table}}'],
        [$rmName, $body],
        $template
    );

    $status = sendEmail($rmEmail, $rmName, $subject, $message);

    foreach ($employees as $emp) {
        logEmail($emp['emp_id'], 'pending', $rmEmail, $rmName, $subject, date('Y-m-d', strtotime('-2 days')), null, $status);
    }
}

// === Send Escalation Email to HOD ===
function sendEscalationEmail($hodEmail, $hodName, $employees)
{
    $subject = "Timesheet Escalation Notice";
    $body = generateEmployeeTableHtml($employees, 'Unapproved After 48 Hours', 'escalation');

    $template = file_get_contents(__DIR__ . '/../../includes/email_templates/escalation.html');
    $message = str_replace(
        ['{{manager_name}}', '{{employee_table}}'],
        [$hodName, $body],
        $template
    );

    $status = sendEmail($hodEmail, $hodName, $subject, $message);

    foreach ($employees as $emp) {
        logEmail($emp['emp_id'], 'escalation', $hodEmail, $hodName, $subject, $emp['task_date'], null, $status);
    }
}


function sendLowHourEmail($rmEmail, $rmName, $employees)
{
    $subject = "Low Work Hour Alert";
    $body = generateEmployeeTableHtml($employees, 'Low Work Hour Violations', 'low_hours');

    $template = file_get_contents(__DIR__ . '/../../includes/email_templates/low_hours.html');
    $message = str_replace(
        ['{{manager_name}}', '{{employee_table}}'],
        [$rmName, $body],
        $template
    );

    $status = sendEmail($rmEmail, $rmName, $subject, $message);

    foreach ($employees as $emp) {
        $type = $emp['has_half_day'] ? 'half_day_violation' : 'working_violation';
        logEmail($emp['emp_id'], $type, $rmEmail, $rmName, $subject, $emp['task_date'], null, $status);
    }
}


// === Send Password Change Reminder Email ===
function sendPasswordChangeReminder($emp)
{
    $subject = "Reminder: Please Change Your Password";

    // Prepare message body
    $templatePath = __DIR__ . '/../../includes/email_templates/password_change_reminder.html';
    $template = file_get_contents($templatePath);

    $message = str_replace(
        ['{{employee_name}}', '{{created_date}}', '{{reset_link}}'],
        [
            htmlspecialchars($emp['name']),
            date('d M Y', strtotime($emp['created_at'])),
            // 'https://timesheet.widnettechnologies.com/reset-password'
            'https://localhost/timesheet/reset-password'
            // 'localhost/timesheet/forgot-password'
        ],  
        $template
    );

    // Send email
    $status = sendEmail($emp['email'], $emp['name'], $subject, $message);

    // Log email
    logEmail($emp['emp_id'], 'password_reset', $emp['email'], $emp['name'], $subject, null, null, $status);
}



// === Common Send ===
function sendEmail($toEmail, $toName, $subject, $html)
{
    $status = 'sent';
    try {
        $mail = getMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->send();
        $msg = "SUCCESS: Email sent to $toEmail for subject: $subject";
        echo $msg . "\n<br>";
        // logCronStatus($msg); // Optional: log successes too if needed
    } catch (Exception $e) {
        $status = 'failed';
        $errorMsg = "Mailer Error for $toEmail: {$mail->ErrorInfo}";
        echo $errorMsg . "\n";
        logCronStatus($errorMsg);
    } finally {
        sleep(2); // throttle requests
    }
    return $status;
}

function logCronStatus($message)
{
    // Log to c:\xampp\htdocs\timesheet\logs\cron_errors.log
    $logFile = __DIR__ . '/../../logs/cron_email_errors.log';
    $timestamp = date("Y-m-d H:i:s");
    $entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}

// === Generate HTML Table ===
// function generateEmployeeTableHtml($employees, $title = '')
// {
//     $rows = '';
//     foreach ($employees as $emp) {
//         $empId = $emp['emp_id'] ?? '-';
//         $name = $emp['emp_name'] ?? '-';
//         $dept = !empty($emp['dept']) ? $emp['dept'] : '-';
//         $subDept = !empty($emp['sub_dept']) ? $emp['sub_dept'] : '-';
//         $email = $emp['emp_email'] ?? '-';
//         $date = $emp['task_date'] ?? '-';
//         $hours = isset($emp['total_working_hours']) ? $emp['total_working_hours'] : '-';

//         $rows .= "<tr>
//             <td>{$empId}</td>
//             <td>{$name}</td>
//             <td>{$dept}</td>
//             <td>{$subDept}</td>
//             <td>{$email}</td>
//             <td>{$date}</td>
//             <td>{$hours}</td>
//         </tr>";
//     }

//     return "
//     <h3 style='margin-top:10px;'>$title</h3>
//     <p><em>Below table includes the relevant timesheet data.</em></p>
//     <table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; font-family:Arial;'>
//         <thead>
//             <tr style='background:#eee;'>
//                 <th>Emp ID</th>
//                 <th>Name</th>
//                 <th>Department</th>
//                 <th>Sub-Department</th>
//                 <th>Email</th>
//                 <th>Timesheet Date</th>
//                 <th>Working Hours</th>
//             </tr>
//         </thead>
//         <tbody style='text-align : center;'>$rows</tbody>
//     </table>";
// }


function generateEmployeeTableHtml($employees, $title = '', $type = 'default')
{
    $headers = [
        'default' => ['Emp ID', 'Name', 'Department', 'Sub-Department', 'Email', 'Timesheet Date'],
        'low_hours' => ['Emp ID', 'Name', 'Department', 'Sub-Department', 'Email', 'Timesheet Date', 'Working Hours'],
        'pending' => ['Emp ID', 'Name', 'Department', 'Sub-Department', 'Email', 'Timesheet Date'],
        'missing' => ['Emp ID', 'Name', 'Department', 'Sub-Department', 'Email', 'Timesheet Date'],
        'escalation' => ['Emp ID', 'Name', 'Department', 'Sub-Department', 'Email', 'Timesheet Date']
    ];

    $rows = '';
    foreach ($employees as $emp) {
        $cols = [];
        $cols[] = $emp['emp_id'] ?? '-';
        $cols[] = $emp['emp_name'] ?? '-';
        $cols[] = $emp['dept'] ?? '-';
        $cols[] = $emp['sub_dept'] ?? '-';
        $cols[] = $emp['emp_email'] ?? '-';
        $cols[] = $emp['task_date'] ?? '-';

        if ($type === 'low_hours') {
            $cols[] = isset($emp['total_working_hours']) ? $emp['total_working_hours'] : '-';
        }

        $rows .= "<tr><td>" . implode("</td><td>", $cols) . "</td></tr>";
    }

    $headerRow = "<tr style='background:#eee;'><th>" . implode("</th><th>", $headers[$type]) . "</th></tr>";

    return "
    <h3 style='margin-top:10px;'>$title</h3>
    <p><em>Below table includes the relevant timesheet data.</em></p>
    <table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; font-family:Arial; text-align:center;'>
        <thead>$headerRow</thead>
        <tbody>$rows</tbody>
    </table>";
}



// === Log Email to DB ===
function logEmail($empId, $type, $recipientEmail, $recipientName, $subject, $date = null, $senderEmail = null, $status = 'sent')
{
    global $conn;

    if ($senderEmail === null) {
        $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
    }

    $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssssss", $empId, $type, $senderEmail, $recipientEmail, $recipientName, $subject, $status, $date);
    $stmt->execute();
    $stmt->close();
}

