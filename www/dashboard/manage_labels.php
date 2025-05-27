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

// Check action type
if (!isset($_POST['action']) || !in_array($_POST['action'], ['edit', 'delete'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

$action = $_POST['action'];
$user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);

if ($user_id === false || $user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user_id']);
    exit;
}

if ($action === 'edit') {
    // Validate edit parameters
    if (!isset($_POST['label_id'], $_POST['new_label_text'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
        exit;
    }

    $label_id = filter_var($_POST['label_id'], FILTER_VALIDATE_INT);
    $new_label_text = trim($_POST['new_label_text']);

    if ($label_id === false || $label_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid label_id']);
        exit;
    }

    if (empty($new_label_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Label text cannot be empty']);
        exit;
    }

    // Check if this user has access to this label
    $accessStmt = $conn->prepare("SELECT COUNT(*) as count FROM note_labels WHERE label_id = ? AND user_id = ?");
    $accessStmt->bind_param("ii", $label_id, $user_id);
    $accessStmt->execute();
    $result = $accessStmt->get_result();
    $row = $result->fetch_assoc();
    $hasAccess = $row['count'] > 0;
    $accessStmt->close();

    if (!$hasAccess) {
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to edit this label']);
        exit;
    }

    // Update label - Removed the duplicate check as requested
    $updateStmt = $conn->prepare("UPDATE labels SET label_text = ? WHERE id = ?");
    $updateStmt->bind_param("si", $new_label_text, $label_id);
    
    if ($updateStmt->execute()) {
        // Update successful
        $updateStmt->close();
        
        $checkExistingLabel = $conn->prepare("SELECT id FROM labels WHERE label_text = ? AND id != ?");
        $checkExistingLabel->bind_param("si", $new_label_text, $label_id);
        $checkExistingLabel->execute();
        $existingResult = $checkExistingLabel->get_result();
        
        $checkExistingLabel->close();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Label updated successfully', 
            'new_label_text' => $new_label_text,
            'label_id' => $label_id
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to update label: ' . $conn->error
        ]);
        $updateStmt->close();
    }
} elseif ($action === 'delete') {
    // Validate delete parameters
    if (!isset($_POST['label_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing label_id']);
        exit;
    }

    $label_id = filter_var($_POST['label_id'], FILTER_VALIDATE_INT);

    if ($label_id === false || $label_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid label_id']);
        exit;
    }

    // Check if this user has access to this label
    $accessStmt = $conn->prepare("SELECT COUNT(*) as count FROM note_labels WHERE label_id = ? AND user_id = ?");
    $accessStmt->bind_param("ii", $label_id, $user_id);
    $accessStmt->execute();
    $result = $accessStmt->get_result();
    $row = $result->fetch_assoc();
    $hasAccess = $row['count'] > 0;
    $accessStmt->close();

    if (!$hasAccess) {
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to delete this label']);
        exit;
    }

    // Start transaction to ensure data consistency
    $conn->begin_transaction();
    try {
        // Delete from note_labels for this user only
        $deleteNoteLabelsStmt = $conn->prepare("DELETE FROM note_labels WHERE label_id = ? AND user_id = ?");
        $deleteNoteLabelsStmt->bind_param("ii", $label_id, $user_id);
        $deleteNoteLabelsStmt->execute();
        $affectedRows = $deleteNoteLabelsStmt->affected_rows;
        $deleteNoteLabelsStmt->close();

        // If no rows were affected, something went wrong
        if ($affectedRows === 0) {
            throw new Exception("No label associations were deleted");
        }

        // Check if there are any more references to this label
        $checkLabelStmt = $conn->prepare("SELECT COUNT(*) as count FROM note_labels WHERE label_id = ?");
        $checkLabelStmt->bind_param("i", $label_id);
        $checkLabelStmt->execute();
        $result = $checkLabelStmt->get_result();
        $row = $result->fetch_assoc();
        $labelUsageCount = $row['count'];
        $checkLabelStmt->close();

        // Only delete the label if it's not used elsewhere
        if ($labelUsageCount == 0) {
            $deleteLabelStmt = $conn->prepare("DELETE FROM labels WHERE id = ?");
            $deleteLabelStmt->bind_param("i", $label_id);
            $deleteLabelStmt->execute();
            $deleteLabelStmt->close();
        }

        $conn->commit();
        echo json_encode([
            'status' => 'success', 
            'message' => 'Label deleted successfully',
            'label_id' => $label_id
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to delete label: ' . $e->getMessage()
        ]);
    }
}

$conn->close();
exit;
?>