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

if (!isset($_GET['note_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing note ID'
    ]);
    exit;
}

$note_id = intval($_GET['note_id']);

$stmt = $conn->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $note_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Note not found or you do not have permission to access it'
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT shared_email, permission FROM shared_notes WHERE note_id = ?");
$stmt->bind_param("i", $note_id);
$stmt->execute();
$result = $stmt->get_result();

$shares = [];
while ($row = $result->fetch_assoc()) {
    $shares[] = $row;
}

echo json_encode([
    'success' => true,
    'shares' => $shares
]);
