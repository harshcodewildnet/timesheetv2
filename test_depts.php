<?php
require_once 'config/config.php';
$res = $conn->query('SELECT dept_id, dept_name FROM department WHERE status = 1');
while($row = $res->fetch_assoc()) {
    echo $row['dept_id'] . ':' . $row['dept_name'] . PHP_EOL;
}
