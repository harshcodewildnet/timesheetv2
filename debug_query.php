<?php
require_once 'config/config.php';
$query = "SELECT SQL_CALC_FOUND_ROWS
            t.*, 
            tc.cat_name, 
            tscat.brief, 
            e.name AS name, 
            e.email, 
            e.rm_id, 
            d.dept_name, 
            d.dept_id, 
            cl.client_name
        FROM task t
        JOIN task_category tc ON t.cat_id = tc.cat_id
        LEFT JOIN task_subcategory tscat ON t.subcat_id = tscat.subcat_id
        JOIN employee e ON t.emp_id = e.emp_id
        JOIN department d ON e.dept_id = d.dept_id
        LEFT JOIN client cl ON t.client_id = cl.client_id
        WHERE e.role != 'hod'
        LIMIT 1";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "Prepare failed: " . $conn->error . "\n";
} else {
    echo "Prepare succeeded!\n";
}
?>