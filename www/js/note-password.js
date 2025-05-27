// Note Password Protection Module

const PasswordProtection = (function () {
    // DOM elements
    let passwordModal;
    let passwordModalTitle;
    let verifyPasswordForm;
    let setPasswordForm;
    let removePasswordForm;
    let pendingAction;

    // Initialize
    function init() {
        // Get DOM elements
        passwordModal = document.getElementById('passwordModal');
        passwordModalTitle = document.getElementById('passwordModalTitle');
        verifyPasswordForm = document.getElementById('verifyPasswordForm');
        setPasswordForm = document.getElementById('setPasswordForm');
        removePasswordForm = document.getElementById('removePasswordForm');

        if (!passwordModal) return;

        setupEventListeners();

        setupLockButtons();

        setupProtectedActions();
    }

    function setupEventListeners() {
        passwordModal.addEventListener('click', function (e) {
            if (e.target === passwordModal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !passwordModal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Cancel buttons
        document.getElementById('cancelPasswordBtn')?.addEventListener('click', closeModal);
        document.getElementById('cancelSetPasswordBtn')?.addEventListener('click', closeModal);
        document.getElementById('cancelRemovePasswordBtn')?.addEventListener('click', closeModal);

        // Submit password button
        document.getElementById('submitPasswordBtn')?.addEventListener('click', function () {
            const password = document.getElementById('notePassword').value;
            if (!password) {
                alert('Please enter a password');
                return;
            }
            verifyPassword(pendingAction);
        });

        // Save password button
        document.getElementById('savePasswordBtn')?.addEventListener('click', function () {
            const password = document.getElementById('newNotePassword').value;
            const confirmPassword = document.getElementById('confirmNotePassword').value;

            if (!password) {
                alert('Please enter a password');
                return;
            }

            if (password !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }

            setPassword();
        });

        // Remove password button
        document.getElementById('removePasswordBtn')?.addEventListener('click', function () {
            const password = document.getElementById('currentPassword').value;
            if (!password) {
                alert('Please enter the current password');
                return;
            }
            removePassword();
        });

        // Enter key in password fields
        document.getElementById('notePassword')?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('submitPasswordBtn').click();
            }
        });

        document.getElementById('confirmNotePassword')?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('savePasswordBtn').click();
            }
        });

        document.getElementById('currentPassword')?.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                document.getElementById('removePasswordBtn').click();
            }
        });
    }

    // Set up lock/unlock buttons
    function setupLockButtons() {
        const lockButtons = document.querySelectorAll('.lock-note-btn');
        lockButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                const noteId = this.getAttribute('data-id');
                const noteCard = this.closest('.note-card');
                const hasLockIcon = noteCard.querySelector('.fa-lock');

                pendingAction = {
                    noteId: noteId,
                    action: hasLockIcon ? 'unlock' : 'lock'
                };

                if (hasLockIcon) {
                    showRemovePasswordForm();
                } else {
                    showSetPasswordForm();
                }
            });
        });
    }

    // protection for edit/delete buttons
    function setupProtectedActions() {
        const editButtons = document.querySelectorAll('.note-actions button');
        editButtons.forEach(button => {
            if (button.innerHTML.includes('Edit') || button.innerHTML.includes('Delete')) {
                const originalClick = button.onclick;
                button.onclick = null;

                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const noteCard = button.closest('.note-card');
                    if (!noteCard) return;

                    const noteId = noteCard.getAttribute('data-id');
                    const hasLockIcon = noteCard.querySelector('.fa-lock');

                    if (hasLockIcon) {
                        const isEdit = button.innerHTML.includes('Edit');
                        pendingAction = {
                            noteId: noteId,
                            action: isEdit ? 'edit' : 'delete',
                            originalAction: originalClick
                        };
                        showVerifyPasswordForm();
                    } else {
                        // No password, proceed with original action
                        if (typeof originalClick === 'function') {
                            originalClick.call(this);
                        }
                    }
                });
            }
        });
    }

    // Show verify password form
    function showVerifyPasswordForm() {
        passwordModalTitle.textContent =
            pendingAction.action === 'edit' ? 'Verify Password to Edit' :
                pendingAction.action === 'delete' ? 'Verify Password to Delete' :
                    'Enter Password';

        verifyPasswordForm.style.display = 'block';
        setPasswordForm.style.display = 'none';
        removePasswordForm.style.display = 'none';

        document.getElementById('notePassword').value = '';
        passwordModal.classList.remove('hidden');
    }

    // Show set password form
    function showSetPasswordForm() {
        passwordModalTitle.textContent = 'Lock Note';
        verifyPasswordForm.style.display = 'none';
        setPasswordForm.style.display = 'block';
        removePasswordForm.style.display = 'none';

        document.getElementById('newNotePassword').value = '';
        document.getElementById('confirmNotePassword').value = '';
        passwordModal.classList.remove('hidden');
    }

    // Show remove password form
    function showRemovePasswordForm() {
        passwordModalTitle.textContent = 'Unlock Note';
        verifyPasswordForm.style.display = 'none';
        setPasswordForm.style.display = 'none';
        removePasswordForm.style.display = 'block';

        document.getElementById('currentPassword').value = '';
        passwordModal.classList.remove('hidden');
    }

    // Close modal
    function closeModal() {
        passwordModal.classList.add('hidden');
        pendingAction = null;
    }

    // Verify password
    function verifyPassword(action) {
        if (!pendingAction || !pendingAction.noteId) return;

        const noteId = pendingAction.noteId;
        const actionType = pendingAction.action;
        const callback = pendingAction.originalAction;
        const password = document.getElementById('notePassword').value;

        // Show loading status
        document.getElementById('submitPasswordBtn').textContent = 'Verifying...';
        document.getElementById('submitPasswordBtn').disabled = true;

        // Get CSRF token
        const csrfToken = document.getElementById('csrfToken')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Make AJAX request
        fetch('verify_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `note_id=${noteId}&password=${encodeURIComponent(password)}&csrf_token=${csrfToken}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.valid) {
                    closeModal();

                    if (actionType === 'edit') {
                        if (typeof callback === 'function') {
                            callback(noteId);
                        } else {
                            window.location.href = `index.php?id=${noteId}`;
                        }
                    } else if (actionType === 'delete') {
                        if (typeof confirmDelete === 'function') {
                            confirmDelete(noteId);
                        }
                    }
                } else {
                    alert('Incorrect password. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error verifying password:', error);
                alert('Failed to verify password. Please try again.');
            })
            .finally(() => {
                // Reset button state
                document.getElementById('submitPasswordBtn').textContent = 'Submit';
                document.getElementById('submitPasswordBtn').disabled = false;
            });
    }

    // Set password
    function setPassword() {
        if (!pendingAction || !pendingAction.noteId) return;

        const noteId = pendingAction.noteId;
        const password = document.getElementById('newNotePassword').value;

        // Show loading state
        document.getElementById('savePasswordBtn').textContent = 'Saving...';
        document.getElementById('savePasswordBtn').disabled = true;

        // Get CSRF token
        const csrfToken = document.getElementById('csrfToken')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Make AJAX request
        fetch('set_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `note_id=${noteId}&password=${encodeURIComponent(password)}&csrf_token=${csrfToken}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    closeModal();
                    if (typeof showAutoSaveIndicator === 'function') {
                        showAutoSaveIndicator('Note locked successfully!', true);
                    } else {
                        showPasswordStatus('Note locked successfully!', 'lock');
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Failed to set password: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error setting password:', error);
                alert('Failed to set password. Please try again.');
            })
            .finally(() => {
                // Reset button state
                document.getElementById('savePasswordBtn').textContent = 'Save Password';
                document.getElementById('savePasswordBtn').disabled = false;
            });
    }

    // Remove password
    function removePassword() {
        if (!pendingAction || !pendingAction.noteId) return;

        const noteId = pendingAction.noteId;
        const password = document.getElementById('currentPassword').value;

        // Show loading state
        document.getElementById('removePasswordBtn').textContent = 'Removing...';
        document.getElementById('removePasswordBtn').disabled = true;

        // Get CSRF token
        const csrfToken = document.getElementById('csrfToken')?.value ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Make AJAX request
        fetch('remove_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `note_id=${noteId}&password=${encodeURIComponent(password)}&csrf_token=${csrfToken}`
        })
            .then(response => response.json())
            .then(data => {

                console.log('PasswordProtection: Remove password response:', data);
                if (data.status === 'success' || data.message === 'Password removed successfully') {

                    console.log('PasswordProtection: Password removed successfully');
                    closeModal();


                    if (typeof showAutoSaveIndicator === 'function') {
                        showAutoSaveIndicator('Note unlocked successfully!', true);
                    } else {
                        showPasswordStatus('Note unlocked successfully!', 'unlock');
                    }


                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    console.warn('PasswordProtection: Status message received:', data.message);

                    if (data.message && !data.message.toLowerCase().includes('fail')) {
                        closeModal();
                        showPasswordStatus('Note unlocked!', 'unlock');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert('Error: ' + (data.message || 'Failed to remove password'));
                    }
                }
            })
            .catch(error => {
                console.error('Error removing password:', error);
                alert('Failed to remove password. Please try again.');
            })
            .finally(() => {
                // Reset button state
                document.getElementById('removePasswordBtn').textContent = 'Remove Password';
                document.getElementById('removePasswordBtn').disabled = false;
            });
    }

    // Show password status
    function showPasswordStatus(message, icon) {
        const existingStatus = document.querySelector('.password-status');
        if (existingStatus) {
            existingStatus.remove();
        }

        // Create status element
        const status = document.createElement('div');
        status.className = 'password-status';
        status.innerHTML = `
            <i class="fas fa-${icon === 'lock' ? 'lock' : 'unlock'}"></i>
            <span>${message}</span>
        `;

        // Add to document
        document.body.appendChild(status);
        setTimeout(() => {
            status.remove();
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', init);

    return {
        init,

        checkPasswordAndRun: function (noteId, callback) {
            pendingAction = {
                noteId: noteId,
                action: 'edit',
                originalAction: callback
            };

            showVerifyPasswordForm();
        }
    };
})();

// Initialize password protection
if (typeof PasswordProtection !== 'undefined') {
    PasswordProtection.init();
}