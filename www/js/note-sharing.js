/* note-sharing.js - Handles note sharing functionality */

document.addEventListener('DOMContentLoaded', function() {
    // References to modal elements
    const shareModal = document.getElementById('shareNoteModal');
    const shareNoteId = document.getElementById('shareNoteId');
    const shareEmailInput = document.getElementById('shareEmail');
    const addEmailBtn = document.getElementById('addEmailBtn');
    const recipientsList = document.getElementById('recipientsList');
    const cancelShareBtn = document.getElementById('cancelShareBtn');
    const saveShareBtn = document.getElementById('saveShareBtn');
    const emailError = document.getElementById('emailError');
    
    // Temporary array to store recipients
    let recipients = [];
    
    // Setup listeners for share buttons on note cards
    document.querySelectorAll('.share-note-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const noteId = this.dataset.id;
            openShareModal(noteId);
        });
    });
    
    // Modal opening function
    function openShareModal(noteId) {
        shareNoteId.value = noteId;
        recipients = []; // Reset recipients
        updateRecipientsList();
        
        // Load existing shares for this note
        loadExistingShares(noteId);
        
        shareModal.classList.remove('hidden');
        shareEmailInput.focus();
    }
    
    // Load existing shares for the note
    function loadExistingShares(noteId) {
        fetch(`../api/get_shares.php?note_id=${noteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.shares) {
                    recipients = data.shares.map(share => ({
                        email: share.shared_email,
                        permission: share.permission
                    }));
                    updateRecipientsList();
                }
            })
            .catch(error => console.error('Error loading shares:', error));
    }
    
    // Add email to recipients list
    addEmailBtn.addEventListener('click', function() {
        const email = shareEmailInput.value.trim();
        if (validateEmail(email)) {
            emailError.classList.add('hidden');
            
            // Check if email already exists in the list
            if (!recipients.some(r => r.email === email)) {
                const permission = document.querySelector('input[name="sharePermission"]:checked').value;
                recipients.push({ email, permission });
                updateRecipientsList();
                shareEmailInput.value = '';
                shareEmailInput.focus();
            } else {
                // Update existing recipient's permission
                const index = recipients.findIndex(r => r.email === email);
                if (index !== -1) {
                    const permission = document.querySelector('input[name="sharePermission"]:checked').value;
                    recipients[index].permission = permission;
                    updateRecipientsList();
                    shareEmailInput.value = '';
                }
            }
        } else {
            emailError.classList.remove('hidden');
        }
    });
    
    // Enter key for adding email
    shareEmailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addEmailBtn.click();
        }
    });
    
    // Update the visual list of recipients
    function updateRecipientsList() {
        if (recipients.length === 0) {
            recipientsList.innerHTML = '<div class="text-gray-500 italic text-sm">No recipients added yet</div>';
            return;
        }
        
        recipientsList.innerHTML = '';
        recipients.forEach((recipient, index) => {
            const recipientEl = document.createElement('div');
            recipientEl.className = 'flex justify-between items-center border-b border-gray-200 py-2';
            recipientEl.innerHTML = `
                <div>
                    <span class="font-medium">${recipient.email}</span>
                    <span class="ml-2 text-sm ${recipient.permission === 'read' ? 'text-blue-600' : 'text-green-600'}">
                        ${recipient.permission === 'read' ? 'Read only' : 'Can edit'}
                    </span>
                </div>
                <button class="text-red-600 hover:text-red-800" onclick="removeRecipient(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            recipientsList.appendChild(recipientEl);
        });
    }
    
    // Function to remove a recipient (exposed globally for the inline onclick)
    window.removeRecipient = function(index) {
        recipients.splice(index, 1);
        updateRecipientsList();
    };
    
    // Validate email format
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Cancel button closes the modal
    cancelShareBtn.addEventListener('click', function() {
        shareModal.classList.add('hidden');
    });
    
    // Save shares
    saveShareBtn.addEventListener('click', function() {
        const noteId = shareNoteId.value;
        if (noteId && recipients.length > 0) {
            const formData = new FormData();
            formData.append('note_id', noteId);
            formData.append('recipients', JSON.stringify(recipients));
            formData.append('action', 'share');
            
            fetch('../api/share_note.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the UI to show note is shared
                    const noteCard = document.querySelector(`.note-card[data-id="${noteId}"]`);
                    if (noteCard) {
                        const statusIcons = noteCard.querySelector('.status-icons');
                        if (!statusIcons.querySelector('.fa-share-alt')) {
                            const shareIcon = document.createElement('i');
                            shareIcon.className = 'fas fa-share-alt status-icon';
                            shareIcon.title = 'Shared';
                            statusIcons.appendChild(shareIcon);
                        }
                    }
                    
                    // Show success message
                    showToast('Note shared successfully');
                    shareModal.classList.add('hidden');
                } else {
                    showToast('Error sharing note: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error sharing note:', error);
                showToast('Error sharing note. Please try again.');
            });
        } else if (recipients.length === 0) {
            showToast('Please add at least one recipient');
        }
    });
    
    // Close modal if clicking outside
    shareModal.addEventListener('click', function(e) {
        if (e.target === shareModal) {
            shareModal.classList.add('hidden');
        }
    });
    
    // Show toast notification
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
});

function openShareModal(noteId) {
    const shareNoteIdElement = document.getElementById('shareNoteId');
    const shareModalElement = document.getElementById('shareModal');
    const shareEmailElement = document.getElementById('shareEmail');
    const recipientsListElement = document.getElementById('recipientsList');
    const emailErrorElement = document.getElementById('emailError');
    
    // Check if elements exist before using them
    if (!shareModalElement) {
        console.error('Share modal element not found!');
        return; 
    }
    
    if (shareNoteIdElement) shareNoteIdElement.value = noteId;
    shareModalElement.classList.remove('hidden');
    if (shareEmailElement) shareEmailElement.focus();
    
    // Clear previous recipients and error state
    if (recipientsListElement) {
        recipientsListElement.innerHTML = '<div class="text-gray-500 italic text-sm">No recipients added yet</div>';
    }
    
    if (emailErrorElement) emailErrorElement.classList.add('hidden');
    if (shareEmailElement) shareEmailElement.value = '';
    
    // Load existing shares for this note
    loadExistingShares(noteId);
}

// Load existing shares for a note
function loadExistingShares(noteId) {
    fetch(`../api/get_shares.php?note_id=${noteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.shares) {
                window.recipients = data.shares.map(share => ({
                    email: share.shared_email,
                    permission: share.permission
                }));
                updateRecipientsList();
            }
        })
        .catch(error => console.error('Error loading shares:', error));
}

// Update the recipients list UI
function updateRecipientsList() {
    const recipientsList = document.getElementById('recipientsList');
    
    if (!window.recipients || window.recipients.length === 0) {
        recipientsList.innerHTML = '<div class="text-gray-500 italic text-sm">No recipients added yet</div>';
        return;
    }
    
    recipientsList.innerHTML = '';
    window.recipients.forEach((recipient, index) => {
        const recipientEl = document.createElement('div');
        recipientEl.className = 'flex justify-between items-center border-b border-gray-200 py-2';
        recipientEl.innerHTML = `
            <div>
                <span class="font-medium">${recipient.email}</span>
                <span class="ml-2 text-sm ${recipient.permission === 'read' ? 'text-blue-600' : 'text-green-600'}">
                    ${recipient.permission === 'read' ? 'Read only' : 'Can edit'}
                </span>
            </div>
            <button class="text-red-600 hover:text-red-800" onclick="removeRecipient(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        recipientsList.appendChild(recipientEl);
    });
}

// Function to remove a recipient
function removeRecipient(index) {
    if (window.recipients) {
        window.recipients.splice(index, 1);
        updateRecipientsList();
    }
}

// Add a recipient to the list
function addShareRecipient() {
    const emailInput = document.getElementById('shareEmail');
    const email = emailInput.value.trim();
    const emailError = document.getElementById('emailError');
    
    if (!validateEmail(email)) {
        emailError.classList.remove('hidden');
        return;
    }
    
    emailError.classList.add('hidden');
    
    if (!window.recipients) {
        window.recipients = [];
    }
    
    // Get selected permission
    const permission = document.querySelector('input[name="sharePermission"]:checked').value;
    
    // Check if email already exists
    const existingIndex = window.recipients.findIndex(r => r.email === email);
    
    if (existingIndex !== -1) {
        // Update permission if it exists
        window.recipients[existingIndex].permission = permission;
    } else {
        // Add new recipient
        window.recipients.push({ email, permission });
    }
    
    // Update UI and clear input
    updateRecipientsList();
    emailInput.value = '';
    emailInput.focus();
}

// Validate email format
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Save shares
function saveShares() {
    const noteId = document.getElementById('shareNoteId').value;
    if (!noteId || !window.recipients || window.recipients.length === 0) {
        showToast(window.recipients && window.recipients.length === 0 ? 
            'Please add at least one recipient' : 'Invalid note');
        return;
    }
    
    const formData = new FormData();
    formData.append('note_id', noteId);
    formData.append('recipients', JSON.stringify(window.recipients));
    formData.append('action', 'share');
    
    fetch('../api/share_note.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show the note is shared
            const noteCard = document.querySelector(`.note-card[data-id="${noteId}"]`);
            if (noteCard) {
                const statusIcons = noteCard.querySelector('.status-icons');
                if (!statusIcons.querySelector('.fa-share-alt')) {
                    const shareIcon = document.createElement('i');
                    shareIcon.className = 'fas fa-share-alt status-icon';
                    shareIcon.title = 'Shared';
                    statusIcons.appendChild(shareIcon);
                }
            }
            
            // Show success message and close modal
            showToast('Note shared successfully');
            closeShareModal();
        } else {
            showToast('Error sharing note: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error sharing note:', error);
        showToast('Error sharing note. Please try again.');
    });
}

// Close share modal
function closeShareModal() {
    document.getElementById('shareModal').classList.add('hidden');
}

// Revoke access for a specific email
function revokeAccess(noteId, email) {
    if (!confirm(`Are you sure you want to revoke access for ${email}?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('note_id', noteId);
    formData.append('email', email);
    formData.append('action', 'revoke');
    
    fetch('../api/share_note.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from the list and update UI
            window.recipients = window.recipients.filter(r => r.email !== email);
            updateRecipientsList();
            
            // Check if there are any recipients left
            if (window.recipients.length === 0) {
                // Update the UI to show the note is no longer shared
                const noteCard = document.querySelector(`.note-card[data-id="${noteId}"]`);
                if (noteCard) {
                    const shareIcon = noteCard.querySelector('.fa-share-alt');
                    if (shareIcon) {
                        shareIcon.remove();
                    }
                }
            }
            
            showToast('Access revoked successfully');
        } else {
            showToast('Error revoking access: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error revoking access:', error);
        showToast('Error revoking access. Please try again.');
    });
}

// Show toast notification
function showToast(message) {
    // Create toast element if it doesn't exist
    let toast = document.querySelector('.toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast';
        document.body.appendChild(toast);
    }
    
    // Set message and display
    toast.textContent = message;
    toast.style.display = 'block';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Initialize share button listeners when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add listeners to share buttons
    document.querySelectorAll('.share-note-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const noteId = this.getAttribute('data-id');
            openShareModal(noteId);
        });
    });
    
    // Setup event listeners for share modal
    const shareModal = document.getElementById('shareModal');
    if (shareModal) {
        // Add email button
        document.getElementById('addEmailBtn').addEventListener('click', addShareRecipient);
        
        // Enter key handling for email input
        document.getElementById('shareEmail').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addShareRecipient();
            }
        });
        
        // Save button
        document.getElementById('saveShareBtn').addEventListener('click', saveShares);
        
        // Cancel button
        document.getElementById('cancelShareBtn').addEventListener('click', closeShareModal);
        
        // Close modal when clicking outside
        shareModal.addEventListener('click', function(e) {
            if (e.target === shareModal) {
                closeShareModal();
            }
        });
    }
});