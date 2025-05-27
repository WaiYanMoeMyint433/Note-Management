<?php


// FUNCTIONS
// Function to extract the first image from HTML content
function extractFirstImage($html) {
    $pattern = '/<img[^>]+src="([^"]+)"[^>]*>/i';
    if (preg_match($pattern, $html, $matches)) {
        return $matches[0]; // Return the full img tag
    }
    return null;
}

function createNote($conn, $user_id, $title, $content, $password = null) {
    $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $content, $password);
    $stmt->execute();
    return ['status' => 'success', 'note_id' => $stmt->insert_id];
}

function updateNote($conn, $note_id, $user_id, $title, $content) {
    $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssii", $title, $content, $note_id, $user_id);
    $stmt->execute();
    return ['status' => 'success'];
}

function getNotes($conn, $user_id) {
    $stmt = $conn->prepare("SELECT 
    n.*
FROM 
    notes n

WHERE 
    n.user_id = ? ORDER BY n.update_time DESC ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getNoteById($conn, $note_id, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function deleteNote($conn, $note_id, $user_id) {
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    $stmt->execute();
    return ['status' => 'deleted'];
}

function shareNote($conn, $note_id, $emails) {
    $stmt1 = $conn->prepare("UPDATE notes SET shared = 1 WHERE id = ?");
    $stmt1->bind_param("i", $note_id);
    $stmt1->execute();

    foreach ($emails as $email) {
        $stmt2 = $conn->prepare("REPLACE INTO shared_notes (note_id, shared_email) VALUES (?, ?)");
        $stmt2->bind_param("is", $note_id, $email);
        $stmt2->execute();
    }
    return ['status' => 'shared'];
}

function unshareNote($conn, $note_id, $email) {
    $stmt = $conn->prepare("DELETE FROM shared_notes WHERE note_id = ? AND shared_email = ?");
    $stmt->bind_param("is", $note_id, $email);
    $stmt->execute();
    return ['status' => 'removed'];
}

function setPassword($conn, $note_id, $user_id, $password) {
    $stmt = $conn->prepare("UPDATE notes SET password = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $password, $note_id, $user_id);
    $stmt->execute();
    return ['status' => 'password_set'];
}

function removePassword($conn, $note_id, $user_id) {
    $stmt = $conn->prepare("UPDATE notes SET password = NULL WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    $stmt->execute();
    return ['status' => 'password_removed'];
}


function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getNoteLabelsById($conn, $note_id, $user_id) {
    $stmt = $conn->prepare("
        SELECT 
            l.id AS label_id,
            l.label_text,
            l.icon
        FROM 
            note_labels nl
        JOIN 
            labels l ON nl.label_id = l.id
        JOIN 
            notes n ON nl.note_id = n.id
        WHERE 
            nl.note_id = ? AND n.user_id = ?
    ");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $note_id, $user_id);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getLabels($conn) {
    $stmt = $conn->prepare("SELECT * FROM labels");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


?>
