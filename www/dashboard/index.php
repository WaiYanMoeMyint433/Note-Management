<?php
// index.php

include "../admin/conn.php";
include "../helpers/functions.php";
include "./auth_check.php"; // Check if user is logged in

// Get user info
$user_id =  $_SESSION['user_id']; // Use session value in production
$notes = getNotes($conn, $user_id);
$note_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$all_labels = getLabels($conn, $user_id);
$note_labels = getNoteLabelsById($conn, $note_id, $user_id);

// Validate database connection
if (!$conn) {
    die("Database connection failed.");
}

// Fetch note only if note_id is valid
$note = $note_id > 0 ? getNoteById($conn, $note_id, $user_id) : null;
if ($note_id > 0 && !$note) {
    die("Note not found.");
}

$stmt = $conn->prepare("SELECT activation FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note-Taking App</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Quill.js CDN -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="../css/modal-note.css">
</head>

<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">
    <!-- Pass user ID to JavaScript -->
    <script>
        window.currentUserId = <?php echo json_encode($user_id); ?>;
    </script>

    <!-- Mobile Menu Toggle -->
    <button id="menuToggle" class="sm:hidden fixed top-4 left-4 z-30 p-2 bg-purple-600 text-white rounded">
        ☰
    </button>

    <!-- Add this line: Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar fixed top-0 left-0 w-64 bg-white shadow-lg h-full p-6 flex flex-col z-10">
        <div class="text-2xl font-bold text-purple-600 mb-6">Notes</div>
        <div class="sidebar-item flex items-center p-3 cursor-pointer hover:bg-purple-50 rounded-lg" onclick="window.location.href='shared_notes.php'">
            <i class="fas fa-share-alt w-6 h-6 mr-3 text-purple-600"></i>
            <span class="text-base font-medium">Shared with Me</span>
        </div>
        <!-- Search Bar -->
        <div class="mb-4 relative">
            <input type="text" id="searchInput"
                class="w-full p-2 pl-8 pr-8 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600"
                placeholder="Search notes...">
            <i class="fas fa-search absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <button id="clearSearch" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hidden">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="sidebar-item flex items-center p-3 cursor-pointer hover:bg-purple-50 rounded-lg active" onclick="UI.filterNotes('all')">
            <i class="fas fa-notes-medical w-6 h-6 mr-3 text-purple-600"></i>
            <span class="text-base font-medium">All Notes</span>
        </div>

        <div class="sidebar-item flex items-center p-3 cursor-pointer hover:bg-purple-50 rounded-lg" onclick="UI.filterNotes('Pinned')">
            <i class="fas fa-thumbtack w-6 h-6 mr-3 text-purple-600"></i>
            <span class="text-base font-medium">Pinned</span>
        </div>

        <!-- Labels Section -->
        <div class="mt-4 mb-2 text-sm font-semibold text-gray-500 uppercase">Labels</div>
        <div id="labelList" class="flex-1 overflow-y-auto">
            <?php if ($all_labels): ?>
                <?php foreach ($all_labels as $label): ?>
                    <div class="flex items-center justify-between p-3 cursor-pointer hover:bg-purple-50 rounded-lg"
                        data-label="<?php echo htmlspecialchars($label['label_text']); ?>"
                        data-label-id="<?php echo $label['id']; ?>">
                        <div class="flex items-center flex-1" onclick="filterNotes('<?php echo htmlspecialchars($label['label_text']); ?>')">
                            <i class="fas fa-tag w-6 h-6 mr-3 text-purple-600"></i>
                            <span class="text-base font-medium label-text"><?php echo htmlspecialchars($label['label_text']); ?></span>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="editLabel(this)"
                                class="text-blue-600 hover:text-blue-800 edit-btn" title="Rename">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteLabel(<?php echo $label['id']; ?>)"
                                class="text-red-600 hover:text-red-800 delete-btn" title="Delete">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-gray-500 text-sm p-3">No labels yet</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main flex-1 ml-0 sm:ml-64 p-6">
        <div class="content max-w-7xl mx-auto">
            <!-- Top Bar -->
            <div class="top-bar flex flex-col sm:flex-row items-center justify-center sm:justify-between mb-8 gap-4">
                <!-- Title section - always visible -->
                <div class="top-bar-title-section w-full sm:w-auto flex items-center justify-between">
                    <div class="text-3xl font-bold text-purple-600">Note-Taking App</div>
                </div>

                <!-- Actions section -->
                <div class="top-bar-actions flex items-center gap-4 w-full sm:w-auto">
                    <!-- Search - hidden on smallest screens -->
                    <div class="search-container flex-1 sm:flex-none hidden xs:block">
                        <input type="text" id="searchInputTop"
                            class="search-bar w-full sm:w-64 p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600"
                            placeholder="Search notes...">
                    </div>

                    <!-- Main action buttons -->
                    <button class="action-btn add-btn p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                        onclick="<?php echo $note_id == 0 ? 'toggleNoteForm()' : 'window.location.href=\'index.php\''; ?>">
                        <i class="fas fa-<?php echo $note_id == 0 ? 'plus' : 'list'; ?> mr-1"></i>
                        <span class="hide-on-mobile"><?php echo $note_id == 0 ? 'Add Note' : 'View All'; ?></span>
                    </button>

                    <!-- View toggle - hidden on mobile -->
                    <button id="viewToggleBtn" class="p-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition hide-on-mobile">
                        <i id="viewIcon" class="fas fa-th-large"></i>
                    </button>

                    <button id="themeToggleBtn" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-purple-100">
                        <span class="inline-flex w-5 justify-center mr-2">
                            <i class="fas fa-moon"></i>
                            <i class="fas fa-sun hidden"></i>
                        </span>
                    </button>

                    <!-- Settings menu - combines remaining options -->
                    <div class="settings-menu relative">
                        <button id="settingsToggle" class="p-2 bg-gray-200 text-gray-800 rounded">
                            <i class="fas fa-cog"></i>
                        </button>

                        <!-- Settings dropdown -->
                        <div id="settingsDropdown" class="settings-dropdown hidden">
                            <!-- Preferences -->
                            <a href="preferences.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-100">
                                <i class="fas fa-cog mr-2"></i> Preferences
                            </a>

                            <!-- Logout -->
                            <a href="../auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Message -->

            <?php if (isset($_SESSION['status'])): ?>
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    <?php echo htmlspecialchars($_SESSION['status']); ?>
                </div>
                <?php unset($_SESSION['status']); ?>
            <?php endif; ?>

            <!-- activation alert -->

            <?php if ($user["activation"] === 0): ?>
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">
                    <?php echo "Please activate your account"; ?>
                </div>
            <?php endif; ?>


            <!-- Note Form Container (for creating/editing notes) -->
            <div class="note-form-container bg-white rounded-lg shadow-md p-6 mb-8 <?php echo $note_id == 0 ? 'hidden' : ''; ?>" id="noteFormContainer">
                <h4 class="text-xl font-semibold text-gray-800 mb-4"><?php echo $note_id > 0 ? 'Edit Note' : 'New Note'; ?></h4>
                <form id="noteForm">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" id="userId" />
                    <input type="hidden" name="note_id" value="<?php echo $note_id; ?>" id="noteId" />
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>" id="csrfToken" />

                    <input type="text" class="w-full p-3 mb-4 border-b border-gray-300 focus:outline-none focus:border-purple-600 text-lg"
                        id="noteTitle" placeholder="Note title" aria-label="Note title"
                        value="<?php echo $note ? htmlspecialchars($note['title']) : ''; ?>" />

                    <input type="hidden" id="quillContent" value="<?php echo $note ? htmlspecialchars($note['content'], ENT_QUOTES) : ''; ?>" />
                    <div id="editor-container" class="mb-4 border border-gray-300 rounded-lg"></div>

                    <div class="mt-3">
                        <div class="flex gap-2 mb-3">
                            <input type="text" class="flex-1 p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600"
                                id="labelInput" placeholder="Add label..." aria-label="Add label" />
                            <button type="button" class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition" id="addLabelBtn" onclick="addLabel()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div id="labelContainer" class="flex flex-wrap gap-2 mb-4">
                            <?php foreach ($note_labels as $label): ?>
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm flex items-center">
                                    <?php echo htmlspecialchars($label['label_text']); ?>
                                    <button type="button" class="ml-2 text-purple-800 hover:text-purple-900" onclick="removeLabel(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </span>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition" id="saveNoteBtn">
                                <i class="fas fa-save mr-1"></i> Save
                            </button>
                            <button type="button" class="p-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition" onclick="window.location.href='index.php'">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Notes Layout -->
            <div class="notes-container <?php echo $note_id > 0 ? 'hidden' : ''; ?>" id="notesContainer">
                <!-- No Notes Message -->
                <?php if (count($notes) == 0): ?>
                    <div class="text-center py-8 text-gray-500 italic" id="noNotesMessage">
                        <p>No notes yet. Click "Add Note" to create your first note.</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500 italic hidden" id="noNotesMessage">
                        <p>No notes found matching your search criteria.</p>
                    </div>
                    <!-- Share Note Modal -->
                    <div id="shareNoteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                        <div class="bg-white rounded-lg p-6 w-full max-w-md">
                            <h3 class="text-lg font-bold mb-4">Share Note</h3>
                            <div id="shareForm">
                                <div class="mb-4">
                                    <label for="shareEmail" class="block text-gray-700 mb-2">Share with (email):</label>
                                    <input type="email" id="shareEmail" class="w-full p-2 border border-gray-300 rounded mb-2" placeholder="Enter email address">
                                    <div id="emailError" class="text-red-500 text-sm hidden">Please enter a valid email address</div>
                                    <button id="addEmailBtn" class="mt-2 px-3 py-1 bg-purple-600 text-white rounded hover:bg-purple-700 transition">
                                        <i class="fas fa-plus mr-1"></i> Add
                                    </button>
                                </div>

                                <div id="recipientsList" class="mb-4 max-h-40 overflow-y-auto">
                                    
                                    <div class="text-gray-500 italic text-sm">No recipients added yet</div>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 mb-2">Permission:</label>
                                    <div class="flex gap-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="sharePermission" value="read" class="mr-2" checked>
                                            <span>Read only</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="sharePermission" value="write" class="mr-2">
                                            <span>Can edit</span>
                                        </label>
                                    </div>
                                </div>

                                <input type="hidden" id="shareNoteId">

                                <div class="flex justify-end gap-3">
                                    <button id="cancelShareBtn" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition">
                                        Cancel
                                    </button>
                                    <button id="saveShareBtn" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">
                                        Share
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Pinned Notes -->
                    <?php
                    $pinnedNotes = array_filter($notes, function ($note) {
                        return $note['pinned'] == 1;
                    });

                    if (count($pinnedNotes) > 0):
                    ?>
                        <div id="pinnedNotes" class="mb-8">
                            <div class="text-lg font-semibold text-gray-700 mb-4">Pinned</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($pinnedNotes as $note): ?>
                                    <?php $labels = getNoteLabelsById($conn, $note['id'], $user_id); ?>
                                    <div class="note-card bg-white rounded-lg shadow-md hover:shadow-lg relative"
                                        data-id="<?php echo $note['id']; ?>"
                                        data-labels='<?php echo htmlspecialchars(json_encode(array_column($labels, 'label_text'))); ?>'>

                                        <div class="status-icons">
                                            <?php if ($note['password']): ?>
                                                <i class="fas fa-lock status-icon" title="Password Protected"></i>
                                            <?php endif; ?>
                                            <?php if ($note['shared']): ?>
                                                <i class="fas fa-share-alt status-icon" title="Shared"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="note-title text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($note['title']); ?></div>

                                        <?php
                                        $firstImage = extractFirstImage($note['content']);
                                        if ($firstImage):
                                        ?>
                                            <div class="note-image overflow-hidden">
                                                <?php echo $firstImage; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="note-content prose max-w-none text-gray-600 line-clamp-3">
                                            <?php echo strip_tags(substr($note['content'], 0, 150)) . (strlen($note['content']) > 150 ? '...' : ''); ?>
                                        </div>

                                        <div class="note-labels flex flex-wrap gap-2">
                                            <?php foreach ($labels as $label): ?>
                                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm"><?php echo htmlspecialchars($label['label_text']); ?></span>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="note-actions">
                                            <!-- Date moved into action area -->
                                            <div class="note-date text-sm text-gray-500"><?php echo date('M j, Y', strtotime($note['update_time'])); ?></div>

                                            <!-- Container for the actual buttons -->
                                            <div class="note-buttons">

                                                <div class="note-more-actions">
                                                    <button class="note-btn more" title="More actions">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="note-dropdown">
                                                        <!-- Pin/Unpin toggle -->
                                                        <?php if ($note['pinned'] == 1): ?>
                                                            <button class="note-dropdown-item pin" onclick="togglePin(<?php echo $note['id']; ?>, 0)">
                                                                <i class="fas fa-thumbtack"></i> Unpin
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="note-dropdown-item pin" onclick="togglePin(<?php echo $note['id']; ?>, 1)">
                                                                <i class="fas fa-thumbtack"></i> Pin
                                                            </button>
                                                        <?php endif; ?>

                                                        <!-- Share button -->
                                                        <button class="note-dropdown-item share share-note-btn" data-id="<?php echo $note['id']; ?>">
                                                            <i class="fas fa-share-alt"></i> Share
                                                        </button>

                                                        <!-- Lock/Unlock button -->
                                                        <button class="note-dropdown-item lock lock-note-btn" data-id="<?php echo $note['id']; ?>">
                                                            <i class="fas <?php echo !empty($note['password']) ? 'fa-lock' : 'fa-unlock'; ?>"></i>
                                                            <?php echo !empty($note['password']) ? 'Unlock' : 'Lock'; ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Other Notes -->
                    <?php
                    $otherNotes = array_filter($notes, function ($note) {
                        return $note['pinned'] != 1;
                    });

                    if (count($otherNotes) > 0):
                    ?>
                        <div id="otherNotes">
                            <div class="text-lg font-semibold text-gray-700 mb-4">Others</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($otherNotes as $note): ?>
                                    <?php $labels = getNoteLabelsById($conn, $note['id'], $user_id); ?>
                                    <div class="note-card bg-white rounded-lg shadow-md hover:shadow-lg relative"
                                        data-id="<?php echo $note['id']; ?>"
                                        data-labels='<?php echo htmlspecialchars(json_encode(array_column($labels, 'label_text'))); ?>'>

                                        <div class="status-icons">
                                            <?php if ($note['password']): ?>
                                                <i class="fas fa-lock status-icon" title="Password Protected"></i>
                                            <?php endif; ?>
                                            <?php if ($note['shared']): ?>
                                                <i class="fas fa-share-alt status-icon" title="Shared"></i>
                                            <?php endif; ?>
                                        </div>

                                        <div class="note-title text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($note['title']); ?></div>

                                        <?php
                                        $firstImage = extractFirstImage($note['content']);
                                        if ($firstImage):
                                        ?>
                                            <div class="note-image overflow-hidden">
                                                <?php echo $firstImage; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="note-content prose max-w-none text-gray-600 line-clamp-3">
                                            <?php echo strip_tags(substr($note['content'], 0, 150)) . (strlen($note['content']) > 150 ? '...' : ''); ?>
                                        </div>

                                        <div class="note-labels flex flex-wrap gap-2">
                                            <?php foreach ($labels as $label): ?>
                                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm"><?php echo htmlspecialchars($label['label_text']); ?></span>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="note-actions">
                                            <!-- Date moved into action area -->
                                            <div class="note-date text-sm text-gray-500"><?php echo date('M j, Y', strtotime($note['update_time'])); ?></div>

                                            <!-- Container for the actual buttons -->
                                            <div class="note-buttons">


                                                <div class="note-more-actions">
                                                    <button class="note-btn more" title="More actions">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="note-dropdown">
                                                        <!-- Pin/Unpin toggle -->
                                                        <?php if ($note['pinned'] == 1): ?>
                                                            <button class="note-dropdown-item pin" onclick="togglePin(<?php echo $note['id']; ?>, 0)">
                                                                <i class="fas fa-thumbtack"></i> Unpin
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="note-dropdown-item pin" onclick="togglePin(<?php echo $note['id']; ?>, 1)">
                                                                <i class="fas fa-thumbtack"></i> Pin
                                                            </button>
                                                        <?php endif; ?>

                                                        <!-- Share button -->
                                                        <button class="note-dropdown-item share share-note-btn" data-id="<?php echo $note['id']; ?>">
                                                            <i class="fas fa-share-alt"></i> Share
                                                        </button>

                                                        <!-- Lock/Unlock button -->
                                                        <button class="note-dropdown-item lock lock-note-btn" data-id="<?php echo $note['id']; ?>">
                                                            <i class="fas <?php echo !empty($note['password']) ? 'fa-lock' : 'fa-unlock'; ?>"></i>
                                                            <?php echo !empty($note['password']) ? 'Unlock' : 'Lock'; ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Dialog -->
    <div id="confirmOverlay" class="confirm-overlay" style="display: none;"></div>
    <div id="confirmDialog" class="confirm-dialog" style="display: none;">
        <h3 class="text-lg font-bold mb-4">Confirm Action</h3>
        <p id="confirmMessage" class="mb-6">Are you sure you want to perform this action?</p>
        <div class="flex justify-end gap-3">
            <button id="confirmCancel" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition">
                <i class="fas fa-times mr-1"></i> Cancel
            </button>
            <button id="confirmOk" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                <i class="fas fa-check mr-1"></i> Confirm
            </button>
        </div>
    </div>

    <!-- Auto-save indicator -->
    <div id="autoSaveIndicator" class="auto-save-indicator">
        <i class="fas fa-save mr-2"></i><span id="autoSaveText">Saving...</span>
    </div>

    <!-- Password Modal with inline styles -->
    <div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 9999;">
        <div class="bg-white rounded-lg p-6 w-full max-w-md" style="background-color: white; border-radius: 0.5rem; padding: 1.5rem; width: 100%; max-width: 28rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <h3 id="passwordModalTitle" class="text-lg font-bold mb-4" style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem; color: #333;">Note Password</h3>

            <!-- Verify Password Form -->
            <div id="verifyPasswordForm" style="display: block;">
                <p class="text-gray-600 mb-4" style="color: #718096; margin-bottom: 1rem;">This note is password protected. Enter the password to continue.</p>
                <input type="password" id="notePassword" class="w-full p-2 border border-gray-300 rounded mb-4" placeholder="Enter password" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; margin-bottom: 1rem;">
                <div class="flex justify-end gap-3" style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button id="cancelPasswordBtn" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition" style="padding: 0.5rem 1rem; background-color: #e5e7eb; border-radius: 0.25rem; cursor: pointer;">
                        Cancel
                    </button>
                    <button id="submitPasswordBtn" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition" style="padding: 0.5rem 1rem; background-color: #9333ea; color: white; border-radius: 0.25rem; cursor: pointer;">
                        Submit
                    </button>
                </div>
            </div>

            <!-- Set Password Form -->
            <div id="setPasswordForm" style="display: none;">
                <p class="text-gray-600 mb-4" style="color: #718096; margin-bottom: 1rem;">Set a password to protect this note.</p>
                <input type="password" id="newNotePassword" class="w-full p-2 border border-gray-300 rounded mb-2" placeholder="Enter new password" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; margin-bottom: 0.5rem;">
                <input type="password" id="confirmNotePassword" class="w-full p-2 border border-gray-300 rounded mb-4" placeholder="Confirm new password" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; margin-bottom: 1rem;">
                <div class="flex justify-end gap-3" style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button id="cancelSetPasswordBtn" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition" style="padding: 0.5rem 1rem; background-color: #e5e7eb; border-radius: 0.25rem; cursor: pointer;">
                        Cancel
                    </button>
                    <button id="savePasswordBtn" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition" style="padding: 0.5rem 1rem; background-color: #9333ea; color: white; border-radius: 0.25rem; cursor: pointer;">
                        Save Password
                    </button>
                </div>
            </div>

            <!-- Remove Password Form -->
            <div id="removePasswordForm" style="display: none;">
                <p class="text-gray-600 mb-4" style="color: #718096; margin-bottom: 1rem;">Enter the current password to remove protection.</p>
                <input type="password" id="currentPassword" class="w-full p-2 border border-gray-300 rounded mb-4" placeholder="Enter current password" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; margin-bottom: 1rem;">
                <div class="flex justify-end gap-3" style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button id="cancelRemovePasswordBtn" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition" style="padding: 0.5rem 1rem; background-color: #e5e7eb; border-radius: 0.25rem; cursor: pointer;">
                        Cancel
                    </button>
                    <button id="removePasswordBtn" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition" style="padding: 0.5rem 1rem; background-color: #dc2626; color: white; border-radius: 0.25rem; cursor: pointer;">
                        Remove Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Note Modal -->
    <div id="noteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div id="noteModalContent" class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-auto"
            onclick="event.stopPropagation()">

            <!-- Modal header with close button -->
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">New Note</h3>
                <button id="closeNoteModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Note form without save/cancel buttons -->
            <input type="hidden" id="modalNoteId" value="0">
            <input type="hidden" id="modalUserId" value="<?php echo $user_id; ?>">
            <input type="hidden" id="modalCsrfToken" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

            <input type="text"
                class="w-full p-3 mb-4 border-b border-gray-300 focus:outline-none focus:border-purple-600 text-lg"
                id="modalNoteTitle"
                placeholder="Note title"
                aria-label="Note title">

            <div id="modal-editor-container" class="mb-4 border border-gray-300 rounded-lg min-h-[200px]"></div>

            <!-- Labels Section -->
            <div class="mt-3">
                <div class="flex gap-2 mb-3">
                    <input type="text"
                        class="flex-1 p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-600"
                        id="modalLabelInput"
                        placeholder="Add label..."
                        aria-label="Add label">
                    <button type="button"
                        class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                        id="addModalLabelBtn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <div id="modalLabelContainer" class="flex flex-wrap gap-2 mb-4">
                    <!-- Labels will be added here dynamically -->
                </div>
            </div>

            <!-- Auto-save status -->
            <div id="modalSaveStatus" class="text-sm text-gray-500 italic mt-2">
                Ready to edit
            </div>
        </div>
    </div>

    <!-- Password Modal for Protected Notes -->
    <div id="passwordPromptModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold mb-4">Protected Note</h3>
            <p class="mb-4">This note is password protected. Please enter the password to view it.</p>

            <input type="password" id="notePasswordInput"
                class="w-full p-3 mb-4 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-600"
                placeholder="Enter password">

            <input type="hidden" id="passwordProtectedNoteId" value="">

            <div class="flex justify-end gap-3">
                <button id="cancelPasswordBtn" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button id="submitPasswordBtn" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">
                    Submit
                </button>
            </div>
        </div>
    </div>





    <!-- JavaScript files -->
    <script src="../js/app.js"></script>
    <script src="../js/responsive.js"></script>
    <script src="../js/note-actions.js"></script>
    <script src="../js/note-functions.js"></script>
    <script src="../js/note-password.js"></script>
    <script src="../js/note-sharing.js"></script>
    <script src="../js/modal-note.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Retrieve preferences from localStorage
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


    <script>

    </script>
</body>

</html>