<?php
require 'c:/Users/Harsh Dhiman/timesheetv2/config/config.php';
$res = $conn->query('DESCRIBE calendar');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
