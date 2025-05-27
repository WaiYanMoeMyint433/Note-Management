<?php
// remove_password.php
require_once '../admin/conn.php';  
require_once '../helpers/functions.php';  
include "./auth_check.php"; // Check if user is logged in

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Check for CSRF token
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Get parameters
$noteId = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;
$password = isset($_POST['password']) ? $_POST['password'] : '';
$userId = $_SESSION['user_id'] ?? 1;  

// Validate note ID
if ($noteId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid note ID']);
    exit;
}

try {
    // Get the note's password
    $stmt = $conn->prepare("SELECT password FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $noteId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $note = $result->fetch_assoc();
    
    if (!$note) {
        echo json_encode(['status' => 'error', 'message' => 'Note not found']);
        exit;
    }
    
    // Log the passwords for debugging (remove in production!)
    error_log("Stored password: " . $note['password']);
    error_log("Submitted password: " . $password);
    
    // Verify the password
    $isValid = false;
    
    if (!empty($note['password'])) {
        // For plain text passwords
        $isValid = ($note['password'] === $password);
        
    }
    
    if (!$isValid) {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect password']);
        exit;
    }
    
    // Remove password
    $stmt = $conn->prepare("UPDATE notes SET password = NULL WHERE id = ?");
    $stmt->bind_param("i", $noteId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password removed successfully'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove password: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>