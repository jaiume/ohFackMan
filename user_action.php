<?php
include 'config.php'; // Access $loginsender and $base_url
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Assuming PHPMailer is installed in the 'PHPMailer' folder
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function generateToken() {
    // Generate a secure, unique token
    return bin2hex(random_bytes(32));
}

function sendLoginLink($email, $token, $base_url,$loginsender,$loginsendername,$SMTP_host,$SMTP_User,$SMTP_Password ) {
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        // Server settings
        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = $SMTP_host; // Specify main and backup SMTP servers from config.php
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = $SMTP_User; // SMTP username from config.php
        $mail->Password = $SMTP_Password; // SMTP password from config.php
        $mail->SMTPSecure = ''; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 25; // TCP port to connect to

        // Recipients
        $mail->setFrom($loginsender, $loginsendername);
        $mail->addAddress($email); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $loginUrl = $base_url . "/login.php?token=" . $token; // Construct the login URL
        $mail->Subject = 'Your Login Link';
        $mail->Body    = 'Here is your login link: <a href="' . $loginUrl . '">' . $loginUrl . '</a>';

        $mail->send();
        return "Your login link has been sent to " . $email;
    } catch (Exception $e) {
        return 'Failed to send login link. Mailer Error: ' . $mail->ErrorInfo;
    }
}





if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'], $_POST['action'])) {
    $conn = db_connect();
    $email = $conn->real_escape_string($_POST['email']);
    $action = $_POST['action'];

    $token = generateToken(); // Generate a new token
    $tokenExpiration = date('Y-m-d H:i:s', strtotime('+' . $Token_Life .' days')); 

    if ($action == 'create_user') {
        $insertQuery = "INSERT INTO users (email, login_token, token_expiration, token_used) VALUES (?, ?, ?, 0)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sss", $email, $token, $tokenExpiration);
        $success = $stmt->execute();

        if ($success) {
            $message = sendLoginLink($email, $token, $base_url,$loginsender,$loginsendername,$SMTP_host,$SMTP_User,$SMTP_Password);
        } else {
            $message = "Error creating user.";
        }
    } elseif ($action == 'login') {
        $updateQuery = "UPDATE users SET login_token = ?, token_expiration = ?, token_used = 0 WHERE email = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sss", $token, $tokenExpiration, $email);
        $success = $stmt->execute();

        if ($success) {
            $message = sendLoginLink($email, $token, $base_url,$loginsender,$loginsendername,$SMTP_host,$SMTP_User,$SMTP_Password );
        } else {
            $message = "Error updating user login token.";
        }
    } else {
        $message = "Invalid action.";
    }

    $stmt->close();
    $conn->close();

    //echo json_encode(['message' => $message]);
	echo $message;
}
?>
