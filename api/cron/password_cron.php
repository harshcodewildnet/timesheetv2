<?php
// cron.php
set_time_limit(0); // at the top of your cron script
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'mailer.php';
require_once 'utils.php';

$query = "
    SELECT emp_id, name, email, created_at
    FROM employee e
    WHERE e.role IN ('rm', 'executive')
      AND e.password_changed_at IS NULL
      AND DATEDIFF(CURDATE(), DATE(e.created_at)) >= 3
      AND NOT EXISTS (
          SELECT 1 FROM email_logs l
          WHERE l.emp_id = e.emp_id AND l.email_type = 'password_reset'
      )
";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($emp = $result->fetch_assoc()) {
        sendPasswordChangeReminder($emp);
    }
}

echo "Cron completed at " . date('Y-m-d H:i:s') . "\n";

