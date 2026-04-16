<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Task.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Configuration
$inputFile = __DIR__ . '/../uploads/task_import2.xlsx';
$targetEmpId = 1861;

if (!file_exists($inputFile)) {
    die("Error: File not found at $inputFile\n");
}

try {
    $spreadsheet = IOFactory::load($inputFile);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
} catch (Exception $e) {
    die('Error loading file: ' . $e->getMessage() . "\n");
}

$taskObj = new Task($conn);
$successCount = 0;
$failCount = 0;

// --- Helper Functions ---

function getCategoryId($conn, $name) {
    $name = trim($name);
    // Case-insensitive search
    $stmt = $conn->prepare("SELECT cat_id FROM task_category WHERE LOWER(cat_name) = LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) return $row['cat_id'];
    return null;
}

function getSubCategoryId($conn, $catId, $name) {
    $name = trim($name);
    // Case-insensitive search
    $stmt = $conn->prepare("SELECT subcat_id FROM task_subcategory WHERE LOWER(brief) = LOWER(?) AND cat_id = ? LIMIT 1");
    $stmt->bind_param("si", $name, $catId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) return $row['subcat_id'];
    return null;
}

function parseDuration($rawDuration) {
    $rawDuration = strtolower(trim($rawDuration));
    
    // Handle "45 mins", "30 mins"
    if (preg_match('/(\d+)\s*mins?/', $rawDuration, $matches)) {
        $minutes = (int)$matches[1];
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf("%02d:%02d:00", $hours, $mins);
    }
    
    // Handle Excel numeric time (fraction of day)
    if (is_numeric($rawDuration)) {
        $hours = floor($rawDuration * 24);
        $mins = floor(($rawDuration * 24 * 60) % 60);
        return sprintf("%02d:%02d:00", $hours, $mins);
    }

    // Handle "HH:MM" string
    if (strpos($rawDuration, ':') !== false) {
        return date('H:i:s', strtotime($rawDuration));
    }

    return "00:00:00"; // Fallback
}

function parseDateValue($rawDate) {
    if (is_numeric($rawDate)) {
        // Excel serialized date
        return Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
    }
    // Try parsing DD-MM-YYYY
    $dateObj = DateTime::createFromFormat('d-m-Y', $rawDate);
    if ($dateObj) {
        return $dateObj->format('Y-m-d');
    }
    // Fallback to standard parse
    return date('Y-m-d', strtotime($rawDate));
}

// --- Main Execution ---

echo "Starting import for Employee ID: $targetEmpId...\n";

// Skip Header Row (Index 0)
array_shift($rows);

$totalRows = count($rows);
echo "Found $totalRows rows to process.\n";

foreach ($rows as $index => $row) {
    // Excel Columns based on image:
    // 0: Date
    // 1: Working/Leave
    // 2: Nature of Task (Category)
    // 3: Task Brief (Subcategory)
    // 4: Description
    // 5: Duration

    // Check if row is empty
    if (empty($row[0])) {
        // echo "Row " . ($index + 2) . ": Empty date, skipping.\n";
        continue;
    }

    $rawDate = $row[0];
    // echo "Processing Row " . ($index + 2) . " (Date: $rawDate)...\n";

    $workType = trim($row[1]);
    $categoryName = trim($row[2]);
    $briefName = trim($row[3]);
    $description = trim($row[4]);
    $rawDuration = $row[5];

    // 1. Process Date
    $date = parseDateValue($rawDate);

    // 2. Process Duration
    $timeTaken = parseDuration($rawDuration);

    // 3. Resolve IDs
    if (in_array($workType, ['Leave', 'Week Off', 'Public Holiday', 'Half-Day Leave'])) {
        $catId = -1;
        $subCatId = -1;
    } else {
        $catId = getCategoryId($conn, $categoryName);
        $subCatId = null;

        if ($catId) {
            $subCatId = getSubCategoryId($conn, $catId, $briefName);
        }
    }

    // Validation
    if (!$catId && $workType == 'Working') {
        echo "Row " . ($index + 2) . ": Skipped - Category '$categoryName' not found in DB.\n";
        $failCount++;
        continue;
    }
    
    if (!$subCatId && $workType == 'Working') {
        // Optional: You might want to allow insertion even if subcategory is missing, 
        // but usually it's required. Uncomment below to skip.
        // echo "Row " . ($index + 2) . ": Skipped - Subcategory '$briefName' not found for Category '$categoryName'.\n";
        // $failCount++;
        // continue;
        
        // For now, let's set it to NULL or a default if your DB allows, 
        // otherwise the INSERT will fail if subcat_id is required.
        // Based on your SQL dump, subcat_id is NOT NULL usually, but let's try.
    }

    // Prepare Task Array
    $taskData = [
        'emp_id' => $targetEmpId,
        'work_type' => $workType,
        'task_category' => $catId,
        'task_subcategory' => $subCatId,
        'client_id' => null, // No client column in Excel
        'task_description' => $description,
        'date' => $date,
        'time_taken' => $timeTaken,
        'status' => 0 // Pending
    ];

    // Check for Duplicates
    try {
        $checkStmt = $conn->prepare("SELECT task_id FROM task WHERE emp_id = ? AND date = ? AND duration = ? AND description = ? LIMIT 1");
        if (!$checkStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $checkStmt->bind_param("isss", $targetEmpId, $date, $timeTaken, $description);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            // echo "Row " . ($index + 2) . ": Skipped - Duplicate entry found.\n";
            $checkStmt->close();
            continue;
        }
        $checkStmt->close();
    } catch (Exception $e) {
        echo "Row " . ($index + 2) . ": Error checking duplicates - " . $e->getMessage() . "\n";
        $failCount++;
        continue;
    }

    // Insert
    $result = $taskObj->addTask($taskData);

    if ($result['success']) {
        $successCount++;
    } else {
        echo "Row " . ($index + 2) . ": Failed - " . ($result['error'] ?? 'Unknown error') . "\n";
        $failCount++;
    }
}

echo "\nImport Completed.\n";
echo "Success: $successCount\n";
echo "Failed: $failCount\n";
?>