<?php
session_start();
require '../vendor/autoload.php';
include "../templates/functions.php";
include "../templates/errorReport.php";
include "../admin/conn.php";// Your DB connection

$user_id = $_SESSION['user_id'] ?? null;
//check email is already verify or not?
$stmt = $conn->prepare("SELECT activation FROM Users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if already logged in
if (isset($_SESSION['user_id']) and $user["activation"] === 1) {
    header("Location: ../dashboard/index.php");
    exit;
}

// Get token from URL
$token = $_GET['token'] ?? null;


if (!$token) {
    echo "<p>Invalid verification link.</p>";
    exit;
}

// Find user with matching token
$stmt = $conn->prepare("SELECT id, activation FROM users WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if ($user['activation'] == 1) {
        echo "<h3>Your email is already activation!</h3>";
    } else {
        // Update user as activation
        $update = $conn->prepare("UPDATE users SET activation = 1, token = NULL WHERE id = ?");
        $update->bind_param("i", $user['id']);
        $update->execute();
        header("Location: ../dashboard/index.php");
        exit;
    }
} else {
    echo "<h3>Invalid or expired verification token.</h3>";
}

$stmt->close();
$conn->close();
include "../templates/nav.php";
include "../templates/footer.php";
?>