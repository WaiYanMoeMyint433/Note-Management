<?php
// get_note_ajax.php 

// Start session
include "./auth_check.php";


try {
    
    require_once "../admin/conn.php";
    require_once "../helpers/functions.php";

    // Set JSON content type
    header('Content-Type: application/json');

    // Check for required parameters
    if (!isset($_GET['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing note ID']);
        exit;
    }

    // Get user ID from session
    $user_id = $_SESSION['user_id'] ?? 0;
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
        exit;
    }

    // Get note ID
    $note_id = intval($_GET['id']);
    
    
    $debug = [
        'user_id' => $user_id,
        'note_id' => $note_id,
        'session' => session_id()
    ];

   
    $note = getNoteById($conn, $note_id, $user_id);
    
    // Add to debug info
    $debug['note_found'] = ($note !== false);

    if (!$note) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Note not found or access denied',
            'debug' => $debug
        ]);
        exit;
    }

    // Try to get labels
    $labels = [];
    try {
        $rawLabels = getNoteLabelsById($conn, $note_id, $user_id);
        
       
        foreach ($rawLabels as $label) {
            $labels[] = [
                'id' => $label['id']?? null,
                'text' => $label['label_text']
            ];
        }
        
     
        $debug['labels_count'] = count($labels);
    } catch (Exception $e) {
        $debug['label_error'] = $e->getMessage();
    }

    // Create the response with escaped values
    $safeNote = [];
foreach ($note as $key => $value) {
    
    if ($key === 'content') {
        $safeNote[$key] = $value;
    } else {
        // Add null check or empty string fallback
        $safeNote[$key] = htmlspecialchars($value ?? '', ENT_NOQUOTES, 'UTF-8');
    }
}


    // Return success response
    echo json_encode([
        'status' => 'success',
        'note' => $safeNote,
        'labels' => $labels,
        'debug' => $debug
    ]);
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>