<?php
// fetch_labels.php - Retrieves labels for a user

// Start session for CSRF validation

include "../admin/conn.php";
include "../helpers/functions.php";
include "./auth_check.php"; 

// Set JSON content type
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check database connection
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

// Get user ID from POST
$user_id = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
if ($user_id === false || $user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit;
}

try {
    // Query to get all labels used by this user
    $stmt = $conn->prepare("
        SELECT DISTINCT l.id, l.label_text 
        FROM labels l
        JOIN note_labels nl ON l.id = nl.label_id
        WHERE nl.user_id = ?
        ORDER BY l.label_text
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = [
            'id' => $row['id'],
            'text' => $row['label_text']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'labels' => $labels
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch labels: ' . $e->getMessage()
    ]);
}

exit;
?>