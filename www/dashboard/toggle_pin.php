<?php
// toggle_pin.php
require_once '../admin/conn.php';  
require_once '../helpers/functions.php';  
include "./auth_check.php"; // Check if user is logged in

// Set content type to JSON
header('Content-Type: application/json');

// Enable error logging but don't display errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');  

// Check for CSRF token
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Get parameters
$noteId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$pinStatus = isset($_POST['pin']) ? intval($_POST['pin']) : 0;
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;  

// Validate note ID
if ($noteId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid note ID']);
    exit;
}

try {
    // Check if the notes table has the pinned and pinned_time columns
    $checkColumns = $conn->query("SHOW COLUMNS FROM notes LIKE 'pinned'");
    if ($checkColumns->num_rows === 0) {
        // Add the missing columns if they don't exist
        $conn->query("ALTER TABLE notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0");
        $conn->query("ALTER TABLE notes ADD COLUMN pinned_time DATETIME NULL");
    }

    // Check if note belongs to user
    $stmt = $conn->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $noteId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Note not found or access denied']);
        exit;
    }
    
    // Update pin status
    if ($pinStatus) {
        $stmt = $conn->prepare("UPDATE notes SET pinned = 1, pinned_time = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE notes SET pinned = 0, pinned_time = NULL WHERE id = ?");
    }
    $stmt->bind_param("i", $noteId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => $pinStatus ? 'Note pinned successfully' : 'Note unpinned successfully'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update pin status: ' . $conn->error]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>