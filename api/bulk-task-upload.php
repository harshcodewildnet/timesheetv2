<?php
// api/bulk-task-upload.php
session_start();
require_once '../config/config.php';
require_once '../classes/Task.php';
require_once '../classes/Project.php';

header('Content-Type: application/json');

// 1. Auth check - Admin only
if (!isset($_SESSION['logged_in']) || $_SESSION['emp_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin access required.']);
    exit;
}

if (!isset($_FILES['csv_file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
$handle = fopen($file, "r");

if ($handle === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to open file.']);
    exit;
}

// Skip header
$header = fgetcsv($handle);
/* 
Expected CSV Header:
emp_id, date, project_id, cat_id, subcat_id, client_id, work_type, duration, description
*/

$taskObj = new Task($conn);
$successCount = 0;
$errorRows = [];
$rowNum = 1; // Starting after header

$conn->begin_transaction();

try {
    // ─── LOOKUP CACHES ───
    // We fetch these to support both Name and ID in the CSV
    $empLookup = []; // name -> id
    $empIds    = []; // id -> id (validation)
    $res = $conn->query("SELECT emp_id, name FROM employee WHERE status=1");
    while($r = $res->fetch_assoc()) {
        $empLookup[strtolower(trim($r['name']))] = $r['emp_id'];
        $empIds[$r['emp_id']] = $r['emp_id'];
    }

    $clientLookup = []; // name -> id
    $clientIds    = []; // id -> id
    $res = $conn->query("SELECT client_id, client_name FROM client");
    while($r = $res->fetch_assoc()) {
        $clientLookup[strtolower(trim($r['client_name']))] = $r['client_id'];
        $clientIds[strtolower(trim($r['client_id']))] = $r['client_id'];
    }

    $projectLookup = []; // name -> id
    $projectIds    = []; // id -> id
    $res = $conn->query("SELECT project_id, project_name FROM project");
    while($r = $res->fetch_assoc()) {
        $projectLookup[strtolower(trim($r['project_name']))] = $r['project_id'];
        $projectIds[$r['project_id']] = $r['project_id'];
    }

    $catLookup = []; // name -> id
    $catIds    = []; // id -> id
    $res = $conn->query("SELECT cat_id, cat_name FROM task_category");
    while($r = $res->fetch_assoc()) {
        $catLookup[strtolower(trim($r['cat_name']))] = $r['cat_id'];
        $catIds[$r['cat_id']] = $r['cat_id'];
    }

    while (($data = fgetcsv($handle)) !== false) {
        $rowNum++;
        // Allow optional description, so at least 8 columns
        if (count($data) < 8) {
            $errorRows[] = "Row $rowNum: Insufficient columns. Need at least 8 columns.";
            continue;
        }

        // --- Resolve Employee ---
        $empInput = trim($data[0]);
        $empId = $empIds[$empInput] ?? ($empLookup[strtolower($empInput)] ?? null);

        $date = trim($data[1]);

        // --- Resolve Project ---
        $projInput = trim($data[2]);
        $projectId = $projectIds[$projInput] ?? ($projectLookup[strtolower($projInput)] ?? null);

        // --- Resolve Category ---
        $catInput = trim($data[3]);
        $catId = $catIds[$catInput] ?? ($catLookup[strtolower($catInput)] ?? null);

        // Sub-category (optional/default 0)
        $subcatInput = trim($data[4] ?? '0');
        $subcatId = is_numeric($subcatInput) ? (int)$subcatInput : 0;

        // --- Resolve Client ---
        $clientInput = trim($data[5]);
        // Client ID can be alphanumeric like 'p006'
        $clientId = $clientIds[strtolower($clientInput)] ?? ($clientLookup[strtolower($clientInput)] ?? null);

        $workType    = trim($data[6]);
        $duration    = trim($data[7]);
        $description = trim($data[8] ?? '');

        // Basic validation
        if (!$empId)     { $errorRows[] = "Row $rowNum: Employee '$empInput' not found or invalid ID."; continue; }
        if (!$date)      { $errorRows[] = "Row $rowNum: Date is missing."; continue; }
        if (!$catId)     { $errorRows[] = "Row $rowNum: Category '$catInput' not found or invalid ID."; continue; }
        if (!$duration)  { $errorRows[] = "Row $rowNum: Duration is missing."; continue; }

        // Bypass lock is handled by passing 'admin' role to addTask
        $result = $taskObj->addTask([
            'emp_id'          => $empId,
            'work_type'       => $workType ?: 'Working',
            'task_category'   => $catId,
            'task_subcategory'=> $subcatId,
            'client_id'       => $clientId ?: null,
            'project_id'      => $projectId ?: null,
            'description'     => $description,
            'date'            => $date,
            'time_taken'      => $duration,
            'status'          => 1, // Auto-approve bulk entries
            'user_role'       => 'admin' 
        ]);

        if ($result['success']) {
            $successCount++;
        } else {
            $errorRows[] = "Row $rowNum: " . ($result['error'] ?? 'Unknown error');
        }
    }

    if (!empty($errorRows)) {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'Bulk upload failed. No data was imported.',
            'errors' => $errorRows
        ]);
    } else {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "Successfully imported $successCount tasks."
        ]);
    }

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Critical error: ' . $e->getMessage()]);
}

fclose($handle);
?>
