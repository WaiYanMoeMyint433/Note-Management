<?php
// Check if user is logged in
if (!isset($_SESSION["insession"])) {
    header("Location: login.php");
    exit;
}
?>