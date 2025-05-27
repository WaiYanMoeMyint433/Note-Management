<?php
include "../templates/errorReport.php";
session_start(); // Start the session
session_unset(); // Remove all session variables
//to clear session cookies on client side, additional consideration
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy(); // Destroy the session
header("Location: ../index.php");
exit;
?>