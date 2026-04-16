<?php
session_start();
require '../vendor/autoload.php';
require '../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $otp = trim($data['otp'] ?? '');

    if (!$email) {
        throw new Exception('Invalid email address.');
    }

    // Check if email exists in the DB
    $stmt = $conn->prepare("SELECT emp_id FROM employee WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email not registered.']);
        exit;
    }
    $stmt->close();

    // Handle OTP sending
    if ($action === 'send') {
        $generatedOtp = rand(100000, 999999);

        $_SESSION['otp'] = [
            'email' => $email,
            'code' => $generatedOtp,
            'expires' => time() + 600
        ];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'dmwildnet@gmail.com';
            $mail->Password = 'vklo zozv ryip siyo';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('dmwildnet@gmail.com', 'DM WildNet');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'OTP Verification Code';
            $mail->Body = "
                            <p>Dear User,</p>
                            <p>Your One-Time Password (OTP) for password reset is: <strong>$generatedOtp</strong></p>
                            <p>This code is valid for the next <strong>10 minutes</strong>. Please do not share this code with anyone.</p>
                            <br>
                            <p>Regards,<br><b>DM WildNet Team<b></p>
                        ";

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
        } catch (Exception $e) {
            throw new Exception("Mailer error: " . $mail->ErrorInfo);
        }
    }

    // Handle OTP verification
    elseif ($action === 'verify') {
        $stored = $_SESSION['otp'] ?? null;

        if (!$stored) {
            throw new Exception('No OTP session found. Please request a new OTP.');
        }

        if (
            $stored['email'] === $email &&
            $stored['code'] == $otp &&
            time() < $stored['expires']
        ) {
            $_SESSION['verified_email'] = $email;
            unset($_SESSION['otp']);
            echo json_encode(['success' => true, 'message' => 'OTP verified successfully. Redirecting !']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
        }
    }

    // Invalid action fallback
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
