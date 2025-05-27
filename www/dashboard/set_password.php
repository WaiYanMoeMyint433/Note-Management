<?php
// set_password.php
require_once '../admin/conn.php';  
require_once '../helpers/functions.php';  
include "./auth_check.php"; // Check if user is logged in

// Set content type to JSON
header('Content-Type: application/json');

// Check for CSRF token
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Get parameters
$noteId = isset($_POST['note_id']) ? intval($_POST['note_id']) : 0;
$password = isset($_POST['password']) ? $_POST['password'] : '';
$userId = $_SESSION['user_id'] ?? 1;  

// Validate note ID and password
if ($noteId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid note ID']);
    exit;
}

if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Password cannot be empty']);
    exit;
}

try {
    // Check if note exists and belongs to user
    $stmt = $conn->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $noteId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Note not found or access denied']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE notes SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $password, $noteId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password set successfully'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to set password: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>