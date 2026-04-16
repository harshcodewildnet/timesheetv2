<?php
// api/login.php
// ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_start();
require_once '../config/config.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    die('Email and password are required.');
}


// Find employee by email and role
$stmt = $conn->prepare("SELECT * FROM employee WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows >= 1) {
    $emp = $result->fetch_assoc();
    // employee deactived by admin 
    // if (!(int) $emp['status'] == 1) {
    //     // echo 'in if condition';
    //     $_SESSION['error'] = 'emp Deactivated by Admin !';
    //     header("Location: ../index");
    //     exit;
    // }
    // Verify password
    if ($password == $emp['password']) {
        if (!$emp['status']) {
            $_SESSION['error'] = 'User deactivated! Please contact the HR Team!';
            header("Location: ../index");
            exit;
        }
        // Save session data
        // After verifying login credentials...
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time(); // store login timestamp
        $_SESSION['emp_id'] = $emp['emp_id'];
        $_SESSION['emp_name'] = $emp['name'];
        $_SESSION['emp_role'] = $emp['role'];

        // Redirect based on role
        /*
        Admin/HR
        HOD
        Rm
        Exec
        */
        switch ($emp['role']) {
            case 'admin':
                header('Location: ../dashboard-admin');
                break;
            case 'hod':
                header('Location: ../dashboard-hod');
                break;
            case 'rm':
                header('Location: ../dashboard-rm');
                break;
            case 'executive':
                header('Location: ../dashboard-executive');
                break;
            case 'sales_manager':
                header('Location: ../dashboard-sm');
                break;
            case 'project_manager':
                header('Location: ../dashboard-pm');
                break;
            default:
                echo "Invalid role.";
        }
        exit;
    } else {
        $_SESSION['error'] = 'Invalid Password';
        header("Location: ../index");
    }
}

$_SESSION['error'] = 'Invalid Login credentials';
header("Location: ../index");
