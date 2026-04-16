<?php
function isValidCompanyEmail($email, $allowedDomains = ['wildnet.com'])
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format.";
    }

    [$local, $domain] = explode('@', $email, 2);

    // Optional: handle IDN domains
    if (function_exists('idn_to_ascii')) {
        $ascii = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
        if ($ascii !== false) {
            $domain = $ascii;
        }
    }

    if (!in_array($domain, $allowedDomains, true)) {
        return "Only company emails (" . implode(', ', $allowedDomains) . ") are allowed.";
    }

    return true;
}

function generateRandomString($length = 6)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomSring = "";
    for ($i = 0; $i < $length; $i++) {
        $randomSring .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomSring;
}

function sendAccountEmail($email, $name, $password)
{
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = defined('SMTP_USER') ? SMTP_USER : 'timesheet.owner@wildnettechnologies.com';
        $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : 'dkqk ytsd wspw rejp';
        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;

        $mail->setFrom(defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com', 'WildNet Timesheet Portal');
        $mail->addAddress($email, $name);

        $loginUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : "https://timesheet.wildnettechnologies.com";

        $mail->isHTML(true);
        $mail->Subject = "Account Details for Wildnet Timesheet Portal";

        $mail->Body = "
                    <p><strong>Dear $name,</strong></p>
                    <p>We are pleased to inform you that your account has been successfully created in the Wildnet Timesheet Portal. Please find your login details below.</p>
                    <p>Your Login Credentials:</p>
                    <ul>
                        <li>Email Id: $email</li>
                        <li>Password: $password</li>
                        <li>Timesheet Link: <a href='$loginUrl'>$loginUrl</a></li>
                    </ul>
                                            
                    <p><strong>Important Instructions:</strong></p>
                    <ul>
                        <li>Use the above credentials to log in for the first time.</li>
                        <li>Change your password after log in to keep your account secure.</li>
                        <li>Keep your login credentials confidential and do not share them with anyone.</li>
                    </ul>
                    <p>If you encounter any issues during login or need assistance, please contact our support team at " . (defined('TIMESHEET_EMAIL') ? TIMESHEET_EMAIL : 'timesheet.owner@wildnettechnologies.com') . ".
                    We are excited to have you onboard and look forward to your contribution to Wildnet.</p>
                    <b>Best Regards,</b><br>
                    <b>HR Team</b> <br>
                    <b>Wildnet Technologies Ltd.</b>
                ";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        logMailStatus("Mail failed to $email: " . $e->getMessage());
        throw new \Exception("Failed to send email to $email : " . $e->getMessage());
    }
}

function logMailStatus($message)
{
    $logFile = __DIR__ . "/../api/bulk_mail_log.txt";
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}