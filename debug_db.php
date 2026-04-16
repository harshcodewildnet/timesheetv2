<?php
require_once 'config/config.php';
$res = $conn->query("DESCRIBE task");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>