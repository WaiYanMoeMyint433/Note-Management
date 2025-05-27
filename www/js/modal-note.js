// modal-note.js 

// Global variables for auto-save
let autoSaveTimer;
let contentChanged = false;
const AUTOSAVE_DELAY = 2000;

// Initialize modal note functionality
document.addEventListener('DOMContentLoaded', function () {
    // Initialize Quill editor inside the modal
    const modalEditorContainer = document.getElementById('modal-editor-container');
    if (modalEditorContainer) {
        const quill = new Quill('#modal-editor-container', {
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
    }

    // Set up event listeners for the modal
    const noteModal = document.getElementById('noteModal');
    const closeNoteBtn = document.getElementById('closeNoteModal');
    const addNoteBtn = document.querySelector('.add-btn');

    // Add note button
    if (addNoteBtn) {
        if (addNoteBtn.hasAttribute('onclick')) {
            addNoteBtn.removeAttribute('onclick');
        }

        // Add event listener for opening modal
        addNoteBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openNoteModal();
        });
    }

    // Note card click event for opening in modal
    document.addEventListener('click', function (e) {
        const noteCard = e.target.closest('.note-card');
        if (noteCard && !e.target.closest('.note-actions')) {
            const noteId = noteCard.dataset.id;

            // Check if note is password protected (has lock icon)
            const hasLockIcon = noteCard.querySelector('.fa-lock');

            if (hasLockIcon) {
                // Note is password protected, use the password verification system
                if (typeof PasswordProtection !== 'undefined' &&
                    typeof PasswordProtection.checkPasswordAndRun === 'function') {

                    PasswordProtection.checkPasswordAndRun(
                        noteId,
                        function () {
                            openNoteModal(noteId);
                        }
                    );
                } else {
                    alert('This note is password protected.');
                }
            } else {
                // Note is not password protected
                openNoteModal(noteId);
            }
        }
    });

    // Close button
    if (closeNoteBtn) {
        closeNoteBtn.addEventListener('click', function (e) {
            e.preventDefault();
            closeNoteModal();
        });
    }

    // Close on click outside
    if (noteModal) {
        noteModal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeNoteModal();
            }
        });
    }

    // Modal label functionality
    const modalLabelInput = document.getElementById('modalLabelInput');
    const addModalLabelBtn = document.getElementById('addModalLabelBtn');

    if (modalLabelInput && addModalLabelBtn) {
        addModalLabelBtn.addEventListener('click', function () {
            addModalLabel();
        });

        // Add label on enter key
        modalLabelInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addModalLabel();
            }
        });
    }

    setupAutoSave();

    // Keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        if (!noteModal || noteModal.classList.contains('hidden')) return;

        // Escape to close modal
        if (e.key === 'Escape') {
            closeNoteModal();
        }

        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveNote();
        }
    });
});

// Image handler for Quill editor 
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
        formData.append('csrf_token', document.getElementById('modalCsrfToken').value);

        updateSaveStatus('Uploading image...');

        fetch('../dashboard/upload_image.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const quill = Quill.find(document.getElementById('modal-editor-container'));
                    const range = quill.getSelection();
                    quill.insertEmbed(range.index, 'image', result.url);

                    // Trigger auto-save after image insert
                    contentChanged = true;
                    updateSaveStatus('Image added');


                    clearTimeout(autoSaveTimer);
                    autoSaveTimer = setTimeout(() => {
                        saveNote();
                    }, AUTOSAVE_DELAY);
                } else {
                    updateSaveStatus('Image upload failed');
                    alert('Image upload failed: ' + result.error);
                }
            })
            .catch(err => {
                console.error('Upload error:', err);
                updateSaveStatus('Image upload error');
                alert('Image upload error');
            });
    };
}

// Open Note Modal (create or edit)
function openNoteModal(noteId = 0) {
    const noteModal = document.getElementById('noteModal');
    const modalTitle = document.getElementById('modalTitle');
    const noteIdField = document.getElementById('modalNoteId');
    const noteTitleField = document.getElementById('modalNoteTitle');
    const modalLabelContainer = document.getElementById('modalLabelContainer');

    // Reset form state
    noteIdField.value = noteId;
    noteTitleField.value = '';
    modalLabelContainer.innerHTML = '';

    // Reset quill content
    const quill = Quill.find(document.getElementById('modal-editor-container'));
    quill.root.innerHTML = '';

    // Set modal title
    modalTitle.textContent = noteId > 0 ? 'Edit Note' : 'New Note';

    if (noteId > 0) {
        // Fetch note data for editing
        fetchNoteData(noteId);
    } else {
        // Clear local storage for new note
        localStorage.setItem('noteLabels', JSON.stringify([]));
        updateSaveStatus('Ready to edit');
    }

    // Show modal
    noteModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    setTimeout(() => {
        noteTitleField.focus();
    }, 100);

    contentChanged = false;
}

// Close Note Modal
function closeNoteModal() {
    const noteModal = document.getElementById('noteModal');
    const noteTitleField = document.getElementById('modalNoteTitle');
    const quill = Quill.find(document.getElementById('modal-editor-container'));

    // Check if there's content to save
    const title = noteTitleField.value.trim();
    const content = quill.getText().trim();

    if (title || content) {

        if (contentChanged) {
            saveNote().then(() => {
                finishClosingModal();
            }).catch(error => {
                console.error('Error saving note before closing:', error);
                finishClosingModal();
            });
        } else {
            finishClosingModal();
        }
    } else {
        // No content
        finishClosingModal();
    }
}

function finishClosingModal() {
    const noteModal = document.getElementById('noteModal');
    noteModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');


    clearTimeout(autoSaveTimer);

    // Refresh notes list 
    refreshNotesList();
}

// Fetch Note Data for editing
function fetchNoteData(noteId) {
    updateSaveStatus('Loading note...');

    // Log the request
    console.log(`Fetching note ID: ${noteId}`);


    const apiUrl = '../dashboard/get_note_ajax.php';

    fetch(`${apiUrl}?id=${noteId}`)
        .then(response => {
            console.log('Response status:', response.status);


            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }


            const contentType = response.headers.get('content-type');
            console.log('Content type:', contentType);


            return response.text();
        })
        .then(rawText => {
            // Log the raw response
            console.log('Raw response:', rawText);

            // Check if empty
            if (!rawText || rawText.trim() === '') {
                throw new Error('Empty response from server');
            }


            let data;
            try {
                data = JSON.parse(rawText);
                console.log('Parsed JSON:', data);
            } catch (e) {
                console.error('JSON parse error:', e);

                // Check for PHP errors in response
                if (rawText.includes('Fatal error') ||
                    rawText.includes('Parse error') ||
                    rawText.includes('Warning') ||
                    rawText.includes('Notice')) {

                    console.error('PHP error detected in response');
                    throw new Error('Server error: PHP error detected');
                }

                throw new Error('Invalid JSON response');
            }


            if (data.status === 'success' && data.note) {

                document.getElementById('modalNoteTitle').value = data.note.title || '';

                const quill = Quill.find(document.getElementById('modal-editor-container'));

                // Set editor content, safely handling HTML
                if (data.note.content) {
                    quill.root.innerHTML = data.note.content;
                } else {
                    quill.root.innerHTML = '';
                }

                // Add labels
                const labelContainer = document.getElementById('modalLabelContainer');
                labelContainer.innerHTML = '';

                if (data.labels && Array.isArray(data.labels)) {
                    data.labels.forEach(label => {
                        if (label && (label.text || label.label_text)) {
                            addLabelToModalContainer(label.text || label.label_text);
                        }
                    });
                }

                updateSaveStatus('Note loaded');
                return data;

            } else {

                console.error('API error:', data.message || 'Unknown error');
                throw new Error(data.message || 'Failed to load note');
            }
        })
        .catch(error => {
            console.error('Error fetching note:', error);
            updateSaveStatus('Error loading note');


            const errorMessage = error.message || 'Could not load the note. Please try again.';
            alert(`Error: ${errorMessage}`);


            const saveStatus = document.getElementById('saveStatus');
            if (saveStatus) {
                saveStatus.innerHTML = `
                    <span class="text-red-500">Error loading note</span>
                    <button id="retryButton" class="ml-3 text-blue-600 hover:text-blue-800 underline">Retry</button>
                    <button id="closeButton" class="ml-3 text-gray-600 hover:text-gray-800 underline">Close</button>
                `;

                // Add event listeners
                document.getElementById('retryButton')?.addEventListener('click', () => {
                    fetchNoteData(noteId);
                });

                document.getElementById('closeButton')?.addEventListener('click', () => {
                    closeNoteModal();
                });
            }
        });
}

// Helper function to add a label to the container
function addLabelToContainer(labelText) {
    if (!labelText || typeof labelText !== 'string') {
        console.warn('Invalid label:', labelText);
        return;
    }

    labelText = labelText.trim();
    if (!labelText) return;

    const labelContainer = document.getElementById('labelContainer');
    if (!labelContainer) {
        console.error('Label container not found');
        return;
    }

    // Create label element
    const labelElement = document.createElement('span');
    labelElement.className = 'px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm flex items-center';

    // Safely set text content
    labelElement.textContent = labelText;

    // Add remove button
    const removeButton = document.createElement('button');
    removeButton.className = 'ml-2 text-purple-800 hover:text-purple-900';
    removeButton.innerHTML = '<i class="fas fa-times"></i>';
    removeButton.addEventListener('click', function () {
        labelElement.remove();


        contentChanged = true;
        updateSaveStatus('Editing...');
        restartAutoSaveTimer();
    });

    labelElement.appendChild(removeButton);
    labelContainer.appendChild(labelElement);
}

setTimeout(() => {
    const saveStatus = document.getElementById('saveStatus');
    if (saveStatus) {
        const testLink = document.createElement('a');
        testLink.href = '#';
        testLink.className = 'ml-3 text-xs text-blue-600 hover:text-blue-800 underline';
        testLink.textContent = 'Test API';
        testLink.addEventListener('click', function (e) {
            e.preventDefault();
            testApiEndpoint();
        });
        saveStatus.appendChild(testLink);
    }
}, 500);



function setupAutoSave() {
    // Get editor instance
    const editorContainer = document.getElementById('modal-editor-container');
    if (!editorContainer) return;

    const quill = Quill.find(editorContainer);
    if (!quill) return;

    // Monitor content changes
    quill.on('text-change', function () {
        contentChanged = true;
        updateSaveStatus('Editing...');

        clearTimeout(autoSaveTimer);

        // Set new timer
        autoSaveTimer = setTimeout(() => {
            saveNote();
        }, AUTOSAVE_DELAY);
    });

    // Monitor title changes
    const noteTitle = document.getElementById('modalNoteTitle');
    if (noteTitle) {
        noteTitle.addEventListener('input', function () {
            contentChanged = true;
            updateSaveStatus('Editing...');

            clearTimeout(autoSaveTimer);

            // Set new timer
            autoSaveTimer = setTimeout(() => {
                saveNote();
            }, AUTOSAVE_DELAY);
        });
    }
}

// Update save status indicator
function updateSaveStatus(message) {
    const saveStatus = document.getElementById('modalSaveStatus');
    if (!saveStatus) return;

    saveStatus.textContent = message;

    // Add animation classes based on message
    saveStatus.classList.remove('text-gray-500', 'text-green-500', 'text-red-500', 'animate-pulse');

    if (message === 'Editing...') {
        saveStatus.classList.add('text-gray-500');
    } else if (message === 'Saving...') {
        saveStatus.classList.add('text-gray-500', 'animate-pulse');
    } else if (message.includes('Error')) {
        saveStatus.classList.add('text-red-500');
    } else if (message === 'All changes saved') {
        saveStatus.classList.add('text-green-500');
    } else {
        saveStatus.classList.add('text-gray-500');
    }
}

// Add label to modal
function addModalLabel() {
    const labelInput = document.getElementById('modalLabelInput');
    if (!labelInput) return;

    const labelText = labelInput.value.trim();
    if (!labelText) return;

    addLabelToModalContainer(labelText);

    labelInput.value = '';

    contentChanged = true;
    updateSaveStatus('Editing...');
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => saveNote(), AUTOSAVE_DELAY);
}

// Helper function to add a label to the container
function addLabelToModalContainer(labelText) {
    if (!labelText.trim()) return;

    const labelContainer = document.getElementById('modalLabelContainer');
    if (!labelContainer) return;

    // Get current labels
    let labels = JSON.parse(localStorage.getItem('noteLabels') || '[]');

    // Only add if not a duplicate
    if (!labels.includes(labelText)) {
        // Add to labels array
        labels.push(labelText);
        localStorage.setItem('noteLabels', JSON.stringify(labels));

        // Create label element
        const label = document.createElement('span');
        label.className = 'px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm flex items-center';
        label.textContent = labelText.trim();

        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.className = 'ml-2 text-purple-800 hover:text-purple-900';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.addEventListener('click', function () {
            // Remove from DOM
            label.remove();

            // Remove from labels array
            labels = labels.filter(l => l !== labelText);
            localStorage.setItem('noteLabels', JSON.stringify(labels));

            // Trigger auto-save
            contentChanged = true;
            updateSaveStatus('Editing...');
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => saveNote(), AUTOSAVE_DELAY);
        });

        label.appendChild(removeBtn);
        labelContainer.appendChild(label);
    }
}

// Save note function for auto-save
async function saveNote() {
    const editorContainer = document.getElementById('modal-editor-container');
    const quill = Quill.find(editorContainer);

    if (!quill) {
        console.error('Editor not found');
        return Promise.reject('Editor not found');
    }

    // Get form data
    const userId = document.getElementById('modalUserId')?.value || window.currentUserId;
    const noteId = document.getElementById('modalNoteId')?.value || '0';
    const title = document.getElementById('modalNoteTitle')?.value || '';
    const content = quill.root.innerHTML;
    const csrfToken = document.getElementById('modalCsrfToken')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Get labels from localStorage
    const labels = JSON.parse(localStorage.getItem('noteLabels') || '[]');

    if (!userId || !csrfToken) {
        updateSaveStatus('Error: Missing required fields');
        return Promise.reject('Missing required fields');
    }

    // Skip saving if title and content are empty
    if (!title.trim() && !quill.getText().trim()) {
        updateSaveStatus('Note is empty');
        return Promise.resolve({ status: 'empty' });
    }

    // Create form data
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('title', title || 'Untitled Note');
    formData.append('content', content);
    formData.append('csrf_token', csrfToken);
    formData.append('labels', JSON.stringify(labels));

    if (noteId !== "0") {
        formData.append('note_id', noteId);
    }

    // Update saving indicator
    updateSaveStatus('Saving...');

    try {
        const response = await fetch('../dashboard/save_note.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {

            if (noteId === "0" && data.note_id) {
                document.getElementById('modalNoteId').value = data.note_id;


                const modalTitle = document.getElementById('modalTitle');
                if (modalTitle) modalTitle.textContent = 'Edit Note';
            }


            updateSaveStatus('All changes saved');


            contentChanged = false;

            return data;
        } else {
            // Handle error
            updateSaveStatus('Error: ' + (data.message || 'Unknown error'));
            return Promise.reject(data.message || 'Unknown error');
        }
    } catch (error) {
        console.error('Request failed:', error);
        updateSaveStatus('Error saving note');
        return Promise.reject(error);
    }
}

function refreshNotesList() {

    window.location.reload();
}