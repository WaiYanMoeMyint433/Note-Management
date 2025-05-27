<?php
include "../admin/conn.php";
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


$domain = "http://localhost:8080/auth/";

function escape($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function showRecordsSelective($conn, $arr, $user_id)
{
    $safeColumns = array_map(function ($col) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $col);
    }, $arr);
    $output = implode(", ", $safeColumns);
    $sql = "SELECT $output FROM Users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// Read all columns
function showRecords($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

//delete user
function deleteUser($conn, $user_id)
{
    $stmt = $conn->prepare("DELETE FROM Users WHERE id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function updateUser($conn, $user_id, $data)
{
    $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssi", $data[0], $data[1], $user_id);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
    
}


function createUser($conn, $username, $email, $password, $token)
{
    error_log("Creating user: $username, $email");

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Users (name, email, password, token) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        echo "false";
        error_log("Error preparing statement: " . $conn->error);
        echo "An error occurred during registration. Please try again later.";
        $conn->close();
        exit();
    }

    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $token);

    $success = $stmt->execute();
    $last_id = mysqli_insert_id($conn);
    $stmt->close();
    return $last_id;
}

function validateUserInput($username, $email, $password, $conn)
{
    $errors = [];

    // Validate Username
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username cannot exceed 50 characters.';
    }

    // Validate Email
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email cannot exceed 100 characters.';
    }

    // Validate Password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/^(?=.*\d)(?=.*[!@#$%^&*]).{8,}$/', $password)) {
        $errors[] = 'Password must include at least one number and one special character.';
    }

    // Check for validation errors
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        header("Location: register.php");
        $conn->close();
        exit();
    }

    // Check for Duplicate Email
    $sql = "SELECT 1 FROM Users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Error preparing statement: " . $conn->error);
        $_SESSION['registration_errors'] = ['An error occurred during registration. Please try again later.'];
        header("Location: register.php");
        $conn->close();
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['registration_errors'] = ['This email is already registered. Please use a different email address.'];
        $stmt->close();
        $conn->close();
        header("Location: register.php");
        exit();
    }

    // Clean up
    $stmt->close();
}

function sendMailforPasswordReset($email, $confirmLink, $content)
{
    $mail = new PHPMailer(true);
    try {
        // SMTP setup
        $mail->isSMTP();
        $mail->Host = 'mailhog'; 
        $mail->Port = 1025;        
        $mail->SMTPAuth = false;

        // Email details
        $mail->setFrom('no-reply@example.com', 'Your App');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $content->Subject;
        $mail->Body = $content->Body;
        $mail->AltBody = $content->AltBody;

        // Send email
        $mail->send();
        return true;

    } catch (Exception $e) {
        echo "Email could not be sent. Error: {$mail->ErrorInfo}";
        return false;
    }
}
function showAlert($message, $type = 'error')
{
    $colors = [
        'error' => '#f44336',
        'warning' => '#ff9800',
        'success' => '#4CAF50',
        'info' => '#2196F3',
    ];
    $color = $colors[$type] ?? '#f44336';

    echo '
    <div id="alertBox" style="
        position: fixed;
        top: 5em;
        right: 20px;
        min-width: 20em;
        max-width: 50rem;
        padding: 15px 20px;
        background-color: ' . $color . ';
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        font-family: Arial, sans-serif;
        z-index: 9999;
        opacity: 1;
        transition: opacity 0.5s ease-out;
    ">
        ' . htmlspecialchars($message) . '
    </div>

    <script>
    setTimeout(function() {
        var alertBox = document.getElementById("alertBox");
        if (alertBox) {
            alertBox.style.opacity = "0";
            setTimeout(function() {
                alertBox.remove();
            }, 500);
        }
    }, 5000); // Hide after 5 seconds
    </script>
    ';
}
function checkEmail($conn, $emailToCheck)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $emailToCheck);
    $stmt->execute();
    $stmt->store_result();
    $status = false;
    if ($stmt->num_rows > 0) {
        $status = true;
    }
    $stmt->close();
    return $status;
}

function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getIdbyEmail($conn, $email)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $id = null; 
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id);
        $stmt->fetch();
        return $id;
    }
    return null;
}


?>