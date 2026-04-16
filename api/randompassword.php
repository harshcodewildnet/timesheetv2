<?php
include '../config/config.php'; // DB connection
include '../includes/utilities.php'; // generate random string function


// function generateRandomString($length = 6)
// {
//     $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
//     $charactersLength = strlen($characters);
//     $randomString = '';
//     for ($i = 0; $i < $length; $i++) {
//         $randomString .= $characters[random_int(0, $charactersLength - 1)];
//     }
//     return $randomString;
// }



// Fetch all employees
$query = "SELECT emp_id, name FROM employee where role != 'admin'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $emp_id = $row['emp_id'];
        $name = $row['name'];

        // Extract first name (first word)
        $firstName = explode(" ", trim($name))[0];

        // Generate secure 6-char alphanumeric string
        $randStr = generateRandomString(6);

        // Final password
        $newPassword = $firstName . '@' . $randStr;

        // Update in DB
        $stmt = $conn->prepare("UPDATE employee SET password = ? WHERE emp_id = ?");
        $stmt->bind_param("si", $newPassword, $emp_id);
        $stmt->execute();

        echo "Updated $name → $newPassword <br>";
    }
    echo "Passwords updated successfully!";
} else {
    echo "No employees found!";
}
