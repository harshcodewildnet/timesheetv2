<?php
require_once 'config/config.php';

$sql = "CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Table 'settings' created successfully.\n";
    
    // Insert default lock date if not exists (e.g., lock anything before 2024-01-01)
    $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('timesheet_lock_date', '2024-01-01')");
    $stmt->execute();
    echo "Initial lock date set.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
