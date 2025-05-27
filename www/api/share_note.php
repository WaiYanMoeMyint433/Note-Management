<?php
header('Content-Type: application/json');
require_once '../admin/conn.php';
require_once '../helpers/functions.php';
require_once '../templates/functions.php';

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

if (!isset($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No action specified'
    ]);
    exit;
}

$action = $_POST['action'];

switch ($action) {
    case 'share':
        handleShareNote($conn, $user_id);
        break;
    case 'revoke':
        handleRevokeAccess($conn, $user_id);
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        exit;
}

function handleShareNote($conn, $user_id) {
    if (!isset($_POST['note_id']) || !isset($_POST['recipients'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    $note_id = intval($_POST['note_id']);
    $recipients = json_decode($_POST['recipients'], true);
    
    if (!is_array($recipients) || empty($recipients)) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid recipients provided'
        ]);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Note not found or you do not have permission to share it'
        ]);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE notes SET shared = 1 WHERE id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    
    $stmt = $conn->prepare("DELETE FROM shared_notes WHERE note_id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    
    $stmt = $conn->prepare("INSERT INTO shared_notes (note_id, shared_email, permission) VALUES (?, ?, ?)");
    
    $success = true;
    foreach ($recipients as $recipient) {
        $email = $recipient['email'];
        $permission = $recipient['permission'];
        
        if ($permission !== 'read' && $permission !== 'write') {
            $permission = 'read'; 
        }
        
        $stmt->bind_param("iss", $note_id, $email, $permission);
        if (!$stmt->execute()) {
            $success = false;
        }
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Note shared successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error sharing note with one or more recipients'
        ]);
    }
}

function handleRevokeAccess($conn, $user_id) {
    if (!isset($_POST['note_id']) || !isset($_POST['email'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }
    
    $note_id = intval($_POST['note_id']);
    $email = $_POST['email'];
    
    $stmt = $conn->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Note not found or you do not have permission to manage sharing'
        ]);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM shared_notes WHERE note_id = ? AND shared_email = ?");
    $stmt->bind_param("is", $note_id, $email);
    $stmt->execute();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM shared_notes WHERE note_id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] === 0) {
        $stmt = $conn->prepare("UPDATE notes SET shared = 0 WHERE id = ?");
        $stmt->bind_param("i", $note_id);
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Access revoked successfully'
    ]);
}
?>
