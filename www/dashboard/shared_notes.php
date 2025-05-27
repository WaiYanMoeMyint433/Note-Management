<?php
include "../admin/conn.php";
include "../helpers/functions.php";
include "./auth_check.php"; // Check if user is logged in



// Get user info
$user_id =  $_SESSION['user_id'];
$user_email = getUserEmail($conn, $user_id);

// Get notes shared with this user
function getSharedNotes($conn, $email) {
    $stmt = $conn->prepare("
        SELECT n.*, s.permission, s.shared_at, u.name as owner_name, u.email as owner_email
        FROM notes n
        JOIN shared_notes s ON n.id = s.note_id
        JOIN users u ON n.user_id = u.id
        WHERE s.shared_email = ?
        ORDER BY s.shared_at DESC
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notes = array();
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    
    return $notes;
}

// Get the user's email
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

// Get all notes shared with the user
$shared_notes = getSharedNotes($conn, $user_email);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Notes - Note-Taking App</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Quill.js CDN -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/app.css">
</head>

<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">
    <!-- Mobile Menu Toggle -->
    <button id="menuToggle" class="sm:hidden fixed top-4 left-4 z-30 p-2 bg-purple-600 text-white rounded">
        ☰
    </button>

    <!-- Sidebar -->
    <div class="sidebar fixed top-0 left-0 w-64 bg-white shadow-lg h-full p-6 flex flex-col z-10">
        <div class="text-2xl font-bold text-purple-600 mb-6">Notes</div>
        
        <div class="sidebar-item flex items-center p-3 cursor-pointer hover:bg-purple-50 rounded-lg" onclick="window.location.href='index.php'">
            <i class="fas fa-notes-medical w-6 h-6 mr-3 text-purple-600"></i>
            <span class="text-base font-medium">My Notes</span>
        </div>
        
        <div class="sidebar-item flex items-center p-3 cursor-pointer hover:bg-purple-50 rounded-lg active bg-purple-50">
            <i class="fas fa-share-alt w-6 h-6 mr-3 text-purple-600"></i>
            <span class="text-base font-medium">Shared with Me</span>
        </div>
        
        <div class="sidebar-item flex items-center p-3 cursor-pointer hover:bg-purple-50 rounded-lg" onclick="window.location.href='preferences.php'">
            <i class="fas fa-cog w-6 h-6 mr-3 text-purple-600"></i>
            <span class="text-base font-medium">Preferences</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main flex-1 ml-0 sm:ml-64 p-6">
        <div class="content max-w-7xl mx-auto">
            <!-- Top Bar -->
            <div class="top-bar flex flex-col sm:flex-row items-center justify-center sm:justify-between mb-8 gap-4">
                <div class="text-3xl font-bold text-purple-600">Shared Notes</div>
                <div class="flex items-center gap-4">
                    <button onclick="window.location.href='index.php'" class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Back to My Notes
                    </button>
                    <!-- User Menu -->
                    <button onclick="window.location.href='../auth/logout.php'" class="p-2 bg-red-600 text-white rounded">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            </div>

            <!-- Notes Layout -->
            <div class="notes-container">
                <?php if (empty($shared_notes)): ?>
                    <div class="text-center py-8 text-gray-500 italic">
                        <p>No notes have been shared with you yet.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($shared_notes as $note): ?>
                            <?php $labels = getNoteLabelsById($conn, $note['id'], $note['user_id']); ?>
                            <div class="note-card bg-white p-6 rounded-lg shadow-md hover:shadow-lg relative">
                                <div class="status-icons">
                                    <i class="fas fa-share-alt status-icon" title="Shared with you"></i>
                                    <?php if ($note['permission'] === 'write'): ?>
                                        <i class="fas fa-edit status-icon text-green-600" title="You can edit"></i>
                                    <?php else: ?>
                                        <i class="fas fa-eye status-icon text-blue-600" title="Read only"></i>
                                    <?php endif; ?>
                                    <?php if ($note['password']): ?>
                                        <i class="fas fa-lock status-icon" title="Password Protected"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="note-title text-lg font-semibold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($note['title']); ?>
                                </div>
                                
                                <div class="note-content prose max-w-none text-gray-600 mb-3 line-clamp-3">
                                    <?php echo strip_tags(substr($note['content'], 0, 150)) . (strlen($note['content']) > 150 ? '...' : ''); ?>
                                </div>
                                
                                <div class="shared-info text-sm text-gray-500 mb-2">
                                    <div><i class="fas fa-user mr-1"></i> Shared by: <?php echo htmlspecialchars($note['owner_name']); ?></div>
                                    <div><i class="fas fa-clock mr-1"></i> Shared on: <?php echo date('M j, Y', strtotime($note['shared_at'])); ?></div>
                                </div>
                                
                                <div class="note-labels flex flex-wrap gap-2 mb-3">
                                    <?php foreach ($labels as $label): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                                            <?php echo htmlspecialchars($label['label_text']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="note-actions flex gap-2 flex-wrap">
                                    <?php if ($note['permission'] === 'write'): ?>
                                        <button class="p-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm"
                                                onclick="window.location.href='view_shared_note.php?id=<?php echo $note['id']; ?>&mode=edit'">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                                            onclick="window.location.href='view_shared_note.php?id=<?php echo $note['id']; ?>&mode=view'">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript files -->
    <script src="../js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Mobile menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }
            
            // Apply preferences
            const savedFontSize = localStorage.getItem('fontSize') || 'text-base';
            const savedNoteColor = localStorage.getItem('noteColor') || '#ffffff';
            const savedTheme = localStorage.getItem('theme') || 'light';

            // Apply font size to the body or specific elements
            document.body.classList.add(savedFontSize);

            // Apply note color to note cards
            const noteCards = document.querySelectorAll('.note-card');
            noteCards.forEach(card => {
                card.style.backgroundColor = savedNoteColor;
            });

            // Apply theme (dark or light)
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
            } else {
                document.body.classList.remove('dark-theme');
            }
        });
    </script>
</body>

</html>