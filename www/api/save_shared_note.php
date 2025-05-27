<?php
header('Content-Type: application/json');
require_once '../admin/conn.php';
require_once '../helpers/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = getUserEmail($conn, $user_id);

if (!isset($_POST['note_id']) || !isset($_POST['title']) || !isset($_POST['content'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

$note_id = intval($_POST['note_id']);
$title = trim($_POST['title']);
$content = $_POST['content'];

if (empty($title)) {
    echo json_encode([
        'success' => false,
        'message' => 'Title cannot be empty'
    ]);
    exit;
}

$permission = checkSharedNotePermission($conn, $note_id, $user_email);

if ($permission !== 'write') {
    echo json_encode([
        'success' => false,
        'message' => 'You do not have permission to edit this note'
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, update_time = NOW() WHERE id = ?");
$stmt->bind_param("ssi", $title, $content, $note_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Note updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating note: ' . $conn->error
    ]);
}

// Helper functions

// Get user email
function getUserEmail($conn, $user_id) {
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['email'];
    }
    
    return null;
}

// Check if user has permission to edit the shared note
function checkSharedNotePermission($conn, $note_id, $user_email) {
    $stmt = $conn->prepare("SELECT permission FROM shared_notes WHERE note_id = ? AND shared_email = ?");
    $stmt->bind_param("is", $note_id, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    return $row['permission'];
}
?>
