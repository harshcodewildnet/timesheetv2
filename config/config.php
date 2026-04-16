<?php
// $path = require__DIR__ . 'env.php';
// $host = DB_HOST;
// $user = DB_USER;
// $password = DB_PASS;
// $db = DB_NAME;
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'timesheet_v2';

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getClientLabel($dept_id = null): string
{
    return (intval($dept_id) === 5) ? 'Project' : 'Client';
}
