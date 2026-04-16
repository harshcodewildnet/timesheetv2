<?php
// Cron job script to generate missed timesheets report for previous day
// Should be run daily at 1 AM to check for missed entries from the previous day

// ─── CLI / Cron Compatibility ────────────────────────────────────────────────
// PHP CLI often has lower memory limits and different php.ini than the web SAPI.
// Override these here so the script behaves consistently in both environments.
ini_set('memory_limit', '512M');   // PhpSpreadsheet is memory-heavy
set_time_limit(0);                 // No timeout for cron jobs
error_reporting(E_ALL);            // Surface all errors to the log
ini_set('display_errors', 0);      // Don't output to STDOUT (breaks cron output)
ini_set('log_errors', 1);          // Send errors to PHP error log

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/Task.php';
require_once __DIR__ . '/mailer.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Log file for this cron job
$logFile = __DIR__ . '/../../logs/missed_timesheets_daily.log';

// Detect execution context for debugging
$runMode = (php_sapi_name() === 'cli') ? 'CLI/Cron' : 'Web (' . php_sapi_name() . ')';



// Helper function to log messages with better formatting
function logMessage($message, $logFile, $isHeader = false)
{
    $timestamp = date('Y-m-d H:i:s');
    $indent = $isHeader ? "" : "   ";
    $logEntry = "[{$timestamp}] {$indent}{$message}\n";

    // Add extra newline for headers in the log file
    if ($isHeader) {
        $headerLine = str_repeat("-", 60) . "\n";
        file_put_contents($logFile, "\n" . $headerLine . $logEntry . $headerLine, FILE_APPEND);
    } else {
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    // Console/Web Output
    if (PHP_SAPI === 'cli') {
        if ($isHeader)
            echo "\n" . str_repeat("=", 50) . "\n";
        echo ($isHeader ? ">> " : "   ") . $message . "\n";
        if ($isHeader)
            echo $isHeader ? str_repeat("=", 50) . "\n" : "";
    } else {
        if ($isHeader)
            echo "<br><strong>" . str_repeat("-", 20) . " " . htmlspecialchars($message) . " " . str_repeat("-", 20) . "</strong><br>";
        else
            echo "&nbsp;&nbsp;&nbsp; " . htmlspecialchars($message) . "<br>";
    }
}


// Helper function to clean strings for Excel
function cleanString($str)
{
    if (empty($str)) {
        return '';
    }
    // Remove null bytes and other problematic characters
    $str = str_replace("\0", '', $str);
    // Remove control characters except newline, carriage return, and tab
    $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
    // Ensure proper UTF-8 encoding
    if (!mb_check_encoding($str, 'UTF-8')) {
        $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    }
    return trim($str);
}

// Helper function to convert HH:MM to decimal hours
function timeToDecimal($time)
{
    $parts = explode(':', $time);
    if (count($parts) < 2)
        return floatval($time);
    return intval($parts[0]) + (intval($parts[1]) / 60);
}

// Helper function to generate email HTML
function generateDailyMissedEmailHtml($reportDate, $deptFilter, $missedCount, $missedEmployees, $submittedCount, $submittedTasks, $cumulativeData = [])
{
    $cumulativeCount = count($cumulativeData);
    global $logFile;

    // Ensure logs directory exists
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Generate missed employee table HTML
    $missedTableHtml = '';
    if ($missedCount > 0) {
        $missedTableHtml = '<table style="border-collapse: collapse; width: 100%; margin-top: 20px;">';
        $missedTableHtml .= '<thead><tr>';
        $missedTableHtml .= '<th style="background-color: #FF6B6B; color: white; padding: 12px; text-align: left;">Date</th>';
        $missedTableHtml .= '<th style="background-color: #FF6B6B; color: white; padding: 12px; text-align: left;">Emp ID</th>';
        $missedTableHtml .= '<th style="background-color: #FF6B6B; color: white; padding: 12px; text-align: left;">Name</th>';
        $missedTableHtml .= '<th style="background-color: #FF6B6B; color: white; padding: 12px; text-align: left;">Email</th>';
        $missedTableHtml .= '<th style="background-color: #FF6B6B; color: white; padding: 12px; text-align: left;">Department</th>';
        $missedTableHtml .= '<th style="background-color: #FF6B6B; color: white; padding: 12px; text-align: left;">Reporting Manager</th>';
        $missedTableHtml .= '</tr></thead><tbody>';

        foreach ($missedEmployees as $emp) {
            $missedTableHtml .= '<tr>';
            $missedTableHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($emp['date'] ?? '') . '</td>';
            $missedTableHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($emp['emp_id'] ?? '') . '</td>';
            $missedTableHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($emp['name'] ?? '') . '</td>';
            $missedTableHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($emp['email'] ?? '') . '</td>';
            $missedTableHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($emp['department'] ?? '') . '</td>';
            $missedTableHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($emp['rm_name'] ?? '') . '</td>';
            $missedTableHtml .= '</tr>';
        }

        $missedTableHtml .= '</tbody></table>';
    } else {
        $missedTableHtml = '<p style="color: #4CAF50; font-weight: bold;">✓ No missed cases for this date.</p>';
    }

    // Generate submitted tasks summary (just count by employee)
    $submittedSummaryHtml = '';
    if ($submittedCount > 0) {
        // Group by employee and calculate total hours
        $employeeHours = [];
        foreach ($submittedTasks as $task) {
            $empId = $task['emp_id'];
            if (!isset($employeeHours[$empId])) {
                $employeeHours[$empId] = [
                    'name' => $task['name'],
                    'email' => $task['email'],
                    'dept_name' => $task['dept_name'],
                    'rm_name' => $task['rm_name'],
                    'total_hours_decimal' => 0,
                    'task_count' => 0
                ];
            }
            $employeeHours[$empId]['total_hours_decimal'] += timeToDecimal($task['hours'] ?? '00:00');
            $employeeHours[$empId]['task_count']++;
        }

        $submittedSummaryHtml = '<table style="border-collapse: collapse; width: 100%; margin-top: 20px;">';
        $submittedSummaryHtml .= '<thead><tr>';
        $submittedSummaryHtml .= '<th style="background-color: #4CAF50; color: white; padding: 12px; text-align: left;">Emp ID</th>';
        $submittedSummaryHtml .= '<th style="background-color: #4CAF50; color: white; padding: 12px; text-align: left;">Name</th>';
        $submittedSummaryHtml .= '<th style="background-color: #4CAF50; color: white; padding: 12px; text-align: left;">Email</th>';
        $submittedSummaryHtml .= '<th style="background-color: #4CAF50; color: white; padding: 12px; text-align: left;">Department</th>';
        $submittedSummaryHtml .= '<th style="background-color: #4CAF50; color: white; padding: 12px; text-align: left;">Total Hours</th>';
        $submittedSummaryHtml .= '<th style="background-color: #4CAF50; color: white; padding: 12px; text-align: left;">Tasks</th>';
        $submittedSummaryHtml .= '</tr></thead><tbody>';

        foreach ($employeeHours as $empId => $data) {
            $submittedSummaryHtml .= '<tr>';
            $submittedSummaryHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($empId) . '</td>';
            $submittedSummaryHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($data['name']) . '</td>';
            $submittedSummaryHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($data['email']) . '</td>';
            $submittedSummaryHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . htmlspecialchars($data['dept_name']) . '</td>';
            $submittedSummaryHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . number_format($data['total_hours_decimal'], 2) . ' hrs</td>';
            $submittedSummaryHtml .= '<td style="padding: 10px; border-bottom: 1px solid #ddd;">' . $data['task_count'] . '</td>';
            $submittedSummaryHtml .= '</tr>';
        }

        $submittedSummaryHtml .= '</tbody></table>';
    } else {
        $submittedSummaryHtml = '<p style="color: #999;">No submitted cases for this date.</p>';
    }

    // Cumulative hours table for email body
    $cumulativeTableHtml = '';
    if ($cumulativeCount > 0) {
        $cumulativeTableHtml = '<div style="margin-top: 30px;">';
        $cumulativeTableHtml .= '<h3 style="color: #2196F3; border-bottom: 2px solid #2196F3; padding-bottom: 10px; margin-bottom: 15px;">Cumulative Hours by Client / Project</h3>';
        
        // Group data by employee
        $groupedCumulative = [];
        foreach ($cumulativeData as $row) {
            $empKey = $row['emp_id'] . '_' . $row['emp_name'];
            if (!isset($groupedCumulative[$empKey])) {
                $groupedCumulative[$empKey] = [
                    'id' => $row['emp_id'],
                    'name' => $row['emp_name'],
                    'projects' => []
                ];
            }
            $groupedCumulative[$empKey]['projects'][] = [
                'client_name' => $row['client_name'] ?? 'Others',
                'hours' => $row['total_hours_formatted'] ?? '00:00:00'
            ];
        }

        foreach ($groupedCumulative as $empData) {
            $cumulativeTableHtml .= '<div style="background: #f1f8ff; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #2196F3;">';
            $cumulativeTableHtml .= '<strong style="font-size: 1.1em; color: #1a73e8;">' . htmlspecialchars($empData['name']) . ' (ID: ' . htmlspecialchars($empData['id']) . ')</strong>';
            
            $cumulativeTableHtml .= '<table style="border-collapse: collapse; width: 100%; margin-top: 8px; background: white;">';
            $cumulativeTableHtml .= '<thead><tr style="background-color: #f8f9fa;">';
            $cumulativeTableHtml .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9em; width: 70%;">Project / Client</th>';
            $cumulativeTableHtml .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9em;">Total Hours</th>';
            $cumulativeTableHtml .= '</tr></thead><tbody>';

            foreach ($empData['projects'] as $proj) {
                $cumulativeTableHtml .= '<tr>';
                $cumulativeTableHtml .= '<td style="border: 1px solid #ddd; padding: 8px; font-size: 0.9em;">' . htmlspecialchars($proj['client_name']) . '</td>';
                $cumulativeTableHtml .= '<td style="border: 1px solid #ddd; padding: 8px; font-size: 0.9em; font-weight: bold;">' . htmlspecialchars($proj['hours']) . '</td>';
                $cumulativeTableHtml .= '</tr>';
            }
            $cumulativeTableHtml .= '</tbody></table></div>';
        }
        $cumulativeTableHtml .= '</div>';
    }

    // Load template
    $templatePath = realpath(__DIR__ . '/../../includes/email_templates/missed_daily_report.html');
    $template = false;

    if ($templatePath && file_exists($templatePath)) {
        $template = file_get_contents($templatePath);
    }

    if ($template === false || empty($template)) {
        // Fallback if template not found
        logMessage("WARNING: Using fallback HTML template.", $logFile);
        $template = '<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px;">Daily Timesheet Activity Report</h2>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <p><strong>Report Date:</strong> {{report_date}}</p>
                <p><strong>Department:</strong> {{department_filter}}</p>
                <p><strong>Missed Cases:</strong> <span style="color: #d63031; font-weight: bold;">{{missed_count}}</span></p>
                <p><strong>Submitted Cases:</strong> <span style="color: #27ae60; font-weight: bold;">{{submitted_count}}</span></p>
            </div>
            <h3>Missed Cases</h3>
            {{missed_table}}
            <h3 style="margin-top: 30px;">Submitted Cases</h3>
            {{submitted_summary}}
            {{cumulative_table}}
            <p style="margin-top: 40px; font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
                <em>Generated automatically on {{timestamp}}</em>
            </p>
        </body></html>';
    }

    // Replace placeholders
    $html = str_replace(
        ['{{report_date}}', '{{department_filter}}', '{{missed_count}}', '{{submitted_count}}', '{{missed_table}}', '{{submitted_summary}}', '{{cumulative_table}}', '{{timestamp}}'],
        [$reportDate, $deptFilter, $missedCount, $submittedCount, $missedTableHtml, $submittedSummaryHtml, $cumulativeTableHtml, date('Y-m-d H:i:s')],
        $template
    );

    return $html;
}


// Helper function to send email with attachment
function sendEmailWithAttachment($toEmail, $toName, $subject, $html, $attachmentPath, $attachmentName)
{
    global $logFile;

    // Validate email body
    if (empty($html) || strlen(trim($html)) < 10) {
        logMessage("ERROR: Email body is empty or too short for $toEmail", $logFile);
        throw new Exception("Email body is empty or invalid");
    }

    $mail = getMailer();
    $mail->addAddress($toEmail, $toName);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = strip_tags($html); // Plain text alternative

    // Add attachment
    if (file_exists($attachmentPath)) {
        $mail->addAttachment($attachmentPath, $attachmentName);
        logMessage("Attachment added: $attachmentPath", $logFile);
    } else {
        logMessage("WARNING: Attachment file not found: $attachmentPath", $logFile);
    }

    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }

    return true; // Success
}

// Helper function to log email to database
function logEmailToDb($empId, $emailType, $recipientEmail, $recipientName, $subject, $timesheetDate = null, $senderEmail = null, $status = 'sent')
{
    global $conn, $logFile;

    if ($senderEmail === null) {
        $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
    }

    try {
        $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssssss", $empId, $emailType, $senderEmail, $recipientEmail, $recipientName, $subject, $status, $timesheetDate);
        $stmt->execute();
        $stmt->close();
        logMessage("Email logged to database for $recipientEmail (status: $status)", $logFile);
    } catch (Exception $e) {
        logMessage("ERROR: Failed to log email to database: " . $e->getMessage(), $logFile);
    }
}

$task = new Task($conn);

logMessage("STARTING DAILY TIMESHEET REPORT GENERATION", $logFile, true);
logMessage("Run Mode    : $runMode  |  Memory Limit: " . ini_get('memory_limit'), $logFile);

// ─── Cron / CLI Defaults ─────────────────────────────────────────────────────
// When running as a cron job, $_GET is always empty.
// Define defaults here so the command needs no query-string arguments.
// These are overridden by explicit $_GET values when called via URL.
$cronDefaultDept = 5; // Department ID to report on (cron fallback)
$cronDefaultManager = 1946; // Manager ID to report on (cron fallback)
// ─────────────────────────────────────────────────────────────────────────────

// Allow specifying a custom date (format: YYYY-MM-DD) or default to yesterday
$customDate = $_GET['date'] ?? null;

if ($customDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $customDate)) {
    $targetDate = $customDate;
    logMessage("Target Date : $customDate (Custom)", $logFile);
} else {
    $targetDate = date('Y-m-d', strtotime('-1 day'));
    logMessage("Target Date : $targetDate (Auto-Yesterday)", $logFile);
}

// For daily report, start and end date are the same
$reportStart = $targetDate;
$reportEnd = $targetDate;

// Sanitize and validate input parameters (web requests override cron defaults)
$employees = isset($_GET['employees']) && is_array($_GET['employees']) ? array_map('intval', $_GET['employees']) : [];
$departments = isset($_GET['departments']) && is_array($_GET['departments']) ? array_map('intval', $_GET['departments']) : [];
$repManagers = isset($_GET['repManagers']) && is_array($_GET['repManagers']) ? array_map('intval', $_GET['repManagers']) : [];
$allowedEmployees = isset($_GET['allowedEmployees']) && is_array($_GET['allowedEmployees']) ? array_map('intval', $_GET['allowedEmployees']) : [];

// Handle department filter
$filterDesc = "None";
if (isset($_GET['dept']) && !empty($_GET['dept'])) {
    // Explicit web request — use the provided dept
    $deptId = intval($_GET['dept']);
    if ($deptId > 0) {
        $departments = [$deptId];
        $filterDesc = "Department ID: $deptId (Web)";
    }
} elseif (empty($departments)) {
    // No dept supplied at all — apply the cron default
    $departments = [$cronDefaultDept];
    $filterDesc = "Department ID: $cronDefaultDept (Cron Default)";
} else {
    $filterDesc = "Dept IDs: " . implode(', ', $departments);
}

// Handle manager filter
if (isset($_GET['manager']) && !empty($_GET['manager'])) {
    $managerId = intval($_GET['manager']);
    if ($managerId > 0) {
        $repManagers = [$managerId];
        $filterDesc .= " | Manager ID: $managerId (Web)";
    }
} elseif (empty($repManagers) && empty($departments)) {
    // If no specific dept/manager provided, apply both cron defaults
    $repManagers = [$cronDefaultManager];
    $filterDesc .= " | Manager ID: $cronDefaultManager (Cron Default)";
} elseif (!empty($repManagers)) {
    $filterDesc .= " | Manager IDs: " . implode(', ', $repManagers);
}

if (!empty($employees))
    $filterDesc = "Emp IDs: " . implode(', ', $employees);

logMessage("Filters     : $filterDesc", $logFile);

// Email recipient — falls back to TIMESHEET_FEEDBACK_EMAIL (defined in env.php)
$emailTo = $_GET['email_to'] ?? $_POST['email_to'] ?? TIMESHEET_FEEDBACK_EMAIL;
$sendEmail = !empty($emailTo);

// Parse multiple email recipients (comma-separated)
$emailRecipients = array_map('trim', explode(',', $emailTo));

logMessage("Running database queries...", $logFile);
$result = $task->getMissedTasksForDateRange($employees, $departments, $repManagers, $allowedEmployees, $reportStart, $reportEnd);


// Log the result details
if (isset($result['message'])) {
    logMessage("Query result: " . $result['message'], $logFile);
}
if (isset($result['error'])) {
    logMessage("Query error: " . $result['error'], $logFile);
}

// Check for errors
if (!isset($result['status']) || $result['status'] !== 'success') {
    $errorMsg = 'Error generating missed cases report: ' . ($result['message'] ?? 'Unknown error');
    logMessage("ERROR: " . $errorMsg, $logFile);
    error_log($errorMsg);
    die($errorMsg . "\n");
}

$data = $result['tasks'] ?? [];
logMessage("Found " . count($data) . " missed cases", $logFile);

// Get submitted tasks for the same date range
$submittedResult = $task->getSubmittedTasksForDateRange($employees, $departments, $repManagers, $allowedEmployees, $reportStart, $reportEnd);
$submittedData = $submittedResult['tasks'] ?? [];
logMessage("Found " . count($submittedData) . " submitted cases", $logFile);

// 10. Fetch cumulative data
$cumulativeResult = $task->getCumulativeHoursByClientEmployee($employees, $departments, $repManagers, $allowedEmployees);
if ($cumulativeResult['status'] === 'error') {
    logMessage("Error fetching cumulative hours: " . $cumulativeResult['message'], $logFile);
    $cumulativeData = [];
} else {
    $cumulativeData = $cumulativeResult['data'] ?? [];
}
logMessage("Found " . count($cumulativeData) . " cumulative rows (all-time)", $logFile);

// If no missed entries and no submitted entries, log and exit
if (count($data) === 0 && count($submittedData) === 0) {
    logMessage("No missed entries or submitted cases found for $reportStart", $logFile);
    echo "No missed timesheets or submitted tasks found for $reportStart\n";
    exit(0);
}

// If no missed entries but have submitted tasks, we still want to generate the report
if (count($data) === 0) {
    logMessage("No missed cases found for $reportStart, but continuing to generate activity report for " . count($submittedData) . " cases.", $logFile);
}


try {
    $spreadsheet = new Spreadsheet();

    // ===== SHEET 1: MISSED CASES =====
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Missed Cases');

    // Headers for missed entries
    $headers = ['Date', 'Employee ID', 'Employee Name', 'Email', 'Department', 'Reporting Manager'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Data rows for missed entries - sanitize data to prevent Excel corruption
    $rowIndex = 2;
    if (count($data) === 0) {
        $sheet->setCellValueExplicit("A{$rowIndex}", 'No Missed Timesheets', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->mergeCells("A{$rowIndex}:F{$rowIndex}");
        $sheet->getStyle("A{$rowIndex}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$rowIndex}")->getFont()->setItalic(true)->getColor()->setRGB('999999');
    } else {
        foreach ($data as $row) {
            $sheet->setCellValueExplicit("A{$rowIndex}", $row['date'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$rowIndex}", $row['emp_id'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowIndex}", cleanString($row['name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowIndex}", $row['email'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowIndex}", cleanString($row['department'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowIndex}", cleanString($row['rm_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $rowIndex++;
        }
    }


    // Style headers for missed sheet
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF6B6B');
    $sheet->getColumnDimension('C')->setWidth(25);
    $sheet->getColumnDimension('D')->setWidth(30);
    $sheet->getColumnDimension('E')->setWidth(20);
    $sheet->getColumnDimension('F')->setWidth(25);

    // ===== SHEET 2: SUBMITTED CASES =====
    $submittedSheet = $spreadsheet->createSheet();
    $submittedSheet->setTitle('Submitted Cases');

    // Headers for submitted tasks
    $submittedHeaders = ['Date', 'Emp ID', 'Name', 'Email', 'Department', 'Reporting Manager', 'Task Description', 'Hours', 'Task Category', 'Client / Project', 'Status'];
    $submittedSheet->fromArray($submittedHeaders, NULL, 'A1');

    // Data rows for submitted tasks
    $submittedRowIndex = 2;
    if (count($submittedData) === 0) {
        // Add "No Entries" message when there are no submitted tasks
        $submittedSheet->setCellValueExplicit("A{$submittedRowIndex}", 'No Entries', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $submittedSheet->mergeCells("A{$submittedRowIndex}:K{$submittedRowIndex}");
        $submittedSheet->getStyle("A{$submittedRowIndex}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $submittedSheet->getStyle("A{$submittedRowIndex}")->getFont()->setItalic(true)->getColor()->setRGB('999999');
    } else {
        foreach ($submittedData as $task) {
            $submittedSheet->setCellValueExplicit("A{$submittedRowIndex}", $task['date'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("B{$submittedRowIndex}", $task['emp_id'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("C{$submittedRowIndex}", cleanString($task['name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("D{$submittedRowIndex}", $task['email'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("E{$submittedRowIndex}", cleanString($task['dept_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("F{$submittedRowIndex}", cleanString($task['rm_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("G{$submittedRowIndex}", cleanString($task['task_description'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("H{$submittedRowIndex}", $task['hours'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("I{$submittedRowIndex}", cleanString($task['task_category'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("J{$submittedRowIndex}", cleanString($task['client_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedSheet->setCellValueExplicit("K{$submittedRowIndex}", $task['status'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $submittedRowIndex++;
        }
    }

    // Style headers for submitted sheet
    $submittedSheet->getStyle('A1:K1')->getFont()->setBold(true);
    $submittedSheet->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4CAF50');
    $submittedSheet->getColumnDimension('C')->setWidth(25);
    $submittedSheet->getColumnDimension('D')->setWidth(30);
    $submittedSheet->getColumnDimension('E')->setWidth(20);
    $submittedSheet->getColumnDimension('F')->setWidth(25);
    $submittedSheet->getColumnDimension('G')->setWidth(40);
    $submittedSheet->getColumnDimension('I')->setWidth(20);
    $submittedSheet->getColumnDimension('J')->setWidth(20);

    // ===== SHEET 3: PROJECT-WISE EMPLOYEE HOURS DISTRIBUTION =====
    $cumulativeSheet = $spreadsheet->createSheet();
    $cumulativeSheet->setTitle('Cumulative Hours');

    // ── Build a last-day lookup: [emp_id][client_id] => total seconds on report date ──
    // Keys are cast to string so they always match regardless of NULL / 0 / '' differences.
    $lastDayLookup = [];
    foreach ($submittedData as $st) {
        $eid = (string) ($st['emp_id'] ?? 0);
        $cid = (string) ($st['client_id'] ?? 0);   // NULL becomes '0'
        if (!$eid || $eid === '0')
            continue;
        $durationSec = 0;
        if (!empty($st['duration'])) {
            [$h, $m, $s] = array_map('intval', explode(':', $st['duration'] . ':0:0'));
            $durationSec = $h * 3600 + $m * 60 + $s;
        }
        if (!isset($lastDayLookup[$eid][$cid]))
            $lastDayLookup[$eid][$cid] = 0;
        $lastDayLookup[$eid][$cid] += $durationSec;
    }

    // Helper: seconds → "HH:MM Hrs"
    $fmtSec = function (int $sec): string {
        $h = floor($sec / 3600);
        $m = floor(($sec % 3600) / 60);
        return sprintf('%02d:%02d Hrs', $h, $m);
    };

    // ── Group cumulativeData by client_name ──
    $byProject = [];
    foreach ($cumulativeData as $row) {
        $proj = cleanString($row['client_name'] ?? 'Others');
        $byProject[$proj][] = $row;
    }
    // Sort projects alphabetically, then move 'Others' to the last
    ksort($byProject);
    if (isset($byProject['Others'])) {
        $others = $byProject['Others'];
        unset($byProject['Others']);
        $byProject['Others'] = $others;
    }

    // ── Column definitions ──
    $colHeaders = [
        'A' => 'Emp ID',
        'B' => 'Employee Name',
        'C' => 'Email',
        'D' => 'Department',
        'E' => 'Reporting Manager',
        'F' => 'Work Hours (Last Day)',
        'G' => 'Total Hours (Cumulative)',
    ];
    $lastCol = 'G';

    // ── PhpSpreadsheet style shortcuts ──
    $styleFill = fn($sheet, $range, $rgb) =>
        $sheet->getStyle($range)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB($rgb);

    $cumulativeRowIndex = 1;

    if (count($cumulativeData) === 0) {
        $cumulativeSheet->setCellValueExplicit("A1", 'No Data', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $cumulativeSheet->mergeCells("A1:{$lastCol}1");
        $cumulativeSheet->getStyle("A1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $cumulativeSheet->getStyle("A1")->getFont()->setItalic(true)->getColor()->setRGB('999999');
    } else {
        foreach ($byProject as $projectName => $employees) {

            // ── Project header row ──
            $cumulativeSheet->setCellValueExplicit("A{$cumulativeRowIndex}", $projectName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $cumulativeSheet->mergeCells("A{$cumulativeRowIndex}:{$lastCol}{$cumulativeRowIndex}");
            $cumulativeSheet->getStyle("A{$cumulativeRowIndex}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
            $cumulativeSheet->getStyle("A{$cumulativeRowIndex}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $styleFill($cumulativeSheet, "A{$cumulativeRowIndex}:{$lastCol}{$cumulativeRowIndex}", '1565C0');
            $cumulativeSheet->getRowDimension($cumulativeRowIndex)->setRowHeight(20);
            $cumulativeRowIndex++;

            // ── Column header row for this project ──
            foreach ($colHeaders as $col => $label) {
                $cumulativeSheet->setCellValueExplicit("{$col}{$cumulativeRowIndex}", $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $cumulativeSheet->getStyle("A{$cumulativeRowIndex}:{$lastCol}{$cumulativeRowIndex}")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $styleFill($cumulativeSheet, "A{$cumulativeRowIndex}:{$lastCol}{$cumulativeRowIndex}", '2196F3');
            $cumulativeRowIndex++;

            // ── Employee rows ──
            foreach ($employees as $row) {
                $empId = (string) ($row['emp_id'] ?? 0);
                $clientId = (string) ($row['client_id'] ?? 0);

                // Last-day hours for this employee+project
                // Both keys are strings to match how lastDayLookup was built.
                $lastDaySec = $lastDayLookup[$empId][$clientId]
                    ?? $lastDayLookup[$empId]['0']
                    ?? $lastDayLookup[$empId]['']
                    ?? 0;
                $lastDayFormatted = $fmtSec((int) $lastDaySec);

                // Cumulative hours already formatted
                $totalFormatted = ($row['total_hours_formatted'] ?? '00:00:00');
                // Reformat as "HH:MM Hrs"
                $parts = explode(':', $totalFormatted);
                $totalDisplay = sprintf('%02d:%02d Hrs', (int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0));

                $cumulativeSheet->setCellValueExplicit("A{$cumulativeRowIndex}", (string) $empId, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $cumulativeSheet->setCellValueExplicit("B{$cumulativeRowIndex}", cleanString($row['emp_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $cumulativeSheet->setCellValueExplicit("C{$cumulativeRowIndex}", $row['email'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $cumulativeSheet->setCellValueExplicit("D{$cumulativeRowIndex}", cleanString($row['dept_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $cumulativeSheet->setCellValueExplicit("E{$cumulativeRowIndex}", cleanString($row['rm_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $cumulativeSheet->setCellValueExplicit("F{$cumulativeRowIndex}", $lastDayFormatted, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $cumulativeSheet->setCellValueExplicit("G{$cumulativeRowIndex}", $totalDisplay, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                // Alternate row shading
                if ($cumulativeRowIndex % 2 === 0) {
                    $styleFill($cumulativeSheet, "A{$cumulativeRowIndex}:{$lastCol}{$cumulativeRowIndex}", 'EBF5FB');
                }

                $cumulativeRowIndex++;
            }

            // Blank spacer row between projects
            $cumulativeRowIndex++;
        }
    }

    // ── Column widths ──
    $cumulativeSheet->getColumnDimension('A')->setWidth(10);
    $cumulativeSheet->getColumnDimension('B')->setWidth(25);
    $cumulativeSheet->getColumnDimension('C')->setWidth(32);
    $cumulativeSheet->getColumnDimension('D')->setWidth(25);
    $cumulativeSheet->getColumnDimension('E')->setWidth(25);
    $cumulativeSheet->getColumnDimension('F')->setWidth(22);
    $cumulativeSheet->getColumnDimension('G')->setWidth(24);


    // Set active sheet back to first sheet
    $spreadsheet->setActiveSheetIndex(0);

    // Generate filename with date
    $deptSuffix = !empty($departments) ? '_dept_' . implode('_', $departments) : '';
    $filename = "daily_timesheet_report_{$reportStart}{$deptSuffix}.xlsx";

    // Determine department name for display
    $deptFilterDisplay = 'All Departments';
    if (!empty($departments)) {
        $deptQuery = $conn->prepare("SELECT GROUP_CONCAT(dept_name SEPARATOR ', ') as dept_names FROM department WHERE dept_id IN (" . implode(',', array_fill(0, count($departments), '?')) . ")");
        if ($deptQuery) {
            $deptQuery->bind_param(str_repeat('i', count($departments)), ...$departments);
            $deptQuery->execute();
            $deptResult = $deptQuery->get_result();
            if ($deptRow = $deptResult->fetch_assoc()) {
                $deptFilterDisplay = $deptRow['dept_names'] ?? 'Department ID: ' . implode(', ', $departments);
            }
        }
    }

    if ($sendEmail) {
        // Save Excel file to temporary location
        $tempFilePath = sys_get_temp_dir() . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);

        logMessage("Excel file created for email: $tempFilePath", $logFile);

        // Generate email HTML with missed, submitted, and cumulative data
        $emailHtml = generateDailyMissedEmailHtml($reportStart, $deptFilterDisplay, count($data), $data, count($submittedData), $submittedData, $cumulativeData);

        // Track successfully sent emails

        $successfulRecipients = [];

        // Send email to each recipient
        foreach ($emailRecipients as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                try {
                    sendEmailWithAttachment(
                        $recipient,
                        'Admin',
                        "Daily Timesheet Activity Report - {$reportStart}",
                        $emailHtml,
                        $tempFilePath,
                        $filename
                    );
                    logMessage("Email sent successfully to: $recipient", $logFile);
                    $successfulRecipients[] = $recipient;

                    // Log email to database once per recipient (admin-level report, emp_id=0)
                    logEmailToDb(
                        0,
                        'daily_report',
                        $recipient,
                        'Admin',
                        "Daily Timesheet Activity Report - {$reportStart} (" . count($data) . " employees)",
                        $reportStart,
                        null,
                        'sent'
                    );
                } catch (Exception $e) {
                    logMessage("ERROR: Failed to send email to $recipient - " . $e->getMessage(), $logFile);

                    // Log failed email attempt (admin-level report, emp_id=0)
                    logEmailToDb(
                        0,
                        'daily_report',
                        $recipient,
                        'Admin',
                        "Daily Timesheet Activity Report - {$reportStart} (" . count($data) . " employees)",
                        $reportStart,
                        null,
                        'failed'
                    );
                }
            } else {
                logMessage("WARNING: Invalid email address: $recipient", $logFile);
            }
        }

        // Clean up temp file
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }

        if (!empty($successfulRecipients)) {
            echo "Daily missed cases report has been sent to: " . implode(', ', $successfulRecipients) . "\n";
        } else {
            echo "Failed to send daily missed cases report. Please check the logs for details.\n";
        }
    } else {
        // Save to file in the current directory when no email specified
        $filePath = __DIR__ . '/' . $filename;
        logMessage("Generating Excel file: $filename", $logFile);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        logMessage("Report successfully saved to: $filePath", $logFile);
        logMessage("File size: " . filesize($filePath) . " bytes", $logFile);
        echo "Report saved to: $filePath\n";
    }

    // Clean up
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

} catch (Exception $e) {
    $errorMsg = 'Error generating Excel file: ' . $e->getMessage();
    logMessage("ERROR: " . $errorMsg, $logFile);
    error_log($errorMsg);
    die($errorMsg . "\n");
}

logMessage("EXECUTION SUMMARY", $logFile, true);
logMessage("Date Range  : $reportStart", $logFile);
logMessage("Missed      : " . count($data), $logFile);
logMessage("Submitted   : " . count($submittedData), $logFile);
logMessage("Cumulative  : " . count($cumulativeData) . " rows (all-time)", $logFile);
if ($sendEmail && !empty($successfulRecipients)) {
    logMessage("Recipients  : " . implode(', ', $successfulRecipients), $logFile);
}
logMessage("Status      : COMPLETED SUCCESSFULLY", $logFile);
exit;

