// Global functions


// Filter notes based on search and active label
function filterNotes() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const noteCards = document.querySelectorAll('.note-card');
    const noNotesMessage = document.getElementById('noNotesMessage');
    const currentFilter = window.UI?.currentFilter || 'all';

    let visibleCount = 0;

    noteCards.forEach(card => {
        // Get note data
        const title = card.querySelector('.note-title')?.textContent.toLowerCase() || '';
        const content = card.querySelector('.note-content')?.textContent.toLowerCase() || '';
        const labels = JSON.parse(card.dataset.labels || '[]').map(label => label.toLowerCase());

        const pinButton = card.querySelector('button:has(.fa-thumbtack)');
        const isPinned = pinButton && pinButton.textContent.trim().includes('Unpin');

        // Updated labelMatch logic
        const labelMatch = currentFilter === 'all' ||
            (currentFilter === 'Pinned' && isPinned) ||
            labels.includes(currentFilter.toLowerCase());

        // Check if note matches search
        const searchMatch = !searchTerm ||
            title.includes(searchTerm) ||
            content.includes(searchTerm) ||
            labels.some(label => label.includes(searchTerm));

        // Show or hide based on filters
        if (labelMatch && searchMatch) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    if (noNotesMessage) {
        noNotesMessage.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

// Add label to note
function addLabel() {
    const labelInput = document.getElementById('labelInput');
    const labelContainer = document.getElementById('labelContainer');

    if (!labelInput || !labelContainer) {
        console.error('Label input or container not found');
        return;
    }

    const labelText = labelInput.value.trim();

    if (labelText) {
        // Get current labels
        let labels = JSON.parse(localStorage.getItem('noteLabels') || '[]');
        if (!labels.includes(labelText)) {
            labels.push(labelText);
            localStorage.setItem('noteLabels', JSON.stringify(labels));

            // Create label element
            const labelElement = document.createElement('span');
            labelElement.className = 'px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm flex items-center';
            labelElement.innerHTML = `
                ${labelText}
                <button type="button" class="ml-2 text-purple-800 hover:text-purple-900" onclick="removeLabel(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;

            labelContainer.appendChild(labelElement);
            labelInput.value = '';

            showAutoSaveIndicator('Label added', true);
        } else {

            showAutoSaveIndicator('Label already exists', true);
        }
    } else {

        showAutoSaveIndicator('Please enter a label name', true);
    }
}

// Remove label from note
function removeLabel(button) {
    const labelElement = button.parentElement;
    const labelText = labelElement.textContent.trim();

    // Remove from localStorage
    let labels = JSON.parse(localStorage.getItem('noteLabels') || '[]');
    labels = labels.filter(label => label !== labelText);
    localStorage.setItem('noteLabels', JSON.stringify(labels));

    // Remove from DOM
    labelElement.remove();

    // Show feedback
    showAutoSaveIndicator('Label removed', true);
}

// Save note
function saveNote() {
    const noteForm = document.getElementById('noteForm');
    const editorContainer = document.getElementById('editor-container');
    const quill = editorContainer ? Quill.find(editorContainer) : null;

    if (!noteForm || !quill) {
        console.error('Form or editor not found');
        return;
    }

    // Get form data
    const userId = document.getElementById('userId')?.value || window.currentUserId;
    const noteId = document.getElementById('noteId')?.value || '0';
    const title = document.getElementById('noteTitle')?.value || '';
    const content = quill.root.innerHTML;
    const csrfToken = document.getElementById('csrfToken')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const labels = JSON.parse(localStorage.getItem('noteLabels') || '[]');

    if (!userId || !csrfToken) {
        console.error('Missing required fields');
        return;
    }

    // Validate
    if (!title.trim()) {
        alert('Please enter a title for your note.');
        return;
    }

    // Create form data
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('title', title);
    formData.append('content', content);
    formData.append('csrf_token', csrfToken);
    formData.append('labels', JSON.stringify(labels));

    if (noteId !== "0") {
        formData.append('note_id', noteId);
    }

    // Show saving indicator
    showAutoSaveIndicator('Saving...', false);

    // Send to server
    fetch('../dashboard/save_note.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Update saving indicator
                showAutoSaveIndicator('Saved!', true);

                // Reset labels
                localStorage.setItem('noteLabels', JSON.stringify([]));

                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                showAutoSaveIndicator('Error: ' + (data.message || 'Unknown error'), true);
            }
        })
        .catch(error => {
            console.error('Request failed:', error);
            window.location.href = 'index.php';
        });
}

// Show autosave indicator
function showAutoSaveIndicator(text, hide = false) {
    const autoSaveIndicator = document.getElementById('autoSaveIndicator');
    const autoSaveText = document.getElementById('autoSaveText');

    if (autoSaveIndicator && autoSaveText) {
        autoSaveText.textContent = text;
        autoSaveIndicator.style.display = 'block';

        if (hide) {
            setTimeout(() => {
                autoSaveIndicator.style.display = 'none';
            }, 2000);
        }
    }
}

// Toggle note form visibility
function toggleNoteForm() {
    const noteFormContainer = document.getElementById('noteFormContainer');
    const notesContainer = document.getElementById('notesContainer');

    if (noteFormContainer && notesContainer) {
        noteFormContainer.classList.toggle('hidden');
        notesContainer.classList.toggle('hidden');
    }
}

// Confirm delete note
function confirmDelete(noteId) {
    console.log('confirmDelete called with noteId:', noteId);

    if (!noteId) {
        console.error('Cannot delete note: No note ID provided');
        return;
    }

    const deleteUrl = 'delete_note.php?id=' + noteId;
    console.log('Delete URL would be:', deleteUrl);

    const confirmOverlay = document.getElementById('confirmOverlay');
    const confirmDialog = document.getElementById('confirmDialog');

    if (!confirmOverlay || !confirmDialog) {
        if (confirm('Are you sure you want to delete this note?')) {
            try {
                console.log('Attempting to delete via fallback confirm');
                submitDeleteRequest(noteId);
            } catch (e) {
                console.error('Error during delete redirect:', e);
                alert('Error deleting note. Please try again.');
            }
        }
        return;
    }

    // Get references to other elements
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmCancel = document.getElementById('confirmCancel');
    const confirmOk = document.getElementById('confirmOk');

    confirmMessage.textContent = 'Are you sure you want to delete this note?';
    confirmOverlay.style.display = 'block';
    confirmDialog.style.display = 'block';

    // Store ID on dialog
    confirmDialog.setAttribute('data-note-id', noteId);

    // Cancel button
    confirmCancel.onclick = function () {
        confirmOverlay.style.display = 'none';
        confirmDialog.style.display = 'none';
    };

    confirmOk.onclick = function () {
        const noteToDelete = confirmDialog.getAttribute('data-note-id');
        console.log('Confirm OK clicked for note:', noteToDelete);

        confirmOverlay.style.display = 'none';
        confirmDialog.style.display = 'none';

        submitDeleteRequest(noteToDelete);
    };
}

function submitDeleteRequest(noteId) {
    // Create a form for submission
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = 'delete_note.php';

    // Add the note ID as a hidden input
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id';
    input.value = noteId;

    // Submit the form
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}



// Toggle pin status
function togglePin(noteId, pinStatus) {
    const csrfToken = document.getElementById('csrfToken')?.value ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }

    fetch('toggle_pin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + noteId + '&pin=' + (pinStatus ? 1 : 0) + '&csrf_token=' + csrfToken
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAutoSaveIndicator(pinStatus ? 'Note pinned!' : 'Note unpinned!', true);

                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                alert('Error: ' + (data.message || 'Failed to update pin status'));
            }
        })
        .catch(error => {
            console.error('Request failed:', error);
            alert('An error occurred. Please try again.');
        });
}

function editLabel(labelId, currentText) {
    const newLabelText = prompt('Enter new label name:', currentText);

    if (newLabelText === null || newLabelText.trim() === '') {
        console.log('Label edit cancelled or empty text');
        return;
    }

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
    formData.append('action', 'edit');
    formData.append('label_id', labelId);
    formData.append('new_label_text', newLabelText);
    formData.append('user_id', window.currentUserId);

    // Show loading indicator
    showAutoSaveIndicator('Updating label...', false);

    fetch('../dashboard/manage_labels.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (window.Notes && typeof window.Notes.fetchLabels === 'function') {
                    window.Notes.fetchLabels();
                }

                if (window.UI && window.UI.currentFilter === currentText) {
                    window.UI.filterNotes(newLabelText);
                }

                // Show success message
                showAutoSaveIndicator('Label updated successfully!', true);
            } else {
                // Show error message
                showAutoSaveIndicator(`Error: ${data.message}`, true);
                console.error('Failed to update label:', data.message);
            }
        })
        .catch(error => {
            showAutoSaveIndicator('Error updating label', true);
            console.error('Error:', error);
        });
}


function deleteLabel(labelId, labelText) {
    if (!confirm(`Are you sure you want to delete the label "${labelText}"?`)) {
        console.log('Label deletion cancelled');
        return;
    }

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
    formData.append('action', 'delete');
    formData.append('label_id', labelId);
    formData.append('user_id', window.currentUserId);

    // Show loading indicator
    showAutoSaveIndicator('Deleting label...', false);

    // Send AJAX request
    fetch('../dashboard/manage_labels.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {

                if (window.Notes && typeof window.Notes.fetchLabels === 'function') {
                    window.Notes.fetchLabels();
                }


                if (window.UI && window.UI.currentFilter === labelText) {
                    window.UI.filterNotes('all');
                }

                // Show success message
                showAutoSaveIndicator('Label deleted successfully!', true);
            } else {
                // Show error message
                showAutoSaveIndicator(`Error: ${data.message}`, true);
                console.error('Failed to delete label:', data.message);
            }
        })
        .catch(error => {
            showAutoSaveIndicator('Error deleting label', true);
            console.error('Error:', error);
        });
}

// Helper functions for browser compatibility
if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector ||
        Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
    Element.prototype.closest = function (s) {
        var el = this;
        do {
            if (el.matches(s)) return el;
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}

if (typeof jQuery !== 'undefined') {
    jQuery.expr[':'].contains = function (a, i, m) {
        return jQuery(a).text().indexOf(m[3]) >= 0;
    };
}