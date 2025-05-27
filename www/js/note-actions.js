/**
 * Dialog-style Action Menu
 */
document.addEventListener('DOMContentLoaded', function () {
  // Create the action dialog and add it to the body
  function createActionDialog() {
    const overlay = document.createElement('div');
    overlay.className = 'action-dialog-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'action-dialog';
    dialog.innerHTML = `
      <div class="action-dialog-header">
        <div class="action-dialog-title">Note Actions</div>
      </div>
      <div class="action-dialog-content">
        <!-- Actions will be dynamically inserted here -->
      </div>
      <div class="action-dialog-footer">
        <button class="action-dialog-cancel">Cancel</button>
      </div>
    `;

    document.body.appendChild(overlay);
    document.body.appendChild(dialog);

    return { overlay, dialog };
  }

  // Create elements
  const { overlay, dialog } = createActionDialog();
  const contentContainer = dialog.querySelector('.action-dialog-content');
  let currentNoteId = null;

  // Show action dialog for a specific note
  function showActionDialog(noteId, noteTitle, isPinned, isLocked) {
    currentNoteId = noteId;

    // Update title with note info
    dialog.querySelector('.action-dialog-title').textContent =
      noteTitle ? `"${noteTitle}"` : 'Note Actions';

    // Set actions
    contentContainer.innerHTML = `
      <button class="action-dialog-button pin" data-action="pin">
        <i class="fas fa-thumbtack"></i> 
        ${isPinned ? 'Unpin' : 'Pin'} Note
      </button>
      <button class="action-dialog-button share" data-action="share">
        <i class="fas fa-share-alt"></i> 
        Share Note
      </button>
      <button class="action-dialog-button lock" data-action="lock">
        <i class="fas ${isLocked ? 'fa-lock' : 'fa-unlock'}"></i> 
        ${isLocked ? 'Unlock' : 'Lock'} Note
      </button>
      <button class="action-dialog-button delete" data-action="delete">
        <i class="fas fa-trash"></i> 
        Delete Note
      </button>
    `;

    // Show the overlay and dialog
    overlay.classList.add('active');
    dialog.classList.add('active');

    // Prevent body scrolling
    document.body.style.overflow = 'hidden';
  }

  // Hide action dialog
  function hideActionDialog() {
    overlay.classList.remove('active');
    dialog.classList.remove('active');
    document.body.style.overflow = '';
    currentNoteId = null;
  }

  // Handle action button clicks
  document.addEventListener('click', function (e) {
    // Handle "more" button clicks
    if (e.target.closest('.note-btn.more')) {
      e.preventDefault();
      e.stopPropagation();

      const noteCard = e.target.closest('.note-card');
      const noteId = noteCard.getAttribute('data-id');
      const noteTitle = noteCard.querySelector('.note-title')?.textContent.trim() || '';
      const isPinned = noteCard.querySelector('.status-icon[title="Pinned"]') !== null ||
        noteCard.parentElement.closest('#pinnedNotes') !== null;
      const isLocked = noteCard.querySelector('.status-icon[title="Password Protected"]') !== null;

      showActionDialog(noteId, noteTitle, isPinned, isLocked);
      return;
    }

    // Handle overlay click (to close)
    if (e.target === overlay) {
      hideActionDialog();
      return;
    }

    // Handle cancel button click
    if (e.target.closest('.action-dialog-cancel')) {
      hideActionDialog();
      return;
    }

    // Handle action button clicks
    const actionButton = e.target.closest('.action-dialog-button');
    if (actionButton && currentNoteId) {
      const action = actionButton.getAttribute('data-action');
      switch (action) {
        case 'pin':
          const isPinned = actionButton.textContent.trim().startsWith('Unpin');
          togglePin(currentNoteId, isPinned ? 0 : 1);
          break;
        case 'share':
          document.querySelector(`.share-note-btn[data-id="${currentNoteId}"]`)?.click();
          break;
        case 'lock':
          document.querySelector(`.lock-note-btn[data-id="${currentNoteId}"]`)?.click();
          break;
        case 'delete':
          const noteCard = document.querySelector(`.note-card[data-id="${currentNoteId}"]`);
          const isLocked = noteCard.querySelector('.status-icon[title="Password Protected"]') !== null;

          // Store noteId 
          const noteIdToDelete = currentNoteId;
          console.log('Note ID to delete:', noteIdToDelete);

          if (isLocked) {
            PasswordProtection.checkPasswordAndRun(noteIdToDelete, function () {

              console.log('Password verified for note:', noteIdToDelete);
              confirmDelete(noteIdToDelete);
            });
          } else {

            console.log('No password needed for note:', noteIdToDelete);
            confirmDelete(noteIdToDelete);
          }
          break;

      }

      hideActionDialog();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && dialog.classList.contains('active')) {
      hideActionDialog();
    }
  });
});


