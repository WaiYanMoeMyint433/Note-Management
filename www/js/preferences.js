// preferences.js - JavaScript for User Preferences page

document.addEventListener('DOMContentLoaded', () => {
    // Get DOM elements
    const body = document.getElementById('body');
    const fontSizeSelect = document.getElementById('fontSize');
    const noteColorInput = document.getElementById('noteColor');
    const toggleThemeBtn = document.getElementById('toggleTheme');

    // Load saved preferences
    loadPreferences();

    // Set up event listeners
    fontSizeSelect.addEventListener('change', saveFontSize);
    noteColorInput.addEventListener('input', saveNoteColor);
    toggleThemeBtn.addEventListener('click', toggleTheme);
});

// Load preferences from localStorage
function loadPreferences() {
    const savedFontSize = localStorage.getItem('fontSize') || 'text-base';
    const savedNoteColor = localStorage.getItem('noteColor') || '#ffffff';
    const savedTheme = localStorage.getItem('theme') || 'light';

    // Apply saved preferences
    document.getElementById('fontSize').value = savedFontSize;
    document.getElementById('noteColor').value = savedNoteColor;
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        updateThemeButton(true);
    }

    // Show current values
    showPreviewText();
}

// Save font size preference
function saveFontSize(e) {
    const fontSize = e.target.value;
    localStorage.setItem('fontSize', fontSize);
    showSavedIndicator('Font size saved!');
    showPreviewText();
}

// Save note color preference
function saveNoteColor(e) {
    const color = e.target.value;
    localStorage.setItem('noteColor', color);
    showSavedIndicator('Note color saved!');
    updateColorPreview(color);
}

// Toggle theme
function toggleTheme() {
    const body = document.body;
    const isDark = body.classList.toggle('dark-theme');
    const theme = isDark ? 'dark' : 'light';
    localStorage.setItem('theme', theme);
    updateThemeButton(isDark);
    showSavedIndicator('Theme updated!');
}

// Update theme button text
function updateThemeButton(isDark) {
    const toggleThemeBtn = document.getElementById('toggleTheme');
    toggleThemeBtn.innerHTML = `
        <i class="fas fa-${isDark ? 'sun' : 'moon'} mr-2"></i>
        ${isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
    `;
}

// Show preview text with current font size
function showPreviewText() {
    const fontSize = document.getElementById('fontSize').value;
    let previewSection = document.getElementById('previewSection');
    
    if (!previewSection) {
        // Create preview section if it doesn't exist
        previewSection = document.createElement('div');
        previewSection.id = 'previewSection';
        previewSection.className = 'preference-card bg-white p-6 rounded-lg shadow-md col-span-full';
        previewSection.innerHTML = `
            <h2 class="preference-title text-lg font-semibold text-gray-800 mb-4">Preview</h2>
            <div id="previewText" class="p-4 border rounded">
                <p class="note-title mb-2">Sample Note Title</p>
                <p class="note-content">This is how your notes will look with the selected font size.</p>
            </div>
        `;
        document.querySelector('.grid').appendChild(previewSection);
    }
    
    // Update font size classes
    const previewTitle = previewSection.querySelector('.note-title');
    const previewContent = previewSection.querySelector('.note-content');
    
    previewTitle.className = `note-title mb-2 font-semibold ${fontSize}`;
    previewContent.className = `note-content ${fontSize}`;
}

// Update color preview
function updateColorPreview(color) {
    let previewSection = document.getElementById('previewSection');
    if (previewSection) {
        const previewText = previewSection.querySelector('#previewText');
        previewText.style.backgroundColor = color;
    }
}

// Show saved indicator
function showSavedIndicator(message) {
    let indicator = document.getElementById('savedIndicator');
    
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'savedIndicator';
        indicator.className = 'fixed bottom-5 right-5 bg-green-500 text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300';
        document.body.appendChild(indicator);
    }
    
    indicator.textContent = message;
    indicator.style.opacity = '1';
    indicator.style.display = 'block';
    
    setTimeout(() => {
        indicator.style.opacity = '0';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 300);
    }, 2000);
}

// Add export/import preferences functionality
function exportPreferences() {
    const preferences = {
        fontSize: localStorage.getItem('fontSize') || 'text-base',
        noteColor: localStorage.getItem('noteColor') || '#ffffff',
        theme: localStorage.getItem('theme') || 'light'
    };
    
    const blob = new Blob([JSON.stringify(preferences, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'note-app-preferences.json';
    a.click();
    URL.revokeObjectURL(url);
    
    showSavedIndicator('Preferences exported!');
}

function importPreferences() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'application/json';
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                try {
                    const preferences = JSON.parse(event.target.result);
                    
                    // Apply imported preferences
                    if (preferences.fontSize) {
                        localStorage.setItem('fontSize', preferences.fontSize);
                        document.getElementById('fontSize').value = preferences.fontSize;
                    }
                    
                    if (preferences.noteColor) {
                        localStorage.setItem('noteColor', preferences.noteColor);
                        document.getElementById('noteColor').value = preferences.noteColor;
                    }
                    
                    if (preferences.theme) {
                        localStorage.setItem('theme', preferences.theme);
                        const isDark = preferences.theme === 'dark';
                        document.body.classList.toggle('dark-theme', isDark);
                        updateThemeButton(isDark);
                    }
                    
                    showPreviewText();
                    showSavedIndicator('Preferences imported successfully!');
                } catch (error) {
                    alert('Error importing preferences. Please check the file format.');
                }
            };
            reader.readAsText(file);
        }
    };
    input.click();
}