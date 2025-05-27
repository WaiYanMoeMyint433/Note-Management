// app.js - Comprehensive JavaScript file for Note-Taking App

document.addEventListener('DOMContentLoaded', function () {
    // Initialize Quill editor
    let quill = null;
    const editorContainer = document.getElementById('editor-container');
    if (editorContainer) {
        quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        ['bold', 'italic', 'underline'],
                        ['image']
                    ],
                    handlers: {
                        image: imageHandler
                    }
                },
            },
            placeholder: 'Start writing your note...',
        });

        // Set saved content if editing a note
        const savedContent = document.getElementById('quillContent');
        if (savedContent && savedContent.value) {
            quill.root.innerHTML = decodeHTMLEntities(savedContent.value);
        }
    }

    // Helper function to decode HTML entities
    function decodeHTMLEntities(text) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = text;
        return textarea.value;
    }

    function imageHandler() {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();

        input.onchange = () => {
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);
            formData.append('csrf_token', document.getElementById('csrfToken').value);

            fetch('../dashboard/upload_image.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const range = quill.getSelection();
                        quill.insertEmbed(range.index, 'image', result.url);
                    } else {
                        alert('Image upload failed: ' + result.error);
                    }
                })
                .catch(err => {
                    console.error('Upload error:', err);
                    alert('Image upload error');
                });
        };
    }

    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 640 &&
                !sidebar.contains(e.target) &&
                e.target !== menuToggle &&
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // View toggle (grid/list)
    const viewToggleBtn = document.getElementById('viewToggleBtn');
    const viewIcon = document.getElementById('viewIcon');
    const noteContainers = document.querySelectorAll('.grid');

    if (viewToggleBtn && viewIcon) {
        viewToggleBtn.addEventListener('click', function () {
            const mainContent = document.querySelector('.main');

            if (viewIcon.classList.contains('fa-th-large')) {
                // Switch to list view
                viewIcon.classList.remove('fa-th-large');
                viewIcon.classList.add('fa-list');
                mainContent.classList.add('list-view');
            } else {
                // Switch to grid view
                viewIcon.classList.remove('fa-list');
                viewIcon.classList.add('fa-th-large');
                mainContent.classList.remove('list-view');
            }
        });
    }

    // Initialize labels array from existing labels
    let labels = [];
    const noteId = document.getElementById('noteId');

    if (noteId && noteId.value !== "0") {
        const labelContainer = document.getElementById('labelContainer');
        if (labelContainer) {
            labels = Array.from(labelContainer.querySelectorAll('span')).map(span => {
                return span.textContent.replace(/[\n\r×]/g, '').trim();
            });
        }
    }

    localStorage.setItem('noteLabels', JSON.stringify(labels));

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchInputTop = document.getElementById('searchInputTop');
    const clearSearch = document.getElementById('clearSearch');
    const noteCards = document.querySelectorAll('.note-card');
    const noNotesMessage = document.getElementById('noNotesMessage');

    function setupSearch(input, topSearch = false) {
        if (!input) return;

        input.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();

            // Sync the other search input if present
            if (topSearch && searchInputTop) {
                searchInputTop.value = searchTerm;
            } else if (!topSearch && searchInput) {
                searchInput.value = searchTerm;
            }

            // Show/hide clear button
            if (clearSearch) {
                clearSearch.style.display = searchTerm ? 'block' : 'none';
            }

            // Filter notes
            filterNotes();
        });
    }

    setupSearch(searchInput);
    setupSearch(searchInputTop, true);

    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (searchInputTop) searchInputTop.value = '';
            this.style.display = 'none';

            // Reset filtering
            filterNotes();
        });
    }

    // Fix for Add Label Button
    const labelInput = document.getElementById('labelInput');  // Note this is 'labelInput', not 'newLabel'
    const addLabelBtn = document.getElementById('addLabelBtn');
    const labelContainer = document.getElementById('labelContainer');

    if (labelInput && addLabelBtn && labelContainer) {
        // Remove inline event handler if it exists
        if (addLabelBtn.hasAttribute('onclick')) {
            addLabelBtn.removeAttribute('onclick');
        }

        // Add proper event listener
        addLabelBtn.addEventListener('click', function () {
            addLabel();
        });

        // Also trigger on Enter key
        labelInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addLabel();
            }
        });
    }

    // Save note functionality
    const saveNoteBtn = document.getElementById('saveNoteBtn');

    if (saveNoteBtn) {
        saveNoteBtn.addEventListener('click', function () {
            saveNote();
        });
    }

    // Initialize Notes object
    window.Notes = {
        init: function () {
            // Fetch the labels when the page loads
            this.fetchLabels();
        },

        fetchLabels: function () {
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                document.getElementById('csrfToken')?.value;

            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('user_id', window.currentUserId);

            // Show loading
            showAutoSaveIndicator('Loading labels...', false);

            // Send AJAX request
            fetch('../dashboard/fetch_labels.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Render the labels
                        UI.renderLabels(data.labels);
                        showAutoSaveIndicator('Labels loaded', true);
                    } else {
                        console.error('Failed to fetch labels:', data.message);
                        showAutoSaveIndicator('Error loading labels', true);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAutoSaveIndicator('Error loading labels', true);
                });
        },

        renameLabel: function (labelText) {
            // Find the label item in the DOM
            const labelItem = document.querySelector(`.sidebar-item[data-label-text="${labelText.replace(/"/g, '\\"')}"]`);
            if (!labelItem) {
                console.error('Label item not found');
                return;
            }

            const labelId = labelItem.getAttribute('data-label-id');
            if (!labelId) {
                console.error('Label ID not found');
                return;
            }

            // Call the edit function
            editLabel(labelId, labelText);
        },

        deleteLabel: function (labelText) {
            // Find the label item in the DOM
            const labelItem = document.querySelector(`.sidebar-item[data-label-text="${labelText.replace(/"/g, '\\"')}"]`);
            if (!labelItem) {
                console.error('Label item not found');
                return;
            }

            const labelId = labelItem.getAttribute('data-label-id');
            if (!labelId) {
                console.error('Label ID not found');
                return;
            }

            // Call the delete function
            deleteLabel(labelId, labelText);
        },

        handleAction: function (action, noteId) {
            switch (action) {
                case 'edit':
                    window.location.href = 'edit_note.php?id=' + noteId;
                    break;
                case 'delete':
                    confirmDelete(noteId);
                    break;
                default:
                    console.error('Unknown action:', action);
            }
        },

        togglePin: function (noteId) {
            const noteElement = document.querySelector(`.note-card[data-id="${noteId}"]`);
            const isPinned = noteElement?.querySelector('.fa-thumbtack') !== null;
            togglePin(noteId, !isPinned);
        },

        togglePassword: function (noteId) {
            // Implement password toggle functionality
            console.log('Toggle password for note', noteId);
            // This would need to be implemented based on your app's requirements
        }
    };

    // Initialize UI object
    window.UI = {
        currentFilter: 'all',

        renderLabels: function (labels) {
            const labelList = document.getElementById('labelList');
            if (!labelList) return;

            labelList.innerHTML = '';

            labels.forEach(label => {
                const labelItem = document.createElement('div');
                // Add data attributes for easier selection
                labelItem.className = 'sidebar-item flex items-center justify-between p-3 cursor-pointer hover:bg-purple-50 rounded-lg';
                labelItem.setAttribute('data-label-id', label.id);
                labelItem.setAttribute('data-label-text', label.text);

                labelItem.innerHTML = `
                    <div class="flex items-center flex-1" onclick="UI.filterNotes('${label.text.replace(/'/g, "\\'")}')">
                        <i class="fas fa-tag w-6 h-6 mr-3 text-purple-600"></i>
                        <span class="text-base font-medium">${label.text}</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="Notes.renameLabel('${label.text.replace(/'/g, "\\'")}')" 
                                class="text-blue-600 hover:text-blue-800" title="Rename">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="Notes.deleteLabel('${label.text.replace(/'/g, "\\'")}')" 
                                class="text-red-600 hover:text-red-800" title="Delete">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                labelList.appendChild(labelItem);
            });
        },

        filterNotes: function (filter) {
            this.currentFilter = filter;

            // Update active state in sidebar
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            sidebarItems.forEach(item => {
                if ((item.dataset.label === filter) ||
                    (filter === 'all' && item.classList.contains('all-notes')) ||
                    (filter === 'Pinned' && item.classList.contains('pinned-notes'))) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });

            // Filter the notes
            filterNotes();
        },

        showAutoSaveIndicator: function (text, hide = false) {
            showAutoSaveIndicator(text, hide);
        }
    };

    // Start the Notes initialization
    if (window.currentUserId) {
        Notes.init();
    }

    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            const body = document.body;
            const isDark = body.classList.toggle('dark-theme');
            const theme = isDark ? 'dark' : 'light';
            localStorage.setItem('theme', theme);

            // Update note cards that don't have custom colors
            const savedNoteColor = localStorage.getItem('noteColor');
            const noteCards = document.querySelectorAll('.note-card');

            noteCards.forEach(card => {
                if (!savedNoteColor) {
                    // Reset inline styles to use CSS variables
                    card.style.backgroundColor = '';
                    card.style.color = '';
                }
            });
        });
    }

    // Helper function to check if a color is dark (for custom note colors)
    function isColorDark(color) {
        if (!color) return false;
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        const brightness = (r * 299 + g * 587 + b * 114) / 1000;
        return brightness < 128;
    }

    // Update custom note colors if they exist
    const savedNoteColor = localStorage.getItem('noteColor');
    if (savedNoteColor) {
        const noteCards = document.querySelectorAll('.note-card');
        noteCards.forEach(card => {
            card.style.backgroundColor = savedNoteColor;

            // Determine if the color is dark and adjust text color
            if (isColorDark(savedNoteColor)) {
                card.style.color = '#e2e8f0'; // Light text for dark backgrounds
            } else {
                card.style.color = '#333333'; // Dark text for light backgrounds
            }
        });
    }

    // Load theme at startup
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }
});



