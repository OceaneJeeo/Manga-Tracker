/**
 * Manga Tracker JavaScript Module
 *
 * This script handles the client-side functionality for managing a manga collection.
 * It includes loading, displaying, adding, editing, and deleting mangas, as well as managing chapters.
 */

let mangas = [];
let deleteId = null;
let currentMangaChapters = null;
let deleteChapterId = null;

/**
 * Loads mangas on page load.
 */
document.addEventListener('DOMContentLoaded', function() {
    loadMangas();
});

/**
 * Loads mangas from the database via AJAX.
 * @async
 */
async function loadMangas() {
    try {
        const response = await fetch('get_mangas.php');
        const data = await response.json();
        
        if (data.success) {
            mangas = data.mangas;
            renderMangas();
            updateStats();
        } else {
            console.error('Error:', data.error);
        }
    } catch (error) {
        console.error('Loading error:', error);
    }
}

/**
 * Renders the mangas in the UI, separating reading and completed ones.
 */
function renderMangas() {
    const gridReading = document.getElementById('mangaGridReading');
    const gridCompleted = document.getElementById('mangaGridCompleted');
    const emptyState = document.getElementById('emptyState');
    
    const mangasReading = mangas.filter(m => m.status === 'reading');
    const mangasCompleted = mangas.filter(m => m.status === 'completed');
    
    if (mangas.length === 0) {
        gridReading.style.display = 'none';
        gridCompleted.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    // Display reading mangas
    if (mangasReading.length > 0) {
        gridReading.style.display = 'grid';
        gridReading.innerHTML = mangasReading.map(manga => renderMangaCard(manga)).join('');
    } else {
        gridReading.style.display = 'none';
    }
    
    // Display completed mangas
    if (mangasCompleted.length > 0) {
        gridCompleted.style.display = 'grid';
        gridCompleted.innerHTML = mangasCompleted.map(manga => renderMangaCard(manga, true)).join('');
    } else {
        gridCompleted.style.display = 'none';
    }
    
    document.getElementById('countReading').textContent = mangasReading.length;
    document.getElementById('countCompleted').textContent = mangasCompleted.length;
}

/**
 * Generates HTML for a manga card.
 * @param {Object} manga - The manga object.
 * @param {boolean} [isCompleted=false] - Whether the manga is completed.
 * @returns {string} The HTML string for the manga card.
 */
function renderMangaCard(manga, isCompleted = false) {
    // Escape apostrophes and quotes for JavaScript
    const jsEscapedTitle = manga.title.replace(/'/g, "\\'").replace(/"/g, '\\"');
    
    return `
        <div class="manga-card ${isCompleted ? 'manga-completed' : ''}" onclick="editManga(${manga.id})">
            <div class="manga-image-wrapper">
                ${manga.image ? 
                    `<img src="${manga.image}" alt="${escapeHtml(manga.title)}" class="manga-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                     <div class="manga-placeholder" style="display:none;">📖</div>` :
                    `<div class="manga-placeholder">📖</div>`
                }
                ${isCompleted ? '<div class="manga-badge">✅ Completed</div>' : ''}
                <div class="manga-overlay">
                    ${manga.notes ? escapeHtml(manga.notes.substring(0, 60)) + (manga.notes.length > 60 ? '...' : '') : 'No notes'}
                </div>
            </div>
            <div class="manga-info">
                <div class="manga-title" title="${escapeHtml(manga.title)}">${escapeHtml(manga.title)}</div>
                <div class="manga-chapter">📖 ${escapeHtml(manga.current_chapter)}</div>
            </div>
            <div class="manga-actions">
                <button class="btn-action btn-link" onclick="event.stopPropagation(); openLink('${manga.reading_link}')">Read</button>
                <button class="btn-action btn-chapters" onclick="event.stopPropagation(); openChaptersModal(${manga.id}, '${jsEscapedTitle}')">📦</button>
                <button class="btn-action btn-edit" onclick="event.stopPropagation(); editManga(${manga.id})">✏️</button>
                <button class="btn-action btn-delete" onclick="event.stopPropagation(); confirmDelete(${manga.id}, '${jsEscapedTitle}')">🗑️</button>
            </div>
        </div>
    `;
}

/**
 * Updates the statistics displayed on the page.
 */
function updateStats() {
    const totalReading = mangas.filter(m => m.status === 'reading').length;
    const totalCompleted = mangas.filter(m => m.status === 'completed').length;
    
    document.getElementById('totalMangas').textContent = mangas.length;
    document.getElementById('totalReading').textContent = totalReading;
    document.getElementById('totalCompleted').textContent = totalCompleted;
    
    if (mangas.length > 0) {
        const lastManga = mangas.reduce((latest, manga) => {
            const latestDate = new Date(latest.date_updated || latest.date_added);
            const currentDate = new Date(manga.date_updated || manga.date_added);
            return currentDate > latestDate ? manga : latest;
        });
        
        const lastDate = new Date(lastManga.date_updated || lastManga.date_added);
        document.getElementById('lastUpdate').textContent = formatDate(lastDate);
    } else {
        document.getElementById('lastUpdate').textContent = '-';
    }
}

/**
 * Formats a date for display.
 * @param {Date} date - The date to format.
 * @returns {string} The formatted date string.
 */
function formatDate(date) {
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)} min ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} d ago`;
    
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
}

/**
 * Formats file size for display.
 * @param {number} bytes - The file size in bytes.
 * @returns {string} The formatted file size string.
 */
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
}

/**
 * Opens the add/edit manga modal.
 */
function openModal() {
    document.getElementById('modal').style.display = 'block';
    document.getElementById('modalTitle').textContent = 'Add a manga';
    document.getElementById('mangaForm').reset();
    document.getElementById('mangaId').value = '';
    document.getElementById('status').value = 'reading';
}

/**
 * Closes the add/edit manga modal.
 */
function closeModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('mangaForm').reset();
}

/**
 * Edits a manga by populating the modal with its data.
 * @param {number} id - The ID of the manga to edit.
 */
async function editManga(id) {
    const manga = mangas.find(m => m.id === id);
    if (!manga) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Manga';
    document.getElementById('mangaId').value = manga.id;
    document.getElementById('title').value = manga.title;
    document.getElementById('imageUrl').value = manga.image && !manga.image.startsWith('img/') ? manga.image : '';
    document.getElementById('readingLink').value = manga.reading_link;
    document.getElementById('currentChapter').value = manga.current_chapter;
    document.getElementById('status').value = manga.status || 'reading';
    document.getElementById('notes').value = manga.notes || '';
    
    document.getElementById('modal').style.display = 'block';
}

/**
 * Handles the submission of the manga form.
 */
document.getElementById('mangaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('add_manga.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal();
            await loadMangas();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error while saving');
    }
});

/**
 * Opens the chapters modal for a specific manga.
 * @param {number} mangaId - The ID of the manga.
 * @param {string} mangaTitle - The title of the manga.
 */
async function openChaptersModal(mangaId, mangaTitle) {
    currentMangaChapters = mangaId;
    document.getElementById('chaptersMangaTitle').textContent = mangaTitle;
    document.getElementById('chapterMangaId').value = mangaId;
    document.getElementById('chaptersModal').style.display = 'block';
    
    await loadChapters(mangaId);
}

/**
 * Closes the chapters modal.
 */
function closeChaptersModal() {
    document.getElementById('chaptersModal').style.display = 'none';
    document.getElementById('chapterUploadForm').reset();
    currentMangaChapters = null;
}

/**
 * Loads chapters for a specific manga.
 * @param {number} mangaId - The ID of the manga.
 */
async function loadChapters(mangaId) {
    try {
        const formData = new FormData();
        formData.append('action', 'list');
        formData.append('manga_id', mangaId);
        
        const response = await fetch('manage_chapters.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            renderChaptersList(data.chapters);
        } else {
            console.error('Error:', data.error);
        }
    } catch (error) {
        console.error('Error loading chapters:', error);
    }
}

/**
 * Renders the list of chapters in the modal.
 * @param {Array<Object>} chapters - The array of chapter objects.
 */
function renderChaptersList(chapters) {
    const listContainer = document.getElementById('chaptersList');
    
    if (chapters.length === 0) {
        listContainer.innerHTML = '<p style="color: #9ca3af; text-align: center; padding: 2rem;">No chapters uploaded yet</p>';
        return;
    }
    
    listContainer.innerHTML = chapters.map(chapter => `
        <div class="chapter-item">
            <div class="chapter-info">
                <div class="chapter-number">📖 Chapter ${escapeHtml(chapter.chapter_number)}</div>
                <div class="chapter-meta">
                    ${formatFileSize(chapter.file_size)} • Added on ${new Date(chapter.date_added).toLocaleDateString('en-US')}
                </div>
            </div>
            <div class="chapter-actions">
                <button class="btn-action btn-download" onclick="downloadChapter('${chapter.file_path}')">📥 Download</button>
                <button class="btn-action btn-delete" onclick="deleteChapter(${chapter.id}, '${escapeHtml(chapter.chapter_number)}')">🗑️</button>
            </div>
        </div>
    `).join('');
}

/**
 * Handles the submission of the chapter upload form.
 */
document.getElementById('chapterUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Uploading...';
    
    try {
        const response = await fetch('manage_chapters.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            this.reset();
            await loadChapters(currentMangaChapters);
            alert('✅ Chapter uploaded successfully!');
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error while uploading chapter');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

/**
 * Downloads a chapter file.
 * @param {string} filePath - The path to the file.
 */
function downloadChapter(filePath) {
    window.open(filePath, '_blank');
}

/**
 * Deletes a chapter after confirmation.
 * @param {number} chapterId - The ID of the chapter.
 * @param {string} chapterNumber - The chapter number.
 */
async function deleteChapter(chapterId, chapterNumber) {
    if (!confirm(`Are you sure you want to delete chapter ${chapterNumber}?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('chapter_id', chapterId);
        
        const response = await fetch('manage_chapters.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadChapters(currentMangaChapters);
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error while deleting');
    }
}

/**
 * Opens a link in a new tab.
 * @param {string} url - The URL to open.
 */
function openLink(url) {
    window.open(url, '_blank');
}

/**
 * Confirms deletion of a manga.
 * @param {number} id - The ID of the manga.
 * @param {string} title - The title of the manga.
 */
function confirmDelete(id, title) {
    deleteId = id;
    document.getElementById('deleteItemName').textContent = title;
    document.getElementById('deletePopup').style.display = 'flex';
}

/**
 * Closes the delete confirmation popup.
 */
function closeDeletePopup() {
    document.getElementById('deletePopup').style.display = 'none';
    deleteId = null;
}

/**
 * Executes the deletion of a manga.
 */
async function executeDelete() {
    if (!deleteId) return;
    
    try {
        const formData = new FormData();
        formData.append('id', deleteId);
        
        const response = await fetch('delete_manga.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeDeletePopup();
            await loadMangas();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error while deleting');
    }
}

/**
 * Escapes HTML characters to prevent XSS.
 * @param {string} text - The text to escape.
 * @returns {string} The escaped text.
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Closes modals when clicking outside them.
 */
window.onclick = function(event) {
    const modal = document.getElementById('modal');
    const chaptersModal = document.getElementById('chaptersModal');
    
    if (event.target === modal) {
        closeModal();
    }
    if (event.target === chaptersModal) {
        closeChaptersModal();
    }
}