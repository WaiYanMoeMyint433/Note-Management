<?php

// Start session for CSRF validation
include "../admin/conn.php";
include "../helpers/functions.php";
include "./auth_check.php"; // Check if user is logged in

// Set JSON content type
header('Content-Type: application/json');

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

// Check required parameters
$required_fields = ['user_id', 'title', 'content'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameter: ' . $field]);
        exit;
    }
}
// Decode labels from JSON if they exist
$labelsJson = isset($_POST['labels']) ? $_POST['labels'] : '[]';
$labels = json_decode($labelsJson, true);
if (!is_array($labels)) {
    $labels = [];
}

// Sanitize and validate inputs
$user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
$title = htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8');
$content = trim($_POST['content']); // Content may contain HTML from Quill, so avoid htmlspecialchars
$password = isset($_POST['password']) ? trim($_POST['password']) : null;
$note_id = isset($_POST['note_id']) ? filter_var($_POST['note_id'], FILTER_VALIDATE_INT) : 0;

if ($user_id === false || ($note_id !== 0 && $note_id === false)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user_id or note_id']);
    exit;
}

// Determine if this is a create or update operation
if ($note_id > 0) {
    // Update existing note
    $result = updateNote($conn, $note_id, $user_id, $title, $content, $password);
} else {
    // Create new note
    $result = createNote($conn, $user_id, $title, $content, $password);
    $note_id = $result['note_id']; // Get the newly created note ID
}

if ($result['status'] === 'success' && !empty($labels)) {
    // Clear existing labels for the note (for updates)
    if ($note_id > 0) {
        $deleteStmt = $conn->prepare("DELETE FROM note_labels WHERE note_id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $note_id, $user_id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    // Process each label and associate with the note
    foreach ($labels as $label) {
        $label = trim($label);
        if ($label === '') continue;

        // First, check if the label exists
        $getLabelIdStmt = $conn->prepare("SELECT id FROM labels WHERE label_text = ?");
        $getLabelIdStmt->bind_param("s", $label);
        $getLabelIdStmt->execute();
        $result_label = $getLabelIdStmt->get_result();
        
        if ($result_label->num_rows > 0) {
            // Label already exists, get its ID
            $label_id = $result_label->fetch_assoc()['id'];
        } else {
            // Label doesn't exist, create it
            $insertLabelStmt = $conn->prepare("INSERT INTO labels (label_text) VALUES (?)");
            $insertLabelStmt->bind_param("s", $label);
            $insertLabelStmt->execute();
            $label_id = $conn->insert_id;
            $insertLabelStmt->close();
        }
        
        $getLabelIdStmt->close();

        // Check if this label is already associated with this note for this user
        $checkNoteLabelStmt = $conn->prepare("SELECT COUNT(*) as count FROM note_labels WHERE note_id = ? AND label_id = ? AND user_id = ?");
        $checkNoteLabelStmt->bind_param("iii", $note_id, $label_id, $user_id);
        $checkNoteLabelStmt->execute();
        $result_check = $checkNoteLabelStmt->get_result();
        $already_associated = $result_check->fetch_assoc()['count'] > 0;
        $checkNoteLabelStmt->close();

        // If not already associated, create the association
        if (!$already_associated) {
            $insertNoteLabelStmt = $conn->prepare("INSERT INTO note_labels (note_id, label_id, user_id) VALUES (?, ?, ?)");
            $insertNoteLabelStmt->bind_param("iii", $note_id, $label_id, $user_id);
            $insertNoteLabelStmt->execute();
            $insertNoteLabelStmt->close();
        }
    }
}


if ($result['status'] === 'success') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Note saved successfully',
        'note_id' => $note_id
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $result['message']
    ]);
}
exit;
?>