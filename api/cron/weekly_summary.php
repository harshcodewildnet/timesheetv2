<?php
/**
 * Weekly Summary Report Script
 * 
 * Generates an Excel report with multiple sheets:
 * 1. Weekly Dashboard
 * 2. Resource Utilization
 * 3. Missed Timesheets
 * 4. Submitted Tasks
 * 5. Project Matrix
 * 
 * Scheduled to run every Monday at 3 AM to report on the previous week.
 */

ini_set('memory_limit', '1024M'); // Spreadsheet generation can be memory-heavy
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/Task.php';
require_once __DIR__ . '/mailer.php';

// Support CLI arguments (similar to missed_daily.php)
// Moved lower after $logFile and logMsg are defined

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Set timezone
date_default_timezone_set('Asia/Kolkata');

$logFile = __DIR__ . '/../../logs/weekly_summary.log';
$task = new Task($conn);

function logMsg($message, $logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    if (PHP_SAPI === 'cli') {
        echo $logEntry;
    } else {
        echo "{$logEntry}<br>";
        ob_flush();
        flush();
    }
}

function logEmailToDb($conn, $logFile, $empId, $emailType, $recipientEmail, $recipientName, $subject, $timesheetDate = null, $senderEmail = null, $status = 'sent')
{
    if ($senderEmail === null) {
        $senderEmail = defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com';
    }

    try {
        $stmt = $conn->prepare("INSERT INTO email_logs (emp_id, email_type, sender_email, recipient_email, recipient_name, subject, status, timesheet_date, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssssss", $empId, $emailType, $senderEmail, $recipientEmail, $recipientName, $subject, $status, $timesheetDate);
        $stmt->execute();
        $stmt->close();
        logMsg("Email logged to database for $recipientEmail (status: $status)", $logFile);
    } catch (Exception $e) {
        logMsg("ERROR: Failed to log email to database: " . $e->getMessage(), $logFile);
    }
}

if (PHP_SAPI !== 'cli') {
    echo "<html><body style='background:#f4f4f4;font-family:monospace;padding:20px;'><pre>";
}

logMsg("STARTING WEEKLY SUMMARY REPORT GENERATION", $logFile);

function generateWeeklySummary($conn, $task, $logFile, $reportStart, $reportEnd, $departments = [], $repManagers = [], $emailRecipients = []) {
    logMsg("--------------------------------------------------", $logFile);
    logMsg("GENERATING REPORT: " . (!empty($departments) ? "Dept " . implode(',', $departments) : "Global"), $logFile);
    // 2. Fetch Data
    logMsg("Fetching data...", $logFile);

    // Missed Timesheets
    $missedResult = $task->getMissedTasksForDateRange([], $departments, $repManagers, [], $reportStart, $reportEnd);
    $missedData = $missedResult['tasks'] ?? [];

    // Submitted Tasks
    $submittedResult = $task->getSubmittedTasksForDateRange([], $departments, $repManagers, [], $reportStart, $reportEnd);
    $submittedData = $submittedResult['tasks'] ?? [];

    // Project Matrix / Utilization
    $matrixResult = $task->getResourceProjectMatrix([], $departments, $repManagers, [], $reportStart, $reportEnd);
    $matrixData = $matrixResult['data'] ?? [];

    // Daily Hours (for Dashboard summary)
    $dailyHoursResult = $task->getDailyHours([], $departments, $repManagers, [], $reportStart, $reportEnd);
    $dailyHoursData = $dailyHoursResult['data'] ?? [];

    // 3. Process Summary Stats
    $totalHours = 0;
    $uniqueEmps = [];
    $empTotalHours = [];

    foreach ($dailyHoursData as $row) {
        $totalHours += $row['total_hours'];
        $uniqueEmps[$row['emp_id']] = $row['emp_name'];
        if (!isset($empTotalHours[$row['emp_id']])) {
            $empTotalHours[$row['emp_id']] = ['name' => $row['emp_name'], 'total' => 0];
        }
        $empTotalHours[$row['emp_id']]['total'] += $row['total_hours'];
    }

    foreach ($missedData as $row) {
        if (!isset($empTotalHours[$row['emp_id']])) {
            $empTotalHours[$row['emp_id']] = ['name' => $row['name'], 'total' => 0];
        }
    }

    $numDays = 7;
    $numEmps = count($uniqueEmps);
    $avgHoursPerDay = ($numEmps > 0) ? ($totalHours / ($numEmps * $numDays)) : 0;

    // Skip if completely empty (no missed, no submitted)
    if (empty($missedData) && empty($submittedData)) {
        logMsg("No data for this filter. Skipping email.", $logFile);
        return false;
    }

    // 4. Generate Excel
    logMsg("Building Excel...", $logFile);
    $spreadsheet = new Spreadsheet();
    
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    // --- SHEET 1: Weekly Dashboard ---
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Weekly Dashboard');
    $sheet->setCellValue('A1', 'Weekly Timesheet Dashboard')->mergeCells('A1:C1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->setCellValue('A3', 'Report Period:')->setCellValue('B3', "$reportStart to $reportEnd");
    
    $stats = [
        ['Metric', 'Value', 'Unit'],
        ['Total Hours Logged', round($totalHours, 2), 'Hours'],
        ['Total Active Resources', $numEmps, 'Employees'],
        ['Avg Daily Hours (Per Resource)', round($avgHoursPerDay, 2), 'Hours'],
        ['Total Missed Cases (Instances)', count($missedData), 'Entries'],
    ];
    $sheet->fromArray($stats, NULL, 'A5');
    $sheet->getStyle('A5:C5')->applyFromArray($headerStyle);
    $sheet->getColumnDimension('A')->setWidth(30);

    // --- SHEET 2: Resource Utilization ---
    $utilSheet = $spreadsheet->createSheet();
    $utilSheet->setTitle('Resource Utilization');
    $utilHeaders = ['Employee ID', 'Employee Name', 'Hours Logged', 'Weekly Capacity', 'Utilization %', 'Status'];
    $utilSheet->fromArray($utilHeaders, NULL, 'A1');
    $utilSheet->getStyle('A1:F1')->applyFromArray($headerStyle);
    
    $uRow = 2;
    $capacity = 40;
    ksort($empTotalHours);
    foreach ($empTotalHours as $id => $data) {
        $hrs = $data['total'];
        $pct = ($hrs / $capacity) * 100;
        $status = 'Optimal'; $color = '000000';
        if ($pct < 70) { $status = 'Underutilized'; $color = 'C00000'; }
        elseif ($pct > 110) { $status = 'Overutilized'; $color = '0070C0'; }
        
        $utilSheet->setCellValue("A$uRow", $id)->setCellValue("B$uRow", $data['name']);
        $utilSheet->setCellValue("C$uRow", round($hrs, 2))->setCellValue("D$uRow", $capacity);
        $utilSheet->setCellValue("E$uRow", round($pct, 1) . '%')->setCellValue("F$uRow", $status);
        $utilSheet->getStyle("F$uRow")->getFont()->getColor()->setRGB($color);
        $uRow++;
    }
    $utilSheet->getColumnDimension('B')->setWidth(25);

    // --- SHEET 3: Missed Cases ---
    $missedSheet = $spreadsheet->createSheet();
    $missedSheet->setTitle('Missed Cases');
    $missedHeaders = ['Date', 'Emp ID', 'Name', 'Email', 'Department', 'Reporting Manager'];
    $missedSheet->fromArray($missedHeaders, NULL, 'A1');
    $missedSheet->getStyle('A1:F1')->applyFromArray($headerStyle);
    $mRow = 2;
    foreach ($missedData as $m) {
        $missedSheet->setCellValue("A$mRow", $m['date'])->setCellValue("B$mRow", $m['emp_id']);
        $missedSheet->setCellValue("C$mRow", $m['name'])->setCellValue("D$mRow", $m['email']);
        $missedSheet->setCellValue("E$mRow", $m['department'])->setCellValue("F$mRow", $m['rm_name']);
        $mRow++;
    }

    // --- SHEET 4: Submitted Cases ---
    $subSheet = $spreadsheet->createSheet();
    $subSheet->setTitle('Submitted Cases');
    $subHeaders = ['Date', 'Emp ID', 'Name', 'Project', 'Task Description', 'Category', 'Hours', 'Status'];
    $subSheet->fromArray($subHeaders, NULL, 'A1');
    $subSheet->getStyle('A1:H1')->applyFromArray($headerStyle);
    $sRow = 2;
    foreach ($submittedData as $s) {
        $subSheet->setCellValue("A$sRow", $s['date'])->setCellValue("B$sRow", $s['emp_id']);
        $subSheet->setCellValue("C$sRow", $s['name'])->setCellValue("D$sRow", $s['client_name']);
        $subSheet->setCellValue("E$sRow", $s['task_description'])->setCellValue("F$sRow", $s['task_category']);
        $subSheet->setCellValue("G$sRow", $s['hours'])->setCellValue("H$sRow", $s['status'] == 1 ? 'Approved' : 'Pending');
        $sRow++;
    }

    // --- SHEET 5: Project Matrix ---
    $matrixSheet = $spreadsheet->createSheet();
    $matrixSheet->setTitle('Project Matrix');
    
    $projects = [];
    foreach ($matrixData as $row) {
        $pName = empty(trim($row['client_name'])) ? 'Others' : trim($row['client_name']);
        if (!in_array($pName, $projects)) {
            $projects[] = $pName;
        }
    }
    sort($projects);
    
    if (($key = array_search('Others', $projects)) !== false) {
        unset($projects[$key]);
        $projects[] = 'Others';
        $projects = array_values($projects);
    }
    
    $matrixHeaders = array_merge(['Employee Name'], $projects, ['Total Hours']);
    $matrixSheet->fromArray($matrixHeaders, NULL, 'A1');            
    $matrixSheet->getStyle('A1:' . $matrixSheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
    
    $matrixLookup = [];
    foreach ($matrixData as $row) { 
        $cName = empty(trim($row['client_name'])) ? 'Others' : trim($row['client_name']);
        if (!isset($matrixLookup[$row['emp_name']][$cName])) {
            $matrixLookup[$row['emp_name']][$cName] = 0;
        }
        $matrixLookup[$row['emp_name']][$cName] += $row['total_hours']; 
    }
    $mxRow = 2;
    foreach ($matrixLookup as $empName => $empProjs) {
        $matrixSheet->setCellValue("A$mxRow", $empName);
        $rowTotal = 0; $col = 'B';
        foreach ($projects as $proj) {
            $h = $empProjs[$proj] ?? 0;
            $matrixSheet->setCellValue($col++ . $mxRow, $h > 0 ? round($h, 2) : '-');
            $rowTotal += $h;
        }
        $matrixSheet->setCellValue($col . $mxRow, round($rowTotal, 2));
        $mxRow++;
    }

    // 5. Save & Send
    $suffix = !empty($departments) ? "_Dept_" . implode('_', $departments) : "_Global";
    $filename = "Weekly_Summary_Report_{$reportStart}_to_{$reportEnd}{$suffix}.xlsx";
    $tempPath = sys_get_temp_dir() . '/' . $filename;
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempPath);

    // Email content
    $template = file_get_contents(__DIR__ . '/../../includes/email_templates/weekly_summary_report.html');
    
    $deptLabel = 'All Departments';
    if (!empty($departments)) {
        $stmt = $conn->prepare("SELECT dept_name FROM department WHERE dept_id = ?");
        $deptId = $departments[0];
        $stmt->bind_param("i", $deptId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) { $deptLabel = $row['dept_name']; }
    }

    $underutilizedTable = '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
    $underutilizedTable .= '<tr><th>Name</th><th style="text-align:center;">Week Hours</th><th style="text-align:center;">Utilization %</th></tr>';
    $count = 0;
    foreach ($empTotalHours as $id => $data) {
        $hrs = $data['total']; $pct = ($hrs / $capacity) * 100;
        if ($pct < 70 && $count < 5) {
            $underutilizedTable .= "<tr><td>{$data['name']}</td><td style='text-align:center;'>".round($hrs, 2)." hrs</td><td style='text-align:center; color:#d93025; font-weight:bold;'>".round($pct, 1)."%</td></tr>";
            $count++;
        }
    }
    if ($count === 0) $underutilizedTable .= '<tr><td colspan="3" style="text-align:center; color:#1e8e3e;">All resources are optimally utilized!</td></tr>';
    $underutilizedTable .= '</table>';

    $displayRange = "$reportStart to $reportEnd" . ($deptLabel !== 'All Departments' ? " [$deptLabel]" : "");
    $emailHtml = str_replace(
        ['{{report_range}}', '{{total_hours}}', '{{avg_hours}}', '{{missed_count}}', '{{pending_count}}', '{{underutilized_table}}'],
        [$displayRange, round($totalHours, 2), round($avgHoursPerDay, 2), count($missedData), 'N/A', $underutilizedTable],
        $template
    );

    try {
        $subject = "Weekly Summary Report: $reportStart to $reportEnd (" . $deptLabel . ")";
        foreach ($emailRecipients as $recipient) {
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) continue;
            logMsg("Emailing $recipient...", $logFile);
            
            try {
                $mail = getMailer();
                $mail->addAddress($recipient);
                $mail->Subject = $subject;
                $mail->Body = $emailHtml;
                $mail->addAttachment($tempPath, $filename);
                $mail->send();
                
                logEmailToDb($conn, $logFile, 0, 'weekly_report', $recipient, 'Admin', $subject, $reportStart, null, 'sent');
            } catch (Exception $e) {
                logMsg("ERROR sending to $recipient: " . $e->getMessage(), $logFile);
                logEmailToDb($conn, $logFile, 0, 'weekly_report', $recipient, 'Admin', $subject, $reportStart, null, 'failed');
            }
        }
        logMsg("Emails sent successfully.", $logFile);
    } catch (Exception $e) {
        logMsg("ERROR: " . $e->getMessage(), $logFile);
    }

    unlink($tempPath);
    return true;
}

// ─── Main Execution Logic ────────────────────────────────────────────────────

// 1. Calculate Date Range (Previous Week: Monday to Sunday)
$reportStart = date('Y-m-d', strtotime('monday last week'));
$reportEnd = date('Y-m-d', strtotime('sunday last week'));

if (PHP_SAPI === 'cli' && isset($argv[1])) {
    parse_str($argv[1], $_GET);
}

// Parameters
if (isset($_GET['start']) && isset($_GET['end'])) {
    $reportStart = $_GET['start'];
    $reportEnd = $_GET['end'];
}

$departments = [];
$forceGlobal = isset($_GET['global']);

if (isset($_GET['dept']) && !empty($_GET['dept'])) {
    $departments = [intval($_GET['dept'])];
} elseif (isset($_GET['departments']) && is_array($_GET['departments'])) {
    $departments = array_map('intval', $_GET['departments']);
}

$repManagers = [];
if (isset($_GET['manager']) && !empty($_GET['manager'])) {
    $repManagers = [intval($_GET['manager'])];
} elseif (isset($_GET['repManagers']) && is_array($_GET['repManagers'])) {
    $repManagers = array_map('intval', $_GET['repManagers']);
}

$emailTo = $_GET['email_to'] ?? $_POST['email_to'] ?? TIMESHEET_FEEDBACK_EMAIL;
$emailRecipients = array_filter(array_map('trim', explode(',', $emailTo)));

// 2. Decide Execution Mode
if (!empty($departments) || !empty($repManagers) || $forceGlobal) {
    // A. Filtered Mode (Manual/Single Dept/Global Force)
    logMsg("Mode: Single Report (Explicit Filter)", $logFile);
    generateWeeklySummary($conn, $task, $logFile, $reportStart, $reportEnd, $departments, $repManagers, $emailRecipients);
} else {
    // B. Auto-Distribution Mode (All Active Departments)
    logMsg("Mode: Auto-Distribution (All Departments)", $logFile);
    
    $deptRes = $conn->query("SELECT dept_id, dept_name FROM department");
    if ($deptRes) {
        $count = 0;
        while ($dept = $deptRes->fetch_assoc()) {
            logMsg("Processing Department: " . $dept['dept_name'], $logFile);
            $success = generateWeeklySummary($conn, $task, $logFile, $reportStart, $reportEnd, [intval($dept['dept_id'])], [], $emailRecipients);
            if ($success) $count++;
        }
        logMsg("Total Department Reports Sent: $count", $logFile);
    } else {
        logMsg("ERROR: Could not fetch departments.", $logFile);
    }
}

logMsg("WEEKLY DISTRIBUTION COMPLETED", $logFile);
if (PHP_SAPI !== 'cli') echo "</pre></body></html>";
exit;
