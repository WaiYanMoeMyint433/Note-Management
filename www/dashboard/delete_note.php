<?php

include "../admin/conn.php";
include "../helpers/functions.php";
include "./auth_check.php"; // Check if user is logged in

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
    $_SESSION['status'] = "Note deleted successfully";
    header("Location: index.php");
    exit;
} else {
    $_SESSION['status'] = "Note delete failed";
    header("Location: index.php");
    exit;
}


    
    $stmt->close();
} else {
    $_SESSION['status'] = "Invalid note ID";
    header("Location: index.php");
    exit;
}

?>