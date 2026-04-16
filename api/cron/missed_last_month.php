<?php
// Cron job script to generate missed timesheets report for last month
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/Task.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Log file for this cron job
$logFile = __DIR__ . '/../../logs/missed_timesheets_report.log';

// Helper function to log messages
function logMessage($message, $logFile, $isCron = false) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Only echo for cron jobs, not web requests
    if ($isCron) {
        echo $logEntry;
    }
}

// Determine if this is a cron job or web request
$isCron = (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST']));

// For web requests, check authentication
if (!$isCron) {
    session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        http_response_code(401);
        die('Please login first. <a href="../../index.php">Go to Login</a>');
    }
    if (!isset($_SESSION['emp_role']) || !in_array($_SESSION['emp_role'], ['admin', 'hod', 'rm'])) {
        http_response_code(403);
        die('Unauthorized access. Only Admin, HOD, and RM can access this report.');
    }
}

$task = new Task($conn);

// Allow specifying a custom month (format: YYYY-MM) or default to last month
$customMonth = $_GET['month'] ?? null;

if ($customMonth && preg_match('/^\d{4}-\d{2}$/', $customMonth)) {
    // Use specified month
    $targetDate = $customMonth . '-01';
    $lastMonthStart = date('Y-m-01', strtotime($targetDate));
    $lastMonthEnd = date('Y-m-t', strtotime($targetDate));
    logMessage("Using custom month: $customMonth", $logFile, $isCron);
} else {
    // Use last month by default
    $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
    $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));
    logMessage("Using last month (auto-calculated)", $logFile, $isCron);
}

// Sanitize and validate input parameters (for web requests)
$employees = isset($_GET['employees']) && is_array($_GET['employees']) ? array_map('intval', $_GET['employees']) : [];
$departments = isset($_GET['departments']) && is_array($_GET['departments']) ? array_map('intval', $_GET['departments']) : [];
$repManagers = isset($_GET['repManagers']) && is_array($_GET['repManagers']) ? array_map('intval', $_GET['repManagers']) : [];
$allowedEmployees = isset($_GET['allowedEmployees']) && is_array($_GET['allowedEmployees']) ? array_map('intval', $_GET['allowedEmployees']) : [];

logMessage("Starting missed timesheets report generation", $logFile, $isCron);

// Calculate and log the date range being queried
logMessage("Date range: $lastMonthStart to $lastMonthEnd", $logFile, $isCron);
logMessage("Filters - Employees: " . json_encode($employees) . ", Departments: " . json_encode($departments) . ", RMs: " . json_encode($repManagers), $logFile, $isCron);

// Query to get missed timesheets for the date range
$query = "
    SELECT d.date, e.emp_id, e.name, e.email, rm.name AS rm_name
    FROM (
        SELECT DISTINCT DATE(date) as date
        FROM task
        WHERE DATE(date) BETWEEN ? AND ?
        UNION
        SELECT DATE_ADD(?, INTERVAL seq.n DAY) as date
        FROM (
            SELECT a.n + b.n * 10 AS n
            FROM 
                (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
                  UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3) b
        ) seq
        WHERE DATE_ADD(?, INTERVAL seq.n DAY) <= ?
    ) d
    CROSS JOIN employee e
    LEFT JOIN employee rm ON rm.emp_id = e.rm_id
    LEFT JOIN task t ON t.emp_id = e.emp_id AND DATE(t.date) = d.date
    WHERE t.task_id IS NULL
    AND e.role NOT IN ('hod', 'admin')
    AND e.status = 1
";

$params = [$lastMonthStart, $lastMonthEnd, $lastMonthStart, $lastMonthStart, $lastMonthEnd];
$types = 'sssss';

// Add employee filter
if (!empty($employees)) {
    $placeholders = implode(',', array_fill(0, count($employees), '?'));
    $query .= " AND e.emp_id IN ($placeholders)";
    $types .= str_repeat('i', count($employees));
    $params = array_merge($params, $employees);
}

// Add department filter
if (!empty($departments)) {
    $placeholders = implode(',', array_fill(0, count($departments), '?'));
    $query .= " AND e.dept_id IN ($placeholders)";
    $types .= str_repeat('i', count($departments));
    $params = array_merge($params, $departments);
}

// Add reporting manager filter
if (!empty($repManagers)) {
    $placeholders = implode(',', array_fill(0, count($repManagers), '?'));
    $query .= " AND e.rm_id IN ($placeholders)";
    $types .= str_repeat('i', count($repManagers));
    $params = array_merge($params, $repManagers);
}

// Add allowed employees filter
if (!empty($allowedEmployees)) {
    $placeholders = implode(',', array_fill(0, count($allowedEmployees), '?'));
    $query .= " AND e.emp_id IN ($placeholders)";
    $types .= str_repeat('i', count($allowedEmployees));
    $params = array_merge($params, $allowedEmployees);
}

$query .= " ORDER BY d.date DESC, e.emp_id ASC";

logMessage("Executing query to find missed timesheets...", $logFile, $isCron);

$stmt = $conn->prepare($query);
if (!$stmt) {
    $errorMsg = 'Failed to prepare SQL statement: ' . $conn->error;
    logMessage("ERROR: " . $errorMsg, $logFile, $isCron);
    die($errorMsg . "\n");
}

if (!$stmt->bind_param($types, ...$params)) {
    $errorMsg = 'Failed to bind SQL parameters: ' . $stmt->error;
    logMessage("ERROR: " . $errorMsg, $logFile, $isCron);
    die($errorMsg . "\n");
}

if (!$stmt->execute()) {
    $errorMsg = 'Failed to execute SQL query: ' . $stmt->error;
    logMessage("ERROR: " . $errorMsg, $logFile, $isCron);
    die($errorMsg . "\n");
}

$result = $stmt->get_result();
if (!$result) {
    $errorMsg = 'Failed to fetch SQL results: ' . $stmt->error;
    logMessage("ERROR: " . $errorMsg, $logFile, $isCron);
    die($errorMsg . "\n");
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

logMessage("Found " . count($data) . " missed timesheet entries", $logFile, $isCron);

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    $headers = ['Date', 'Employee ID', 'Employee Name', 'Email', 'Reporting Manager'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Data rows
    $rowIndex = 2;
    foreach ($data as $row) {
        $sheet->setCellValue("A{$rowIndex}", $row['date'] ?? '');
        $sheet->setCellValue("B{$rowIndex}", $row['emp_id'] ?? '');
        $sheet->setCellValue("C{$rowIndex}", $row['name'] ?? '');
        $sheet->setCellValue("D{$rowIndex}", $row['email'] ?? '');
        $sheet->setCellValue("E{$rowIndex}", $row['rm_name'] ?? '');
        $rowIndex++;
    }

    // Style headers
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);
    $sheet->getColumnDimension('C')->setWidth(25);
    $sheet->getColumnDimension('D')->setWidth(30);
    $sheet->getColumnDimension('E')->setWidth(25);

    // Generate filename
    $filename = "missed_timesheets_last_month_" . date('Y_m') . ".xlsx";

    if ($isCron) {
        // For cron jobs: Save to file in the current directory
        $filePath = __DIR__ . '/' . $filename;
        logMessage("Generating Excel file: $filename", $logFile, $isCron);
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        
        logMessage("Report successfully saved to: $filePath", $logFile, $isCron);
        logMessage("File size: " . filesize($filePath) . " bytes", $logFile, $isCron);
    } else {
        // For web requests: Download the file
        // Clear any previous output
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
    
    // Clean up
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    
} catch (Exception $e) {
    $errorMsg = 'Error generating Excel file: ' . $e->getMessage();
    logMessage("ERROR: " . $errorMsg, $logFile, $isCron);
    error_log($errorMsg);
    if ($isCron) {
        die($errorMsg . "\n");
    } else {
        http_response_code(500);
        die($errorMsg);
    }
}

logMessage("Completed successfully", $logFile, $isCron);
exit;
