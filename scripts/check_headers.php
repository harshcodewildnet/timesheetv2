<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFile = __DIR__ . '/../uploads/task_import2.xlsx';

if (!file_exists($inputFile)) {
    die("Error: File not found at $inputFile\n");
}

try {
    $spreadsheet = IOFactory::load($inputFile);
    $sheet = $spreadsheet->getActiveSheet();
    $headers = $sheet->rangeToArray('A1:Z1', NULL, TRUE, FALSE)[0]; // Read first row
    
    // Filter out empty headers
    $headers = array_filter($headers);
    
    echo "Headers found:\n";
    foreach ($headers as $index => $header) {
        echo "[$index] $header\n";
    }

} catch (Exception $e) {
    die('Error loading file: ' . $e->getMessage() . "\n");
}
?>