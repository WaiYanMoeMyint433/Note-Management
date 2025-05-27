<?php
// view_shared_note.php - Page to view or edit a shared note
include "../admin/conn.php";
include "../helpers/functions.php";
include "./auth_check.php"; // Check if user is logged in



// Get user info
$user_id = $_SESSION['user_id'];
$user_email = getUserEmail($conn, $user_id);

// Check if note ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid note ID";
    header('Location: shared_notes.php');
    exit;
}

$note_id = intval($_GET['id']);
$mode = isset($_GET['mode']) && $_GET['mode'] === 'edit' ? 'edit' : 'view';

// Get the shared note and verify access
$note = getSharedNoteById($conn, $note_id, $user_email);

if (!$note) {
    $_SESSION['error'] = "Note not found or you don't have access to it";
    header('Location: shared_notes.php');
    exit;
}

// If user wants to edit but only has read permission, restrict to view mode
if ($mode === 'edit' && $note['permission'] !== 'write') {
    $_SESSION['error'] = "You don't have permission to edit this note";
    header('Location: view_shared_note.php?id=' . $note_id . '&mode=view');
    exit;
}

// Get the note labels
$labels = getNoteLabelsById($conn, $note_id, $note['user_id']);

// Function to get user email
function getUserEmail($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['email'];
    }

    return null;
}

// Function to get a shared note by ID
function getSharedNoteById($conn, $note_id, $user_email)
{
    $stmt = $conn->prepare("
        SELECT n.*, s.permission, s.shared_at, u.name as owner_name, u.email as owner_email
        FROM notes n
        JOIN shared_notes s ON n.id = s.note_id
        JOIN users u ON n.user_id = u.id
        WHERE n.id = ? AND s.shared_email = ?
    ");
    $stmt->bind_param("is", $note_id, $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'edit' ? 'Edit' : 'View'; ?> Shared Note - Note-Taking App</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Quill.js CDN -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/app.css">
    <style>
        :root {
            --bg-light: #f9fafb;
            --bg-dark: #000;
            /* Pure black */
            --card-light: #ffffff;
            --card-dark: #111;
            /* Almost black for card */
            --text-light: #1f2937;
            --text-dark: #fafafa;
            /* Lighter text for contrast */
        }

        /* Quill dark mode override */
        .dark .ql-toolbar.ql-snow,
        .dark .ql-container.ql-snow {
            background: #23272f;
            color: #e5e7eb;
            border-color: #374151;
        }

        .dark .ql-editor {
            background: #23272f;
            color: #e5e7eb;
        }

        .dark .prose {
            color: #e5e7eb;
        }

        body.dark-theme {
            background-color: var(--bg-dark);
            color: var(--text-dark);
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen" id="body">
    <!-- Main Content -->
    <div class="w-full p-6">
        <div class="content max-w-4xl mx-auto">
            <!-- Top Bar -->
            <div class="top-bar flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <button onclick="window.location.href='shared_notes.php'"
                        class="p-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="text-2xl font-bold text-purple-600">
                        <?php echo $mode === 'edit' ? 'Edit' : 'View'; ?> Shared Note
                    </div>
                </div>
                <div>
                    <?php if ($mode === 'view' && $note['permission'] === 'write'): ?>
                        <button onclick="window.location.href='view_shared_note.php?id=<?php echo $note_id; ?>&mode=edit'"
                            class="p-2 bg-purple-600 text-white rounded">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>
                    <?php elseif ($mode === 'edit'): ?>
                        <button onclick="window.location.href='view_shared_note.php?id=<?php echo $note_id; ?>&mode=view'"
                            class="p-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                            <i class="fas fa-eye mr-1"></i> View
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Note Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="mb-4 pb-4 border-b border-gray-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">
                                <?php echo htmlspecialchars($note['title']); ?>
                            </h2>
                            <div class="text-sm text-gray-500 mt-2">
                                <div><i class="fas fa-user mr-1"></i> Shared by:
                                    <?php echo htmlspecialchars($note['owner_name']); ?>
                                    (<?php echo htmlspecialchars($note['owner_email']); ?>)</div>
                                <div><i class="fas fa-clock mr-1"></i> Shared on:
                                    <?php echo date('F j, Y, g:i a', strtotime($note['shared_at'])); ?></div>
                                <div>
                                    <i
                                        class="fas <?php echo $note['permission'] === 'write' ? 'fa-edit text-green-600' : 'fa-eye text-blue-600'; ?> mr-1"></i>
                                    Permission:
                                    <?php echo $note['permission'] === 'write' ? 'Can edit' : 'Read only'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex">
                            <?php if ($note['pinned']): ?>
                                <span
                                    class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm mr-2">
                                    <i class="fas fa-thumbtack mr-1"></i> Pinned
                                </span>
                            <?php endif; ?>
                            <?php if ($note['password']): ?>
                                <span
                                    class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                    <i class="fas fa-lock mr-1"></i> Protected
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($labels)): ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <?php foreach ($labels as $label): ?>
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                                    <?php echo htmlspecialchars($label['label_text']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($mode === 'edit'): ?>
                    <!-- Edit Mode -->
                    <form id="editNoteForm">
                        <input type="hidden" name="note_id" value="<?php echo $note_id; ?>" id="noteId">
                        <input type="hidden" name="shared_edit" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>"
                            id="csrfToken">

                        <input type="text"
                            class="w-full p-3 mb-4 border-b border-gray-300 focus:outline-none focus:border-purple-600 text-lg"
                            id="noteTitle" placeholder="Note title" aria-label="Note title"
                            value="<?php echo htmlspecialchars($note['title']); ?>" />

                        <input type="hidden" id="quillContent"
                            value="<?php echo htmlspecialchars($note['content'], ENT_QUOTES); ?>" />
                        <div id="editor-container" class="mb-4 border border-gray-300 rounded-lg"></div>

                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button"
                                class="p-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition"
                                onclick="window.location.href='view_shared_note.php?id=<?php echo $note_id; ?>&mode=view'">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </button>
                            <button type="button"
                                class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                                id="saveNoteBtn">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- View Mode -->
                    <div class="note-content prose max-w-none text-gray-700">
                        <?php echo $note['content']; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Auto-save indicator -->
    <div id="autoSaveIndicator" class="auto-save-indicator" style="display: none;">
        <i class="fas fa-save mr-2"></i><span id="autoSaveText">Saving...</span>
    </div>

    <!-- JavaScript -->
    <?php if ($mode === 'edit'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Initialize Quill
                const quill = new Quill('#editor-container', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    },
                    placeholder: 'Write your note here...'
                });

                // Load initial content
                const initialContent = document.getElementById('quillContent').value;
                if (initialContent) {
                    quill.root.innerHTML = initialContent;
                }

                // Auto-save functionality
                let saveTimer;
                const autoSaveDelay = 2000; // 2 seconds

                quill.on('text-change', function () {
                    // Show the indicator
                    const indicator = document.getElementById('autoSaveIndicator');
                    indicator.style.display = 'block';
                    document.getElementById('autoSaveText').textContent = 'Saving...';

                    // Clear existing timer
                    clearTimeout(saveTimer);

                    // Set a new timer
                    saveTimer = setTimeout(function () {
                        saveNote();
                    }, autoSaveDelay);
                });

                // Save button click
                document.getElementById('saveNoteBtn').addEventListener('click', function () {
                    saveNote(true);
                });

                // Function to save the note
                function saveNote(redirect = false) {
                    const noteId = document.getElementById('noteId').value;
                    const title = document.getElementById('noteTitle').value.trim();
                    const content = quill.root.innerHTML;

                    if (!title) {
                        alert('Please enter a title for the note');
                        return;
                    }

                    const indicator = document.getElementById('autoSaveIndicator');
                    indicator.style.display = 'block';

                    const formData = new FormData();
                    formData.append('note_id', noteId);
                    formData.append('title', title);
                    formData.append('content', content);
                    formData.append('shared_edit', '1');
                    formData.append('csrf_token', document.getElementById('csrfToken').value);

                    fetch('../api/save_shared_note.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('autoSaveText').textContent = 'Saved!';
                                setTimeout(function () {
                                    indicator.style.display = 'none';
                                }, 1000);

                                if (redirect) {
                                    window.location.href = 'view_shared_note.php?id=' + noteId + '&mode=view';
                                }
                            } else {
                                document.getElementById('autoSaveText').textContent = 'Error: ' + data.message;
                            }
                        })
                        .catch(error => {
                            console.error('Error saving note:', error);
                            document.getElementById('autoSaveText').textContent = 'Error saving note';
                        });
                }
            });
        </script>
    <?php endif; ?>
</body>
<script>
    function toggleTheme() {
        const body = document.getElementById('body');
        body.classList.toggle('dark-theme');
        localStorage.setItem('theme', body.classList.contains('dark-theme') ? 'dark' : 'light');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.getElementById('body').classList.add('dark-theme');
        }
    });
</script>

</html>