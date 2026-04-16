<?php

session_start();

header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../vendor/autoload.php'; // for PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

$response = ['success' => false, 'message' => '', 'inserted' => 0, 'skipped' => 0];

try {
    if (!isset($_FILES['xls-file']) || $_FILES['xls-file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload failed. Please select a valid Excel/CSV file.");
    }

    $fileTmp = $_FILES['xls-file']['tmp_name'];
    $spreadsheet = IOFactory::load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);

    if (count($rows) < 2) {
        throw new Exception("The uploaded file appears to be empty or incorrectly formatted.");
    }

    $inserted = 0;
    $skipped = 0;

    //  DELETE ALL OLD DATA FIRST
    $conn->query("DELETE FROM calendar");

    // Prepare insert statement
    $insertStmt = $conn->prepare("
        INSERT INTO calendar (`date`, `day`, `description`, `type`) 
        VALUES (?, ?, ?, ?)
    ");

    // Loop rows (skip header)
    foreach ($rows as $index => $row) {
        if ($index == 1)
            continue;

        $date = trim($row['A']);
        $day = trim($row['B']);
        $description = trim($row['C']);
        $type = trim($row['D']);

        if (empty($date) || empty($description) || empty($type)) {
            $skipped++;
            continue;
        }

        // Convert Excel Date
        $excelTimestamp = strtotime($date);
        if (!$excelTimestamp && is_numeric($date)) {
            $excelTimestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($date);
        }

        if (!$excelTimestamp) {
            $skipped++;
            continue;
        }

        $finalDate = date('Y-m-d', $excelTimestamp);

        // Insert new row
        $insertStmt->bind_param('ssss', $finalDate, $day, $description, $type);

        if ($insertStmt->execute()) {
            $inserted++;
        } else {
            $skipped++;
        }
    }

    $response['success'] = true;
    $response['inserted'] = $inserted;
    $response['skipped'] = $skipped;
    $response['message'] = "Upload complete. Replaced old data. Inserted: $inserted, Skipped: $skipped.";

    $_SESSION['message'] = "Upload complete. Replaced old data. Inserted: $inserted, Skipped: $skipped.";
    $_SESSION['success'] = true;


} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();

    $_SESSION['message'] = "Upload Failed. Error : " . $e->getMessage();
    $_SESSION['success'] = true;

}

// Cleanup
if (isset($insertStmt))
    $insertStmt->close();
$conn->close();

echo json_encode($response);
?>